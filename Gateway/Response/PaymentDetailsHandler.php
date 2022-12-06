<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Response;

use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Payment\Gateway\Response\HandlerInterface;
use TotalProcessing\Opp\Gateway\SubjectReader;

/**
 * Class PaymentDetailsHandler
 * @package TotalProcessing\Opp\Gateway\Response
 */
class PaymentDetailsHandler implements HandlerInterface
{
    const BASIC_PAYMENT_TYPE = 'paymentType';
    const BASIC_PAYMENT_BRAND = 'paymentBrand';
    const AMOUNT = 'amount';
    const CURRENCY = 'currency';

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

        $basicPaymentType = $this->subjectReader->readResponse($response, self::BASIC_PAYMENT_TYPE);
        $basicPaymentBrad = $this->subjectReader->readResponse($response, self::BASIC_PAYMENT_BRAND);

        $payment = $paymentDataObject->getPayment();
        ContextHelper::assertOrderPayment($payment);

        $payment->setAdditionalInformation(self::BASIC_PAYMENT_TYPE, $basicPaymentType);
        $payment->setAdditionalInformation(self::BASIC_PAYMENT_BRAND, $basicPaymentBrad);
    }
}
