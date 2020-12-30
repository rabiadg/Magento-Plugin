/*browser:true*/
/*global define*/
define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'TotalProcessing_TPCARDS/js/action/set-payment-method',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/payment/additional-validators',
        'TotalProcessing_TPCARDS/js/model/totalprocessing-payment-service',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Ui/js/modal/modal'
    ],
    function(ko, $, Component, setPaymentMethodAction, quote,
        additionalValidators, totalProcessingService, fullScreenLoader, errorProcessor, modal) {
        'use strict';
        var paymentMethod = ko.observable(null);
        return Component.extend({
            self: this,
            defaults: {
                template: 'TotalProcessing_TPCARDS/payment/tpcards-form'
            },
            isInAction: totalProcessingService.isInAction,
            isLightboxReady: totalProcessingService.isLightboxReady,
            iframeHeight: totalProcessingService.iframeHeight,
            iframeWidth: totalProcessingService.iframeWidth,
            initialize: function() {
                this._super();
                $(window).bind('message', function(event) {
                    if(event.originalEvent.origin !== window.checkoutConfig.payment['totalprocessing_tpcards'].originDomain){return;}
                    //console.log(event.originalEvent.data);
                    //totalProcessingService.iframeResize(event.originalEvent.data);
                });
            },
            resetIframe: function() {
                this.isLightboxReady(false);
                this.isInAction(false);
            },
            /**
             * Get action url for payment method iframe.
             * @returns {String}
             */
            getActionUrl: function(){
                if(quote.paymentMethod() !== null){
                    if(quote.paymentMethod().method === 'totalprocessing_tpcards'){
                        return window.checkoutConfig.payment['totalprocessing_tpcards'].redirectUrl;
                    }
                }
                return '';
            },
            setParentLogListner: function() {
                //console.log('listner func instantiated');
                window.document.addEventListener('parentLog', function(e) {
                    if(e.detail.hasOwnProperty('funcs')){
                        for (var i = 0, len = e.detail.funcs.length; i < len; i++) {
                            if(typeof window[e.detail.funcs[i].name] === 'function'){
                                window[e.detail.funcs[i].name](e.detail.funcs[i].args);
                            } else if(e.detail.funcs[i].name === 'leaveEmbeddedIframe'){
                                $('#tpcards_modalContent div.modal-inner-content').html('<h2>Payment declined : <small>' + e.detail.funcs[i].args[0].code + '</small></h2><p>' + e.detail.funcs[i].args[0].description + '</p>');
                                $('#tpcards_modalContent').modal('openModal');
                            } else if(e.detail.funcs[i].name === 'tpSetFrameHeight') {
                                $('#totalprocessing_tpcards_iframe').css('height', e.detail.funcs[i].args[0]+'px');
                            } else if(e.detail.funcs[i].name === 'setTpInAction') {
                                totalProcessingService.isLightboxReady(e.detail.funcs[i].args[0]);
                                totalProcessingService.isInAction(e.detail.funcs[i].args[0]);
                            } else if(e.detail.funcs[i].name === 'doCompletePayment'){
                                setPaymentMethodAction(true).done(function(response){
                                    console.log(response);
                                    var processUrl = window.checkoutConfig.payment['totalprocessing_tpcards'].redirectUrl + '?sqlQuote=1';
                                    console.log(processUrl);
                                    $.get(processUrl, function(responseProcess){
                                        var processData = JSON.parse(responseProcess);
                                        console.log(processData);
                                        if(typeof processData === 'object'){
                                            if(processData.hasOwnProperty('placeOrder')){
                                                if(processData.placeOrder.hasOwnProperty('status')){
                                                    if(processData.placeOrder.status === true){
                                                        if(processData.placeOrder.hasOwnProperty('redirect')){
                                                            if(processData.placeOrder.redirect !== false){
                                                                window.location.href = processData.placeOrder.redirect;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    });
                                }).fail(function(response){
                                    console.log(response);
                                });
                            }
                        }
                    }
                }, false);
            },
            initModal: function() {
                $('#tpcards_modalContent').modal({
                    type: 'popup',
                    responsive: true,
                    buttons: [{
                        text: $.mage.__('Continue'),
                        class: 'action',
                        click: function () {
                            this.closeModal();
                        }
                    }],
                    closed: function(){
                        $('#totalprocessing_tpcards_iframe').attr('src', window.checkoutConfig.payment['totalprocessing_tpcards'].redirectUrl);
                    }
                });
            },
            continueToPayment: function() {
                //console.log('continueToPayment');
                //console.log(quote.getQuoteId());
                if(this.validate() && additionalValidators.validate()) {
                    if(window.checkoutConfig.payment[quote.paymentMethod().method].iframeEnabled === '1'){
                        setPaymentMethodAction(false).done(function(response){
                            if(response){
                                var updtLink = window.checkoutConfig.payment['totalprocessing_tpcards'].redirectUrl + '?checkoutId=' + $('#totalprocessing_tpcards_iframe')[0].contentWindow.checkoutId;
                                $.get(updtLink, function(responseUpdate){
                                    var jsonData = JSON.parse(responseUpdate);
                                    //console.log(jsonData);
                                    if(typeof jsonData === 'object'){
                                        if(jsonData.hasOwnProperty('responseData')){
                                            if(jsonData.responseData.hasOwnProperty('result')){
                                                if(jsonData.responseData.result.hasOwnProperty('code')){
                                                    if(jsonData.responseData.result.code === '000.200.101'){
                                                        //console.log('wpwl execution');
                                                        $('#totalprocessing_tpcards_iframe')[0].contentWindow.wpwl.executePayment('wpwl-container-card');
                                                    }
                                                }
                                            }
                                        }
                                    }
                                });
                            }
                        }).fail(function(response){
                            //console.log(response);
                        });
                    } else {
                        //console.log('iframeEnabled !== 1');
                    }
                } else {
                    //console.log('additionalValidators error.');
                }
                return false;
            },
            validate: function() {
                return true;
            },
            /**
             * Hide loader when iframe is fully loaded.
             * @returns {void}
             */
            iframeLoaded: function() {
                fullScreenLoader.stopLoader();
            },
            /**
             * Hide loader when iframe is fully loaded.
             * @returns {void}
             */
            iframeLoaded2: function() {
                //console.log('frame2 LOADED');
            }
        });
    }
);
