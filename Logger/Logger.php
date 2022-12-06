<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace TotalProcessing\Opp\Logger;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
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
     * @var Session
     */
    private $session;

    /**
     * backtrace limit in depth
     *
     * @var int
     */
    public $backtraceLimit;

    /**
     * @param string $name
     * @param ConfigInterface $config
     * @param CheckoutSession $checkoutSession
     * @param array $handlers
     * @param array $processors
     */
    public function __construct (
        string $name,
        ConfigInterface $config,
        CheckoutSession $checkoutSession,
        array $handlers = [],
        array $processors = []
    ) {
        $this->config = $config;
        $this->session = $checkoutSession;
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
     * {@inheritdoc }
     */
    public function addRecord($level, $message, array $context = array()): bool
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $this->getBacktraceLimit());

        $caller = array_pop($trace);
        $trace = array_pop($trace);
        $prefix = ($caller["class"]??"") . "->" . ($caller["function"]??"").
            " | " . ($trace["class"]??"") . "->" . ($trace["function"]??"") . ": ";
        // is debug mode enabled
        if ($level == self::DEBUG && $this->isDebugEnabled() == false) {
            return false;
        }

        return parent::addRecord($level, $prefix . $message, $context);
    }

    /**
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function isDebugEnabled(): bool
    {
        return $this->config->isDebugMode($this->session->getQuote()->getStoreId());
    }
}
