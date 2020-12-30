define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'totalprocessing_tpcards',
                component: 'TotalProcessing_TPCARDS/js/view/payment/method-renderer/tpcards-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
