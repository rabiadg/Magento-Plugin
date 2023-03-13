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

            isActive: function () {
                this.resizeIframe(0);
                return this._super();
            },

            initEventListeners: function () {
                this.resizeIframe(0);
                this._super();
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

                setTimeout(function () {
                    window.location.reload()
                }, 5000);

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
