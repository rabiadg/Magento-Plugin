<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Logger;

use Magento\Payment\Gateway\Config\Config as BaseConfig;

/**
 * Class Config
 * @package TotalProcessing\Opp\Logger
 */
class Config extends BaseConfig
{
    const KEY_DEBUG_MODE = 'debug';
    const KEY_DEBUG_LOG_FILE_NAME = "debug_log_filepath";
    const KEY_ERROR_LOG_FILE_NAME = "error_log_filepath";

    /**
     * @param null $storeId
     * @return bool
     */
    public function isDebugMode($storeId = null): bool
    {
        return (bool) $this->getValue(self::KEY_DEBUG_MODE, $storeId);
    }

    /**
     * @param null $storeId
     * @return string
     */
    public function getErrorLogFileName($storeId = null): string
    {
        return (string) $this->getValue(self::KEY_ERROR_LOG_FILE_NAME, $storeId);
    }

    /**
     * @param null $storeId
     * @return string
     */
    public function getDebugLogFileName($storeId = null): string
    {
        return (string) $this->getValue(self::KEY_DEBUG_LOG_FILE_NAME, $storeId);
    }
}
