/**
 * Copyright © Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'jquery',
        'TotalProcessing_Opp/js/view/payment/method-renderer/iframe',
        'mage/translate',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/set-payment-information-extended'
    ],
    function (
        $,
        Component,
        $t,
        fullScreenLoader,
        setPaymentInformationExtended
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'TotalProcessing_Opp/payment/multishipping/iframe',
            },

            /**
             * Checking if the payment method is active.
             *
             * @returns {*}
             */
            isActive: function () {
                this.resizeIframe(0);
                return this._super();
            },

            /**
             * A function that is called when the page is loaded.
             */
            initEventListeners: function () {
                this.resizeIframe(0);
                this._super();
            },

            /**
             * This is a function that is called when the user clicks the "Place Order" button.
             */
            placeOrder: function () {
                this.setPaymentInformation();
            },

            /**
             * Set payment information
             */
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

            /**
             * If the payment fails, the user will be redirected to the checkout page after 5 seconds.
             *
             * @returns {*}
             */
            fail: function () {
                fullScreenLoader.stopLoader();
                setTimeout(function () {
                    window.location.reload()
                }, 5000);

                return this;
            },

            /**
             * This is a function that is called when the payment is successful.
             *
             * @returns {*}
             */
            done: function () {
                fullScreenLoader.stopLoader();
                $('#multishipping-billing-form').submit();
                return this;
            }
        });
    }
);
