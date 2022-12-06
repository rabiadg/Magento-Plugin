/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */

define(
    [
        'underscore',
        'ko',
        'jquery'
    ],
    function (_, ko, $) {
        'use strict';

        let isInAction = ko.observable(false);
        let isLightboxReady = ko.observable(false);

        return {
            isInAction: isInAction,
            isLightboxReady: isLightboxReady,

            /**
             * Stopping the event from propagating.
             * @param event
             */
            stopEventPropagation: function (event) {
                event.stopImmediatePropagation();
                event.preventDefault();
            },

            /**
             * This function is called when the user clicks on the "Leave Embedded Iframe" button
             */
            leaveEmbeddedIframe: function () {
                isInAction(false);
                isLightboxReady(false);
            },

            /**
             * This function is called when the user clicks on a link in the embedded iframe.
             * @param event
             */
            leaveIframeForLinks: function (event) {
                if ($(event.target).closest('a, span, button, input').length) {
                    isInAction(false);
                    isLightboxReady(false);
                } else {
                    event.stopImmediatePropagation();
                    event.preventDefault();
                }
            }
        };
    }
);
