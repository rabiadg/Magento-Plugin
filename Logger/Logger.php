<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace TotalProcessing\Opp\Logger;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Monolog\Logger as BaseLogger;

/**
 * Class Logger
 * @package TotalProcessing\Opp\Logger
 */
class Logger extends BaseLogger
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * backtrace limit in depth
     *
     * @var int
     */
    public $backtraceLimit;

    /**
     * @param string $name
     * @param ConfigInterface $config
     * @param StoreManagerInterface $storeManager
     * @param array $handlers
     * @param array $processors
     */
    public function __construct (
        string $name,
        ConfigInterface $config,
        StoreManagerInterface $storeManager,
        array $handlers = [],
        array $processors = []
    ) {
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->backtraceLimit = 4;
        parent::__construct($name, $handlers, $processors);
    }

    /**
     * @return int
     */
    public function getBacktraceLimit(): int
    {
        return $this->backtraceLimit;
    }

    /**
     * @param int $limit
     * @return $this
     */
    public function setBacktraceLimit(int $limit): Logger
    {
        $this->backtraceLimit = $limit;
        return $this;
    }

    /**
     * @param $level
     * @param $message
     * @param array $context
     * @return bool
     * @throws NoSuchEntityException
     */
    public function addRecord($level, $message, array $context = array()): bool
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $this->getBacktraceLimit());

        $caller = array_pop($trace);
        $trace = array_pop($trace);
        $prefix = ($caller["class"]??"") . "->" . ($caller["function"]??"").
            " | " . ($trace["class"]??"") . "->" . ($trace["function"]??"") . ": ";
        // is debug mode enabled
        if ($level == self::DEBUG && !$this->isDebugEnabled($this->storeManager->getStore()->getId())) {
            return false;
        }

        return parent::addRecord($level, $prefix . $message, $context);
    }

    /**
     * @param null $storeId
     * @return bool
     */
    public function isDebugEnabled($storeId = null): bool
    {
        return $this->config->isDebugMode($storeId);
    }
}
