<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace TotalProcessing\Opp\Logger\Handler;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;
use TotalProcessing\Opp\Logger\Config;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Critical
 * @package TotalProcessing\Opp\Logger\Handler
 */
class Critical extends Base
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
     * @param DriverInterface $filesystem
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     * @param $filePath
     * @param $fileName
     * @throws NoSuchEntityException
     */
    public function __construct(
        DriverInterface $filesystem,
        Config $config,
        StoreManagerInterface $storeManager,
        $filePath = null,
        $fileName = null
    ) {
        $this->fileName = $config->getErrorLogFileName($storeManager->getStore()->getId());
        parent::__construct($filesystem, $filePath, $this->fileName);
    }
}
