<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Request;

use Magento\Framework\HTTP\ZendClient;
use Magento\Payment\Helper\Formatter;
use TotalProcessing\Opp\Gateway\Response\CommonHandler;
use TotalProcessing\Opp\Model\System\Config\PaymentType;

/**
 * Class CaptureRequestDataBuilder
 */
class CaptureRequestDataBuilder extends BaseRequestDataBuilder
{
    use Formatter;

    const STATUS_PATH = '/v1/payments/{id}';

    /**
     * {@inheritdoc}
     */
    public function build(array $buildSubject): array
    {
        $this->subjectReader->debug("buildSubject Data", $buildSubject);

        $paymentDataObject = $this->subjectReader->readPayment($buildSubject);

        $order = $paymentDataObject->getOrder();
        $payment = $paymentDataObject->getPayment();

        $storeId = $order->getStoreId();
        $quoteId = $payment->getAdditionalInformation("customParameters_" . CustomParameterDataBuilder::QUOTE_ID);
        $returnUrl = $payment->getAdditionalInformation("customParameters_" . CustomParameterDataBuilder::RETURN_URL);
        $id = $payment->getAdditionalInformation(CommonHandler::ID);

        $url = rtrim($this->config->getApiUrl($storeId), '/')
            . str_replace('{id}', $id, self::STATUS_PATH);

        $result = [
            PaymentDataBuilder::AMOUNT => $this->formatPrice($this->subjectReader->readAmount($buildSubject)),
            PaymentDataBuilder::CURRENCY => $order->getCurrencyCode(),
            PaymentDataBuilder::PAYMENT_TYPE => PaymentType::CAPTURE,
            PaymentDataBuilder::MERCHANT_TRANSACTION_ID => $payment->getAdditionalInformation(PaymentDataBuilder::MERCHANT_TRANSACTION_ID),
            self::REQUEST_DATA_NAMESPACE => [
                self::REQUEST_DATA_METHOD => ZendClient::POST,
                self::REQUEST_DATA_URL => $url,
                self::REQUEST_DATA_HEADERS => [
                    "Authorization" => "Bearer {$this->config->getAccessToken($storeId)}",
                ],
            ],
            "customParameters[" . CustomParameterDataBuilder::ORDER_ID . "]" => $order->getId(),
            "customParameters[" . CustomParameterDataBuilder::ORDER_INCREMENT_ID . "]" => $order->getOrderIncrementId(),
            "customParameters[" . CustomParameterDataBuilder::PLUGIN . "]" => $this->getVersion(),
            "customParameters[" . CustomParameterDataBuilder::QUOTE_ID . "]" => $quoteId,
            "customParameters[" . CustomParameterDataBuilder::RETURN_URL . "]" => $returnUrl
        ];

        $this->subjectReader->debug("Capture Request Data", $result);

        return $result;
    }
}
