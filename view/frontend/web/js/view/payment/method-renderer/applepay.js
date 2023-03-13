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

            initObservable: function () {
                let self = this;

                self._super().observe(['active', 'buttonActive']);

                return this;
            },

            addErrorMessage: function (message) {
                messageList.addErrorMessage({
                    message: message
                });
            },

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

            getAllowedBrandTypes: function () {
                return window.checkoutConfig.payment[this.getCode()].availableBrandTypes;
            },

            getButtonText: function () {
                return window.checkoutConfig.payment[this.getCode()].paymentBtnText;
            },

            getCode: function () {
                return this.code;
            },

            getCompleteMerchantValidationUrl: function () {
                return window.checkoutConfig.payment[this.getCode()].completeMerchantValidationUrl;
            },

            getData: function () {
                return {
                    'method': this.getCode(),
                    'additional_data': {
                        'token': this.token
                    }
                };
            },

            getDisplayName: function () {
                return window.checkoutConfig.payment[this.getCode()].displayName;
            },

            getMerchantId: function () {
                return window.checkoutConfig.payment[this.getCode()].merchantId;
            },

            getPaymentIcon: function () {
                return window.checkoutConfig.payment[this.getCode()].icon;
            },

            isActive: function () {
                let active = this.getCode() === this.isChecked();
                this.active(active);
                return active;
            },

            isButtonActive: function () {
                return this.isActive() && this.buttonActive;
            },

            setToken: function (token) {
                this.token = token;
            }
        });
    }
)
