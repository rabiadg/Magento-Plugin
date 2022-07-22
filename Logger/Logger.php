<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace TotalProcessing\Opp\Logger;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Monolog\Logger as BaseLogger;

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
    public $backtrace_limit;

    public function __construct
    (
        $name,
        array $handlers = array(),
        array $processors = array(),
        ConfigInterface $config,
        CheckoutSession $checkoutSession
    ) {
        $this->config = $config;
        $this->session = $checkoutSession;
        $this->backtrace_limit = 4;
        parent::__construct($name, $handlers, $processors);
    }

    /**
     * {@inheritdoc }
     */
    public function addRecord($level, $message, array $context = array()): bool
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $this->backtrace_limit);

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
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isDebugEnabled(): bool
    {
        return $this->config->isDebugMode($this->session->getQuote()->getStoreId());
    }
}
