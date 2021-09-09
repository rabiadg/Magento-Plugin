<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Request;

use TotalProcessing\Opp\Gateway\SubjectReader;
use TotalProcessing\Opp\Model\System\Config\PaymentType;

/**
 * Class ReversalDataBuilder
 */
class ReversalDataBuilder extends BaseRequestDataBuilder
{
    /**
     * The payment type for the request
     * <br/>
     * <strong>REQUIRED</strong>
     */
    const PAYMENT_TYPE = 'paymentType';

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject): array
    {
        $this->subjectReader->debug("buildSubject data", $buildSubject);

        $paymentDataObject = $this->subjectReader->readPayment($buildSubject);

        $order = $paymentDataObject->getOrder();
        $payment = $paymentDataObject->getPayment();

        $quoteId = $payment->getAdditionalInformation("customParameters_" . CustomParameterDataBuilder::QUOTE_ID);
        $returnUrl = $payment->getAdditionalInformation("customParameters_" . CustomParameterDataBuilder::RETURN_URL);

        $params = [
            self::PAYMENT_TYPE => PaymentType::REVERSAL,
            PaymentDataBuilder::MERCHANT_TRANSACTION_ID => $order->getOrderIncrementId(),
            "customParameters[" . CustomParameterDataBuilder::ORDER_ID . "]" => $order->getId(),
            "customParameters[" . CustomParameterDataBuilder::ORDER_INCREMENT_ID . "]" => $order->getOrderIncrementId(),
            "customParameters[" . CustomParameterDataBuilder::PLUGIN . "]" => $this->getVersion(),
            "customParameters[" . CustomParameterDataBuilder::QUOTE_ID . "]" => $quoteId,
            "customParameters[" . CustomParameterDataBuilder::RETURN_URL . "]" => $returnUrl,
        ];

        $this->subjectReader->debug("Reversal Data", $params);

        return $params;
    }
}
