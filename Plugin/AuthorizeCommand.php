<?php
/**
 * Copyright © Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Plugin;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Psr\Log\LoggerInterface;
use TotalProcessing\Opp\Gateway\Command\ScheduleCommand;
use TotalProcessing\Opp\Gateway\Config\Config;
use TotalProcessing\Opp\Gateway\SubjectReader;
use TotalProcessing\Opp\Model\System\Config\ScheduleType;

/**
 * Class AuthorizeCommand
 *
 * @TODO Change CommandException message with common message
 */
class AuthorizeCommand
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var CommandManagerInterface
     */
    private $commandManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var ScheduleType
     */
    private $scheduleType;

    /**
     * CaptureCommand constructor.
     *
     * @param Config $config
     * @param CommandManagerInterface $commandManager
     * @param LoggerInterface $logger
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        Config $config,
        CommandManagerInterface $commandManager,
        LoggerInterface $logger,
        ScheduleType $scheduleType,
        SubjectReader $subjectReader
    ) {
        $this->config = $config;
        $this->commandManager = $commandManager;
        $this->logger = $logger;
        $this->scheduleType = $scheduleType;
        $this->subjectReader = $subjectReader;
    }

    /**
     * @param \TotalProcessing\Opp\Gateway\Command\AuthorizeCommand $subject
     * @param \Closure $proceed
     * @param array $commandSubject
     * @return array[]
     * @throws CouldNotSaveException
     */
    public function aroundExecute(
        \TotalProcessing\Opp\Gateway\Command\AuthorizeCommand $subject,
        \Closure $proceed,
        array $commandSubject
    ) {
        $this->logger->debug("Around execute authorize start", $commandSubject);

        try {
            $proceed($commandSubject);

            $skipErrors = false;

            foreach ($this->getAvailableScheduleActions($commandSubject) as $scheduleAction) {
                $command = $this->commandManager->get(ScheduleCommand::COMMAND_CODE);

                if (!$command instanceof CommandInterface) {
                    $this->logger->critical(__("Schedule command not found"), []);
                    throw new CommandException(__("Schedule command not found"));
                }

                $subject = $commandSubject;
                $subject['schedule'] = $scheduleAction;
                $subject['skipErrors'] = $skipErrors;

                $command->execute($subject);

                $skipErrors = true;
            }
        } catch (LocalizedException $e) {
            $this->logger->critical($e->getMessage());
            throw new CouldNotSaveException(__($e->getMessage()));
        } catch (\Throwable $t) {
            $this->logger->critical($t->getMessage());
            throw new CouldNotSaveException(
                __('An error occurred on the server. Please try to place the order again.'),
                $t
            );
        }

        $this->logger->debug("Around execute authorize end");

        return ['commandSubject' => $commandSubject];
    }

    /**
     * Returns whether schedule command required
     *
     * @param array $commandSubject
     * @return array
     */
    private function getAvailableScheduleActions(array $commandSubject): array
    {
        $paymentDataObject = $this->subjectReader->readPayment($commandSubject);

        $order = $paymentDataObject->getOrder();
        $orderItems = $order->getItems();
        $storeId = $order->getStoreId();

        $scheduleSkuList = $this->config->getScheduleSkus($storeId);

        if (!$this->config->isSchedulerActive($storeId) || !$scheduleSkuList) {
            return [];
        }

        $scheduleActions = [];
        $patterns = $this->scheduleType->getScheduleActions();

        foreach ($orderItems as $item) {
            if (in_array($item->getSku(), $scheduleSkuList)) {
                $amount = $item->getQtyOrdered() * $item->getPriceInclTax() - $item->getDiscountAmount();

                $skuParts = explode('-', $item->getSku());
                $skuIdent = end($skuParts);
                $skuIdent = strtoupper($skuIdent);

                if ($skuIdent === $this->scheduleType::ANNUAL_IDENT) {
                    $scheduleAction = $patterns[$this->scheduleType::ANNUAL];
                } elseif ($skuIdent === $this->scheduleType::QUARTERLY_IDENT) {
                    $scheduleAction = $patterns[$this->scheduleType::QUARTERLY];
                } else {
                    $scheduleAction = $patterns[$this->scheduleType::MONTHLY];
                }

                if ($amount > 0) {
                    $scheduleAction[$this->scheduleType::ACTION_AMOUNT] = $amount;
                    $scheduleActions[] = $scheduleAction;
                }
            }
        }

        return $scheduleActions;
    }
}
