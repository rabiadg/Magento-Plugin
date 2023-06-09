<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Request;

use Magento\Payment\Helper\Formatter;
use TotalProcessing\Opp\Model\System\Config\PaymentType;

/**
 * Class RefundDataBuilder
 */
class RefundDataBuilder extends BaseRequestDataBuilder
{
    use Formatter;

    /**
     * The amount of the payment request.
     * <br/>
     * <strong>REQUIRED</strong>
     * <br/>
     * <br/>
     * The amount is the only amount value which is relevant. All other amount declarations like taxAmount or
     * shipping.cost are already included
     */
    const AMOUNT = 'amount';

    /**
     * The currency code of the payment amount request
     * <br>
     * <strong>REQUIRED</strong>
     */
    const CURRENCY = 'currency';

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

        $quoteId = $payment->getAdditionalInformation("customParameters_" . CustomParameterDataBuilder::QUOTE_ID);;
        $returnUrl = $payment->getAdditionalInformation("customParameters_" . CustomParameterDataBuilder::RETURN_URL);

        $params = [
            self::AMOUNT => $this->formatPrice($this->subjectReader->readAmount($buildSubject)),
            self::CURRENCY => $order->getCurrencyCode(),
            self::PAYMENT_TYPE => PaymentType::REFUND,
            PaymentDataBuilder::MERCHANT_TRANSACTION_ID =>
                $payment->getAdditionalInformation(PaymentDataBuilder::MERCHANT_TRANSACTION_ID),
            "customParameters[" . CustomParameterDataBuilder::ORDER_ID . "]" => $order->getId(),
            "customParameters[" . CustomParameterDataBuilder::ORDER_INCREMENT_ID . "]" => $order->getOrderIncrementId(),
            "customParameters[" . CustomParameterDataBuilder::PLUGIN . "]" => $this->getVersion(),
            "customParameters[" . CustomParameterDataBuilder::QUOTE_ID . "]" => $quoteId,
            "customParameters[" . CustomParameterDataBuilder::RETURN_URL . "]" => $returnUrl,
        ];

        $this->subjectReader->debug("Refund data", $params);

        return $params;
    }
}
