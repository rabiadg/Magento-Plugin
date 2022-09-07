<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace TotalProcessing\Opp\Logger\Handler;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;
use TotalProcessing\Opp\Logger\Config;

/**
 * Class Debug
 * @package TotalProcessing\Opp\Logger\Handler
 */
class Debug extends Base
{
    /**
     * Logging level.
     *
     * @var int
     */
    protected $loggerType = Logger::DEBUG;

    /**
     * File name.
     *
     * @var string
     */
    protected $fileName;

    /**
     * @param DriverInterface $filesystem
     * @param Config $config
     * @param CheckoutSession $checkoutSession
     * @param $filePath
     * @param $fileName
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function __construct(
        DriverInterface $filesystem,
        Config $config,
        CheckoutSession $checkoutSession,
        $filePath = null,
        $fileName = null
    ) {
        $this->fileName = $config->getDebugLogFileName($checkoutSession->getQuote()->getStoreId());
        parent::__construct($filesystem, $filePath, $this->fileName);
    }
}
