define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'Magento_Customer/js/model/customer'
    ],
    function($, quote, urlBuilder, storage, customer) {
        'use strict';
        var agreementsConfig = window.checkoutConfig.checkoutAgreements;
        return function(complete) {
            var endPoint = 'set-payment-information';
            var serviceUrl,
                payload,
                paymentData = quote.paymentMethod();
            if (paymentData.title) {
                delete paymentData.title;
            }
            if(paymentData.hasOwnProperty('__disableTmpl')) { 
                delete paymentData.__disableTmpl;
            }
            if(complete === true){
                endPoint = 'payment-information';
            }
            if (agreementsConfig.isEnabled) {
                var agreementForm = $('.payment-method._active form[data-role=checkout-agreements]'),
                    agreementData = agreementForm.serializeArray(),
                    agreementIds = [];

                agreementData.forEach(function(item) {
                    agreementIds.push(item.value);
                });

                paymentData.extension_attributes = {
                    agreement_ids: agreementIds
                };
            }
            
            if (!customer.isLoggedIn()) {
                serviceUrl = urlBuilder.createUrl('/guest-carts/:quoteId/' + endPoint, {
                    quoteId: quote.getQuoteId()
                });
                payload = {
                    cartId: quote.getQuoteId(),
                    email: quote.guestEmail,
                    paymentMethod: paymentData,
                    billingAddress: quote.billingAddress()
                };
            } else {
                serviceUrl = urlBuilder.createUrl('/carts/mine/' + endPoint, {});
                payload = {
                    cartId: quote.getQuoteId(),
                    paymentMethod: paymentData,
                    billingAddress: quote.billingAddress()
                };
            }
            //console.log(serviceUrl);
            return storage.post(
                serviceUrl, JSON.stringify(payload)
            );
        };
    }
);
