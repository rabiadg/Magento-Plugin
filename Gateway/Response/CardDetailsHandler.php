<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use TotalProcessing\Opp\Gateway\SubjectReader;

/**
 * Class CardDetailsHandler
 * @package TotalProcessing\Opp\Gateway\Response
 */
class CardDetailsHandler implements HandlerInterface
{
    const CARD_NAMESPACE = 'card';
    const CARD_BIN = 'bin';
    const CARD_EXP_MONTH = 'expiryMonth';
    const CARD_EXP_YEAR = 'expiryYear';
    const CARD_HOLDER = 'holder';
    const CARD_LAST4_DIGITS = 'last4Digits';
    const CARD_NUMBER = 'cc_number';

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

        $data = $this->subjectReader->readResponse($response);
        $creditCard = $this->subjectReader->readResponse($response, self::CARD_NAMESPACE);

        if ($creditCard) {
            $payment->setCcLast4($creditCard[self::CARD_LAST4_DIGITS]);
            $payment->setCcExpMonth($creditCard[self::CARD_EXP_MONTH]);
            $payment->setCcExpYear($creditCard[self::CARD_EXP_YEAR]);

            $ccType = $this->subjectReader->readResponse($response, PaymentDetailsHandler::BASIC_PAYMENT_TYPE);
            $payment->setCcType($ccType);

            $payment->setAdditionalInformation(self::CARD_NUMBER, 'xxxx-' . $creditCard[self::CARD_LAST4_DIGITS]);
            $payment->setAdditionalInformation(
                OrderPaymentInterface::CC_TYPE,
                $data[PaymentDetailsHandler::BASIC_PAYMENT_TYPE]
            );
        }
    }
}
