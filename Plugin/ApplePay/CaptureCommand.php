<?php
/**
 * Copyright © Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Plugin\ApplePay;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Payment\Model\MethodInterface;
use Psr\Log\LoggerInterface;
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
     * CaptureCommand constructor.
     *
     * @param CommandManagerInterface $commandManager
     * @param LoggerInterface $logger
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        CommandManagerInterface $commandManager,
        LoggerInterface $logger,
        SubjectReader $subjectReader
    ) {
        $this->commandManager = $commandManager;
        $this->logger = $logger;
        $this->subjectReader = $subjectReader;
    }

    public function beforeExecute(
        \TotalProcessing\Opp\Gateway\Command\ApplePay\CaptureCommand $subject,
        array $commandSubject
    ) {
        $this->logger->debug("Before execute capture start", $commandSubject);

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

        $this->logger->debug("Before execute capture end");

        return [$commandSubject];
    }
}
