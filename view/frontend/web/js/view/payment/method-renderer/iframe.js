/**
 * Copyright © Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'jquery',
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'TotalProcessing_Opp/js/model/iframe',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/quote',
    ],
    function (
        $,
        ko,
        Component,
        iframeService,
        fullScreenLoader,
        quote
    ) {
        'use strict';

        let isVisible = ko.observable(false);

        return Component.extend({
            defaults: {
                template: 'TotalProcessing_Opp/payment/iframe',
                active: false,
                isVisible: isVisible,
                code: 'totalprocessing_opp',
                isInAction: iframeService.isInAction,
                isLightboxReady: iframeService.isLightboxReady
            },

            getCode: function () {
                return this.code;
            },

            /**
             * iframe style attribute value
             * @returns {string}
             */
            getStyle: function () {
                if (this.isActive()) {
                    return window.checkoutConfig.payment[this.getCode()].iframeStyles;
                } else {
                    return ""
                }
            },

            isActive: function () {
                return this.getCode() === this.isChecked() && this.isCountryAvailable();
            },

            getSource: function () {
                return window.checkoutConfig.payment[this.getCode()].source;
            },

            iframeLoaded: function () {
                fullScreenLoader.stopLoader();
            },

            initEventListeners: function () {
                let self = this;

                window.onbeforeunload = function (e) {
                    self.resizeIframe(0);
                };

                window.document.addEventListener("iframe", function (e) {
                    let iframe = document.getElementById(self.getCode() + "_iframe");

                    iframe.height = e.detail.iframeHeight + "px";
                    self.isVisible(e.detail.isVisible);

                    if (e.detail.placeOrder) {
                        fullScreenLoader.startLoader();
                        self.placeOrder();
                    }
                });
            },

            selectPaymentMethodClick: function () {
                return this.selectPaymentMethod();
            },

            resizeIframe: function (height) {
                let iframe = document.getElementById(this.getCode() + "_iframe");

                if ($(iframe).length > 0) {
                    iframe.height = height + "px";
                }
            },

            placePendingPaymentOrder: function () {
                let iframe = document.getElementById(this.getCode() + '_iframe');

                iframe.contentWindow.wpwl.executePayment('wpwl-container-card');
            },

            getButtonText: function () {
                return window.checkoutConfig.payment[this.getCode()].paymentBtnText;
            },

            getPlaceOrderDeferredObject: function () {
                let self = this;

                return this._super().fail(function () {
                    fullScreenLoader.stopLoader();
                    self.isInAction(false);
                    document.removeEventListener('click', iframeService.stopEventPropagation, true);

                    setTimeout(function () {
                        window.location.reload()
                    }, 5000);
                });
            },

            isCountryAvailable: function () {
                let country = quote.billingAddress._latestValue.countryId;
                let listed = window.checkoutConfig.payment[this.getCode()].availableCountries;

                if (listed.find(element => element == "All" || element == country)) {
                    return this.isRadioButtonVisible();
                }

                return false;
            }
        });
    }
)
