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
            stopEventPropagation: function (event) {
                event.stopImmediatePropagation();
                event.preventDefault();
            },
            leaveEmbeddedIframe: function () {
                isInAction(false);
                isLightboxReady(false);
            },
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
