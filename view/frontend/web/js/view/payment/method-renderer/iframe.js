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
                isLightboxReady: iframeService.isLightboxReady,
                isLoading: ko.observable(false),
                isPlainFramePlaceholderVisible: ko.observable(false),
                isCardFramePlaceholderVisible: ko.observable(false)
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
             * Returning the style attribute value for the iframe.
             *
             * @returns {string}
             */
            getStyle: function () {
                if (this.isActive()) {
                    return window.checkoutConfig.payment[this.getCode()].iframeStyles;
                }
                return "";
            },

            /**
             * Checking if the payment method is active.
             *
             * @returns {*|boolean}
             */
            isActive: function () {
                let isActive = this.getCode() === this.isChecked() && this.isCountryAvailable();
                this.processingFramePlaceholder(true);
                return isActive;
            },

            /**
             * Returning the source of the iframe.
             *
             * @returns {*}
             */
            getSource: function () {
                return window.checkoutConfig.payment[this.getCode()].source;
            },

            /**
             * This function is called when the iframe is loaded.
             */
            iframeLoaded: function () {
                fullScreenLoader.stopLoader();
            },

            /**
             * This function is called when the iframe is loaded.
             */
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

                    if (!self.isVisible()) {
                        self.processingFramePlaceholder(false);
                    }
                });
            },

            /**
             * This function is called when the payment method is selected.
             *
             * @returns {*}
             */
            selectPaymentMethod: function () {
                this.processingFramePlaceholder(true);
                return this._super();
            },

            /**
             * This function is used to resize the iframe.
             *
             * @param height
             */
            resizeIframe: function (height) {
                let iframe = document.getElementById(this.getCode() + "_iframe");

                if ($(iframe).length > 0) {
                    iframe.height = height + "px";
                }
            },

            /**
             * This function is called when the user clicks on the "Place Order" button.
             */
            placePendingPaymentOrder: function () {
                let iframe = document.getElementById(this.getCode() + '_iframe');

                iframe.contentWindow.wpwl.executePayment('wpwl-container-card');
            },

            /**
             * This function is used to get the text of the button.
             * @returns {*}
             */
            getButtonText: function () {
                return window.checkoutConfig.payment[this.getCode()].paymentBtnText;
            },

            /**
             * This function is called when the user clicks on the "Place Order" button.
             *
             * @returns {*}
             */
            getPlaceOrderDeferredObject: function () {
                let self = this;

                this.processingFramePlaceholder(true);

                return this._super().fail(function () {
                    fullScreenLoader.stopLoader();
                    self.processingFramePlaceholder(false);
                    self.isInAction(false);
                    document.removeEventListener('click', iframeService.stopEventPropagation, true);

                    setTimeout(function () {
                        window.location.reload()
                    }, 5000);
                });
            },

            /**
             * This function is used to check if the country is available for the payment method.
             *
             * @returns {boolean|*}
             */
            isCountryAvailable: function () {
                let country = quote.billingAddress._latestValue.countryId;
                let listed = window.checkoutConfig.payment[this.getCode()].availableCountries;

                if (listed.find(element => element == "All" || element == country)) {
                    return this.isRadioButtonVisible();
                }

                return false;
            },

            /**
             * This function is used to show the loading spinner when the iframe is loading.
             *
             * @param visibilityFlag
             */
            processingFramePlaceholder: function (visibilityFlag) {
                let config = window.checkoutConfig.payment[this.getCode()], styleOptions;

                if (config.hasOwnProperty('styleOptions')) {
                    styleOptions = config.styleOptions;
                }

                if (styleOptions == 'plain') {
                    this.isPlainFramePlaceholderVisible(visibilityFlag);
                }
                if (styleOptions == 'card') {
                    this.isCardFramePlaceholderVisible(visibilityFlag);
                }
            }
        });
    }
)
