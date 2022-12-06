/**
 * Copyright © Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'ko',
    'uiComponent',
    'mage/url',
    'Magento_SalesRule/js/action/set-coupon-code',
    'Magento_SalesRule/js/action/cancel-coupon'
], function (
    ko,
    Component,
    url,
    setCouponCodeAction,
    cancelCouponAction
) {
    'use strict';

    return Component.extend({
        /** @inheritdoc */
        initialize: function () {
            let self = this;
            this._super();

            setCouponCodeAction.registerSuccessCallback(function () {
                self._reInitPaymentForm();
            });
            cancelCouponAction.registerSuccessCallback(function () {
                self._reInitPaymentForm();
            });
        },

        /**
         * @private
         */
        _reInitPaymentForm: function () {
            let iframe = document.getElementById("totalprocessing_opp_iframe");

            if (iframe) {
                let iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                // we must re-init pre-authorization command to re-generate new checkout ID
                // to pickup new prices based on a coupon actions
                iframeDoc.location.reload();
            }
        }
    });
});
