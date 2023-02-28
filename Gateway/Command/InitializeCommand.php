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
                $this->processCapture($order, $payment, $stateObject, 'capture');
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
     * @param string $type
     * @return void
     * @throws LocalizedException
     */
    private function processCapture(Order $order, Payment $payment, $stateObject, string $type): void
    {
        $totalDue = $order->getTotalDue();
        $baseTotalDue = $order->getBaseTotalDue();

        $order->setCanSendNewEmailFlag(false);
        $payment->setAmountAuthorized($totalDue);
        $payment->setBaseAmountAuthorized($baseTotalDue);
        $payment->capture();
        $order->setCustomerNote(__(ucwords($type) . ' payment action by OPP.'));
        $this->updateStateObject(
            $stateObject,
            Order::STATE_PROCESSING,
            $order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING)
        );
    }

    /**
     * @param array $commandSubject
     * @param Order $order
     * @return void
     * @throws CommandException
     * @throws NotFoundException
     */
    private function executeCancelCommand(array $commandSubject, Order $order): void
    {
        if (!isset($commandSubject['amount'])) {
            $commandSubject['amount'] = $order->getGrandTotal();
        }
        if (!isset($commandSubject['currencyCode'])) {
            $commandSubject['currencyCode'] = $order->getOrderCurrency()->getCurrencyCode();
        }

        $this->logger->debug("Before execute cancel", $commandSubject);
        try {
            $command = $this->commandManager->get(ReversalCommand::COMMAND_CODE);
            if (!$command instanceof CommandInterface) {
                $this->logger->critical(__("Cancel command should be provided."), []);
                throw new CommandException(__("Cancel command should be provided."));
            }
            $command->execute($commandSubject);
        } catch (CommandException $e) {
            $this->logger->critical(__($e->getMessage()), []);
            throw new CommandException(__($e->getMessage()));
        }
        $this->logger->debug("After execute cancel", $commandSubject);
    }

    /**
     * @param array $commandSubject
     * @param Order $order
     * @return void
     * @throws CommandException
     * @throws NotFoundException
     */
    private function executeTransactionCheckCommand(array $commandSubject, Order $order): void
    {
        $this->logger->debug("Before execute transaction check", $commandSubject);
        try {
            $command = $this->commandManager->get(TransactionCheckCommand::COMMAND_CODE);
            if (!$command instanceof CommandInterface) {
                $this->logger->critical(__("Transaction check command should be provided."), []);
                throw new CommandException(__("Transaction check command should be provided."));
            }
            $command->execute($commandSubject);
        } catch (CommandException $e) {
            // if the details of the transaction are not available or an error occurred during the request,
            // the customer will receive an error in the storefront, the order in the Magento will not be created,
            // but the transaction in the payment gateway already exist, so we need to cancel this payment
            $this->executeCancelCommand($commandSubject, $order);
            $this->logger->critical(__($e->getMessage()), []);
            throw new CommandException(__($e->getMessage()));
        }
        $this->logger->debug("After execute transaction check", $commandSubject);
    }

    /**
     * @param Order $order
     * @param Payment $payment
     * @param $stateObject
     * @param array $commandSubject
     * @return void
     * @throws CommandException
     * @throws LocalizedException
     * @throws NotFoundException
     */
    private function processDebit(Order $order, Payment $payment, $stateObject, array $commandSubject): void
    {
        $this->executeTransactionCheckCommand($commandSubject, $order);
        $this->processCapture($order, $payment, $stateObject, 'debit');
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
