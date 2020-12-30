<?php

namespace TotalProcessing\TPCARDS\Logger\Handler;

use Monolog\Logger;

class Critical extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level.
     *
     * @var int
     */
    protected $loggerType = Logger::CRITICAL;

    /**
     * File name.
     *
     * @var string
     */
    protected $fileName = '/var/log/totalprocessing/error.log';
}
