/**
 * Copyright © Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list',
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';

        let config = window.checkoutConfig.payment,
            totalProcessingOpp = 'totalprocessing_opp',
            totalProcessingOppApplePay = 'totalprocessing_opp_applepay';

        if (config[totalProcessingOpp].isActive) {
            rendererList.push(
                {
                    type: totalProcessingOpp,
                    component: 'TotalProcessing_Opp/js/view/payment/method-renderer/iframe'
                }
            );
        }

        if (
            config[totalProcessingOppApplePay].isActive
            && window.ApplePaySession
            && ApplePaySession.canMakePayments()
        ) {
            rendererList.push(
                {
                    type: totalProcessingOppApplePay,
                    component: 'TotalProcessing_Opp/js/view/payment/method-renderer/applepay'
                }
            );
        }

        return Component.extend({});
    }
);
