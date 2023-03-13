<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace TotalProcessing\Opp\Logger\Handler;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Filesystem\DriverInterface;
use Monolog\Logger;
use TotalProcessing\Opp\Logger\Config;

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
    protected $fileName;

    /**
     * Debug constructor.
     *
     * @param DriverInterface $filesystem
     * @param null            $filePath
     * @param null            $fileName
     * @param Config          $config
     * @param CheckoutSession $checkoutSession
     * @throws \Exception
     */
    public function __construct(
        DriverInterface $filesystem,
        $filePath = null,
        $fileName = null,
        Config $config,
        CheckoutSession $checkoutSession
    )
    {
        $this->fileName = $config->getErrorLogFileName($checkoutSession->getQuote()->getStoreId());
        parent::__construct($filesystem, $filePath, $this->fileName);
    }
}
