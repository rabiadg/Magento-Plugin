<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Helper;

/**
 * Class SuccessCode
 * @package TotalProcessing\Opp\Gateway\Helper
 */
class SuccessCode
{
    const CHECKOUT_CREATE = '000.200.100';
    const CHECKOUT_UPDATE = '000.200.101';
    const TRANSACTION_SUCCESSFUL = '000.000.000';
    const TRANSACTION_SUCCESSFUL_TEST = '000.100.110';
    const TRANSACTION_CHECK_SUCCESS_CODE = '000.000.100';

    /**
     * Returns checkout create success codes
     *
     * @return string[]
     */
    public static function getCheckoutCreateCodes(): array
    {
        return [
            self::CHECKOUT_CREATE,
        ];
    }

    /**
     * Returns checkout update success codes
     *
     * @return string[]
     */
    public static function getCheckoutUpdateCodes(): array
    {
        return [
            self::CHECKOUT_UPDATE,
        ];
    }

    /**
     * Returns transaction check success codes
     *
     * @return string[]
     */
    public static function getSuccessfulTransactionCheckCodes(): array
    {
        return [
            self::TRANSACTION_CHECK_SUCCESS_CODE,
        ];
    }

    /**
     * Returns successful transaction codes
     *
     * @return string[]
     */
    public static function getSuccessfulTransactionCodes(): array
    {
        return [
            self::TRANSACTION_SUCCESSFUL,
            self::TRANSACTION_SUCCESSFUL_TEST,
        ];
    }
}
