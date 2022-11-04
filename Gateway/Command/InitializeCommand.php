<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Command;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Sales\Model\Order;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Model\Order\Payment;
use Psr\Log\LoggerInterface;
use TotalProcessing\Opp\Model\System\Config\PaymentAction;

/**
 * Class InitializeCommand
 * @package TotalProcessing\Opp\Gateway\Command
 */
class InitializeCommand implements CommandInterface
{
    /**
     * @var CommandManagerInterface
     */
    private $commandManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param CommandManagerInterface $commandManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        CommandManagerInterface $commandManager,
        LoggerInterface $logger
    ) {
        $this->commandManager = $commandManager;
        $this->logger = $logger;
    }

    /**
     * @throws CommandException
     * @throws LocalizedException
     */
    public function execute(array $commandSubject)
    {
        $paymentAction = $commandSubject['paymentAction'] ?? null;
        if (!$paymentAction) {
            throw new \InvalidArgumentException('Payment action should be provided.');
        }

        $stateObject = SubjectReader::readStateObject($commandSubject);
        $paymentDO = SubjectReader::readPayment($commandSubject);

        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();
        /** @var Order $order */
        $order = $payment->getOrder();

        switch ($paymentAction) {
            case PaymentAction::AUTHORIZE:
                $this->processAuthorize($order, $payment, $stateObject);
                break;
            case PaymentAction::AUTHORIZE_CAPTURE:
                $this->processCapture($order, $payment, $stateObject);
                break;
            case PaymentAction::DEBIT:
                $this->processDebit($order, $payment, $stateObject, $commandSubject);
                break;
            default:
                break;
        }
    }

    /**
     * @param Order $order
     * @param Payment $payment
     * @param $stateObject
     * @return void
     */
    private function processAuthorize(Order $order, Payment $payment, $stateObject)
    {
        $totalDue = $order->getTotalDue();
        $baseTotalDue = $order->getBaseTotalDue();

        $order->setCanSendNewEmailFlag(false);
        $payment->authorize(true, $baseTotalDue);
        // base amount will be set inside
        $payment->setAmountAuthorized($totalDue);
        $order->setCustomerNote(__('Authorize payment action by OPP.'));
        $this->updateStateObject(
            $stateObject,
            Order::STATE_NEW,
            $order->getConfig()->getStateDefaultStatus(Order::STATE_NEW)
        );
    }

    /**
     * @param Order $order
     * @param Payment $payment
     * @param $stateObject
     * @return void
     * @throws LocalizedException
     */
    private function processCapture(Order $order, Payment $payment, $stateObject)
    {
        $totalDue = $order->getTotalDue();
        $baseTotalDue = $order->getBaseTotalDue();

        $order->setCanSendNewEmailFlag(false);
        $payment->setAmountAuthorized($totalDue);
        $payment->setBaseAmountAuthorized($baseTotalDue);
        $payment->capture();
        $order->setCustomerNote(__('Capture payment action by OPP.'));
        $this->updateStateObject(
            $stateObject,
            Order::STATE_PROCESSING,
            $order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING)
        );
    }

    /**
     * @param Order $order
     * @param Payment $payment
     * @param $stateObject
     * @param $commandSubject
     * @return void
     * @throws CommandException
     * @throws NotFoundException|LocalizedException
     */
    private function processDebit(Order $order, Payment $payment, $stateObject, $commandSubject)
    {
        try {
            $command = $this->commandManager->get(TransactionCheckCommand::COMMAND_CODE);
            if (!$command instanceof CommandInterface) {
                $this->logger->critical(__("Transaction check command should be provided."), []);
                throw new CommandException(__("Transaction check command should be provided."));
            }
            $command->execute($commandSubject);
        } catch (CommandException $e) {
            $this->logger->critical(__($e->getMessage()), []);
            throw new CommandException(__($e->getMessage()));
        }

        $totalDue = $order->getTotalDue();
        $baseTotalDue = $order->getBaseTotalDue();

        $order->setCanSendNewEmailFlag(false);
        $payment->setAmountAuthorized($totalDue);
        $payment->setBaseAmountAuthorized($baseTotalDue);
        $payment->capture();
        $order->setCustomerNote(__('Debit payment action by OPP.'));
        $this->updateStateObject(
            $stateObject,
            Order::STATE_PROCESSING,
            $order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING)
        );
    }

    /**
     * Updates the state object
     *
     * @param object $stateObject
     * @param string $orderState
     * @param string $orderStatus
     * @return void
     */
    private function updateStateObject(object $stateObject, string $orderState, string $orderStatus): void
    {
        $stateObject->setState($orderState);
        $stateObject->setStatus($orderStatus);
        $stateObject->setIsNotified(true);
    }
}
