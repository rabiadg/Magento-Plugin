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
use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Payment\Model\MethodInterface;
use Psr\Log\LoggerInterface;
use TotalProcessing\Opp\Gateway\Response\TransactionCheckHandler;
use TotalProcessing\Opp\Gateway\Response\TransactionIdHandler;
use TotalProcessing\Opp\Gateway\SubjectReader;

/**
 * Class CaptureCommand
 *
 * @TODO Change CommandException message with common message
 */
class CaptureCommand
{
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
     * @var TransactionIdHandler
     */
    private $transactionHandler;

    /**
     * CaptureCommand constructor.
     *
     * @param CommandManagerInterface $commandManager
     * @param LoggerInterface $logger
     * @param SubjectReader $subjectReader
     * @param TransactionIdHandler $transactionHandler
     */
    public function __construct(
        CommandManagerInterface $commandManager,
        LoggerInterface $logger,
        SubjectReader $subjectReader,
        TransactionIdHandler $transactionHandler
    ) {
        $this->commandManager = $commandManager;
        $this->logger = $logger;
        $this->subjectReader = $subjectReader;
        $this->transactionHandler = $transactionHandler;
    }

    /**
     * @param \TotalProcessing\Opp\Gateway\Command\CaptureCommand $subject
     * @param array $commandSubject
     * @param \Closure $proceed
     * @return array[]
     * @throws CouldNotSaveException
     */
    public function aroundExecute(
        \TotalProcessing\Opp\Gateway\Command\CaptureCommand $subject,
        \Closure $proceed,
        array $commandSubject
    ) {
        $this->logger->debug("Around execute capture start", $commandSubject);

        $result = null;

        try {
            $paymentDataObject = $this->subjectReader->readPayment($commandSubject);
            $payment = $paymentDataObject->getPayment();
            ContextHelper::assertOrderPayment($payment);

            $id = $payment->getParentTransactionId() ?: $payment->getTransactionid();

            if (!$id) {
                $command = $this->commandManager->get(MethodInterface::ACTION_AUTHORIZE);
                if (!$command instanceof CommandInterface) {
                    $this->logger->critical(__("Authorize command not found"), []);
                    throw new CommandException(__("Authorize command not found"));
                }

                $command->execute($commandSubject);
            }

            if (!$this->isPaid($commandSubject)) {
                $result = $proceed($commandSubject);
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

        $this->logger->debug("Around execute capture end");

        return $result;
    }

    /**
     * Returns if order payment already captured
     *
     * @param array $commandSubject
     * @return bool
     * @throws \Throwable
     */
    private function isPaid(array $commandSubject): bool
    {
        try {
            $paymentDataObject = $this->subjectReader->readPayment($commandSubject);
            $payment = $paymentDataObject->getPayment();

            $captureData = $payment->getAdditionalInformation(TransactionCheckHandler::IS_CAPTURED);

            if ($captureData) {
                $this->transactionHandler->handle($commandSubject, $captureData);
                return true;
            }
        } catch (\Throwable $t) {
            throw $t;
        }

        return false;
    }
}
