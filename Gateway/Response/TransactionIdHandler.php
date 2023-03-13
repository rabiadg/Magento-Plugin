<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Response;

use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;
use TotalProcessing\Opp\Gateway\SubjectReader;

/**
 * Class CommonHandler
 */
class TransactionIdHandler implements HandlerInterface
{
    const TRANSACTION_ID = 'id';
    const BUILD_NUMBER = 'buildNumber';

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * Constructor
     *
     * @param SubjectReader $subjectReader
     */
    public function __construct(SubjectReader $subjectReader)
    {
        $this->subjectReader = $subjectReader;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDataObject = $this->subjectReader->readPayment($handlingSubject);

        $payment = $paymentDataObject->getPayment();
        ContextHelper::assertOrderPayment($payment);

        $this->setTransactionId($payment, $response);

        $payment->setIsTransactionClosed($this->shouldCloseTransaction());
        $closed = $this->shouldCloseParentTransaction($payment);
        $payment->setShouldCloseParentTransaction($closed);
    }

    /**
     * @param Payment $payment
     * @param array $response
     * @return void
     */
    protected function setTransactionId(Payment $payment, array $response)
    {
        if (!$payment->getTransactionId()) {
            $transactionId = $this->subjectReader->readResponse($response, self::TRANSACTION_ID);
            $payment->setTransactionId($transactionId ?? '');
        }
    }

    /**
     * Whether transaction should be closed
     *
     * @return false
     */
    protected function shouldCloseTransaction()
    {
        return false;
    }

    /**
     * Whether parent transaction should be closed
     *
     * @param Payment $payment
     * @return false
     */
    protected function shouldCloseParentTransaction(Payment $payment)
    {
        return false;
    }
}
