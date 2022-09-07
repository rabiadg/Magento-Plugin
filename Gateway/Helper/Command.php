<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Helper;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Command\CommandException;
use Psr\Log\LoggerInterface;
use TotalProcessing\Opp\Gateway\Command\PreAuthorizeCommand;
use TotalProcessing\Opp\Gateway\Config\Config;

/**
 * Class Command
 * @package TotalProcessing\Opp\Gateway\Helper
 */
class Command
{
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CommandManagerInterface
     */
    protected $commandManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param CheckoutSession $checkoutSession
     * @param Config $config
     * @param CommandManagerInterface $commandManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        Config $config,
        CommandManagerInterface $commandManager,
        LoggerInterface $logger
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->config = $config;
        $this->commandManager = $commandManager;
        $this->logger = $logger;
    }

    /**
     * Returns total processing checkout id
     *
     * @return string
     * @throws \Exception
     */
    public function getCheckoutId(): string
    {
        try {
            $this->checkoutSession->unsCheckoutId();

            $this->logger->debug("Get Command: " . PreAuthorizeCommand::COMMAND_CODE);
            $command = $this->commandManager->get(PreAuthorizeCommand::COMMAND_CODE);
            if (!$command instanceof CommandInterface) {
                $this->logger->critical(__("Pre-Authorize command not found"), []);
                throw new CommandException(__("Pre-Authorize command not found"));
            }

            $command->execute([
                'amount' => $this->checkoutSession->getQuote()->getGrandTotal(),
                'currencyCode' => $this->checkoutSession->getQuote()->getCurrency()->getQuoteCurrencyCode(),
                'entityId' => $this->config->getEntityId(),
            ]);
        } catch (\Throwable $t) {
            $this->logger->critical($t->getMessage(), []);
            throw new \Exception($t->getMessage());
        }

        return $this->checkoutSession->getCheckoutId() ?? '';
    }

    /**
     * Returns whether scheduler applicable
     *
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function isSchedulerActive(): bool
    {
        $quote = $this->checkoutSession->getQuote();
        $storeId = $quote->getStoreId();
        $scheduleSkuList = $this->config->getScheduleSkus($storeId);
        if (!$this->config->isSchedulerActive($storeId) || !$scheduleSkuList) {
            return false;
        }

        foreach ($quote->getAllVisibleItems() as $item) {
            $amount = $item->getQty() * $item->getPriceInclTax() - $item->getDiscountAmount();
            if (in_array($item->getSku(), $scheduleSkuList) && $amount > 0) {
                return true;
            }
        }

        return false;
    }
}
