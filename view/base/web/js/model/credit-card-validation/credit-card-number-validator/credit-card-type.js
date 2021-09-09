/**
 * Copyright © Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */

/* @api */
define([
    'jquery',
    'mageUtils'
], function ($, utils) {
    'use strict';

    var types = [
        {
            title: 'Visa',
            type: 'VISA',
            pattern: '^4\\d*$',
            gaps: [4, 8, 12],
            lengths: [16],
            code: {
                name: 'CVV',
                size: 3
            }
        },
        // {
        //     title: 'Visa Debit',
        //     type: 'VISADEBIT',
        //     pattern: '^4\\d*$',
        //     gaps: [4, 8, 12],
        //     lengths: [16],
        //     code: {
        //         name: 'CVV',
        //         size: 3
        //     }
        // },
        {
            title: 'MasterCard',
            type: 'MASTER',
            pattern: '^(?:5[1-5][0-9]{2}|222[1-9]|22[3-9][0-9]|2[3-6][0-9]{2}|27[01][0-9]|2720)[0-9]{12}$',
            gaps: [4, 8, 12],
            lengths: [16],
            code: {
                name: 'CVC',
                size: 3
            }
        },
        // {
        //     title: 'MasterCard Debit',
        //     type: 'MASTERDEBIT',
        //     pattern: '^(?:5[1-5][0-9]{2}|222[1-9]|22[3-9][0-9]|2[3-6][0-9]{2}|27[01][0-9]|2720)[0-9]{12}$',
        //     gaps: [4, 8, 12],
        //     lengths: [16],
        //     code: {
        //         name: 'CVC',
        //         size: 3
        //     }
        // },
        {
            title: 'American Express',
            type: 'AMEX',
            pattern: '^3([47]\\d*)?$',
            isAmex: true,
            gaps: [4, 10],
            lengths: [15],
            code: {
                name: 'CID',
                size: 4
            }
        },
        {
            title: 'Diners',
            type: 'DINERS',
            pattern: '^(3(0[0-5]|095|6|[8-9]))\\d*$',
            gaps: [4, 10],
            lengths: [14, 16, 17, 18, 19],
            code: {
                name: 'CVV',
                size: 3
            }
        },
        {
            title: 'Discover',
            type: 'DISCOVER',
            pattern: '^(6011(0|[2-4]|74|7[7-9]|8[6-9]|9)|6(4[4-9]|5))\\d*$',
            gaps: [4, 8, 12],
            lengths: [16, 17, 18, 19],
            code: {
                name: 'CID',
                size: 3
            }
        },
        {
            title: 'UnionPay',
            type: 'UNIONPAY',
            pattern: '^(622(1(2[6-9]|[3-9])|[3-8]|9([[0-1]|2[0-5]))|62[4-6]|628([2-8]))\\d*?$',
            gaps: [4, 8, 12],
            lengths: [16, 17, 18, 19],
            code: {
                name: 'CVN',
                size: 3
            }
        },
        {
            title: 'Maestro',
            type: 'MAESTRO',
            pattern: '^6759(?!24|38|40|6[3-9]|70|76)|676770|676774\\d*$',
            gaps: [4, 8, 12],
            lengths: [12, 13, 14, 15, 16, 17, 18, 19],
            code: {
                name: 'CVC',
                size: 3
            }
        }
    ];

    return {
        /**
         * @param {*} cardNumber
         * @return {Array}
         */
        getCardTypes: function (cardNumber) {
            var i, value,
                result = [];

            if (utils.isEmpty(cardNumber)) {
                return result;
            }

            if (cardNumber === '') {
                return $.extend(true, {}, types);
            }

            for (i = 0; i < types.length; i++) {
                value = types[i];

                if (new RegExp(value.pattern).test(cardNumber)) {
                    result.push($.extend(true, {}, value));
                }
            }

            return result;
        }
    };
});
