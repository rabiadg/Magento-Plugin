/**
 * Copyright © Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'underscore',
        'jquery',
        'mage/translate'
    ],
    function (
        _,
        $,
        $t
    ) {
        'use strict';

        let base = {
            config: {
                merchantCapabilities: ["supports3DS"],
            }
        };

        /**
         * Extending the base config with the config passed in.
         *
         * @param config
         */
        base.configure = function (config) {
            this.config = _.extend(this.config, config);
        };


        /**
         * Setting the `process` function to the `base` object.
         *
         * @param data
         * @param component
         */
        base.process = function (data, component) {
            let paymentRequest = _.extend(base.config, data);
            let session = new ApplePaySession(6, paymentRequest);

            session.onvalidatemerchant = function (event) {
                component.buttonActive(false);

                if (!event.validationURL) {
                    session.abort();
                    component.addErrorMessage($t('Error validating merchant'));
                    component.buttonActive(true);
                    return;
                }

                $.ajax({
                    url: component.getCompleteMerchantValidationUrl(),
                    method: "GET",
                    data: {
                        validationUrl: event.validationURL
                    },
                    dataType: "json",
                    success: function (data) {
                        session.completeMerchantValidation(data);
                    },
                    error: function () {
                        session.abort();
                        component.addErrorMessage($t('Error validating merchant'));
                        component.buttonActive(true);
                    },
                })
            };

            session.onpaymentauthorized = function (event) {
                component.buttonActive(false);

                if (!event.payment.token) {
                    component.addErrorMessage($t('Error tokenizing Apple Pay'));
                    component.buttonActive(true);
                    session.completePayment(ApplePaySession.STATUS_FAILURE);
                    return;
                }

                session.completePayment(ApplePaySession.STATUS_SUCCESS);

                component.setToken(JSON.stringify(event.payment.token));
                component.placeOrder();
            };

            session.oncancel = function (event) {
                component.buttonActive(true);
            }

            session.begin();
        }

        return base;
    }
);
