/**
 * Copyright © Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'jquery',
        'TotalProcessing_Opp/js/view/payment/method-renderer/applepay',
        'mage/translate'
    ],
    function (
        $,
        Component
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'TotalProcessing_Opp/payment/multishipping/applepay',
            },

            placeOrder: function () {
                this.setPaymentInformation();
            },

            setPaymentInformation: function () {
                fullScreenLoader.startLoader();

                $.when(
                    setPaymentInformationExtended(
                        this.messageContainer,
                        this.getData(),
                        false
                    )
                ).done(this.done.bind(this))
                    .fail(this.fail.bind(this));
            },

            fail: function () {
                fullScreenLoader.stopLoader();

                return this;
            },

            done: function () {
                fullScreenLoader.stopLoader();

                $('#multishipping-billing-form').submit();

                return this;
            }
        });
    }
);
