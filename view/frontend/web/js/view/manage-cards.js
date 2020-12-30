define(
    [
        'jquery',
        'Magento_Ui/js/form/form',
        'ko',
        'TotalProcessing_TPCARDS/js/model/manage-cards-service'
    ],
    function($, Component, ko, manageCards) {
        'use strict';

        return Component.extend({
            iframeHeight: manageCards.iframeHeight,
            iframeWidth: manageCards.iframeWidth,
            iframeUrl: manageCards.iframeUrl,
            defaults: {
                template: 'TotalProcessing_TPCARDS/cards/manage'
            },
            initObservable: function() {
                return this;
            },
            initialize: function() {
                this._super();
                $(window).bind('message', function(event) {
                    manageCards.iframeResize(event.originalEvent.data);
                });
            }
        });
    }
);
