/**
 * Copyright © Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/quote',
        'Magento_Ui/js/model/messageList',
        'TotalProcessing_Opp/js/service/payment/applepay',
        'mage/translate'
    ],
    function (
        Component,
        fullScreenLoader,
        quote,
        messageList,
        applePay,
        $t
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                active: false,
                buttonActive: true,
                code: 'totalprocessing_opp_applepay',
                template: 'TotalProcessing_Opp/payment/applepay',
                token: null
            },

            /**
             * Function that is called when the component is initialized.
             *
             * @returns {*}
             */
            initObservable: function () {
                let self = this;

                self._super().observe(['active', 'buttonActive']);

                return this;
            },

            /**
             * Adding an error message to the message list.
             *
             * @param message
             */
            addErrorMessage: function (message) {
                messageList.addErrorMessage({
                    message: message
                });
            },

            /**
             * This is a function that is called when the user clicks the place order button.
             */
            beforePlaceOrder: function () {
                let self = this;

                self.buttonActive(false);

                if (!quote.billingAddress()) {
                    self.addErrorMessage($t("Billing address is required"));
                    self.buttonActive(true);
                    return;
                }

                let data = {
                    total: {
                        label: this.getDisplayName(),
                        amount: quote.totals()['base_grand_total']
                    },
                    currencyCode: quote.totals()['base_currency_code'],
                    supportedNetworks: this.getAllowedBrandTypes(),
                    countryCode: quote.billingAddress().countryId
                };

                applePay.process(data, self);
            },

            /**
             * Getting the allowed brand types from the config.
             *
             * @returns {*}
             */
            getAllowedBrandTypes: function () {
                return window.checkoutConfig.payment[this.getCode()].availableBrandTypes;
            },

            /**
             * Getting the button text from the config.
             *
             * @returns {*}
             */
            getButtonText: function () {
                return window.checkoutConfig.payment[this.getCode()].paymentBtnText;
            },

            /**
             * Returning the code of the payment method.
             *
             * @returns {*}
             */
            getCode: function () {
                return this.code;
            },

            /**
             * Get the URL that the Apple Pay JS SDK will call to validate the merchant.
             *
             * @returns {*}
             */
            getCompleteMerchantValidationUrl: function () {
                return window.checkoutConfig.payment[this.getCode()].completeMerchantValidationUrl;
            },

            /**
             * Returning the data that will be sent to the server.
             *
             * @returns {{additional_data: {token}, method: *}}
             */
            getData: function () {
                return {
                    'method': this.getCode(),
                    'additional_data': {
                        'token': this.token
                    }
                };
            },

            /**
             * Getting the display name from the config.
             *
             * @returns {*}
             */
            getDisplayName: function () {
                return window.checkoutConfig.payment[this.getCode()].displayName;
            },

            /**
             * Getting the merchant id from the config.
             *
             * @returns {null|*}
             */
            getMerchantId: function () {
                return window.checkoutConfig.payment[this.getCode()].merchantId;
            },

            /**
             * Getting the payment icon from the config.
             *
             * @returns {*}
             */
            getPaymentIcon: function () {
                return window.checkoutConfig.payment[this.getCode()].icon;
            },

            /**
             * Checking if the payment method is active.
             *
             * @returns {boolean}
             */
            isActive: function () {
                let active = this.getCode() === this.isChecked();
                this.active(active);
                return active;
            },

            /**
             * Checking if the payment method is active and if the button is active.
             *
             * @returns {false|boolean}
             */
            isButtonActive: function () {
                return this.isActive() && this.buttonActive;
            },

            /**
             * Setting the token that is returned from the Apple Pay JS SDK.
             *
             * @param token
             */
            setToken: function (token) {
                this.token = token;
            }
        });
    }
)
