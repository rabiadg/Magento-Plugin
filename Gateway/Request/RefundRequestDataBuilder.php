<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Request;

use Magento\Framework\HTTP\ZendClient;
use TotalProcessing\Opp\Gateway\Response\CommonHandler;

/**
 * Class RefundRequestDataBuilder
 * @package TotalProcessing\Opp\Gateway\Request
 */
class RefundRequestDataBuilder extends BaseRequestDataBuilder
{
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
        $id = $payment->getAdditionalInformation(CommonHandler::ID);

        $url = rtrim($this->config->getApiUrl($storeId), '/')
            . str_replace('{id}', $id, self::STATUS_PATH);

        $result = [
            self::REQUEST_DATA_NAMESPACE => [
                self::REQUEST_DATA_METHOD => ZendClient::POST,
                self::REQUEST_DATA_URL => $url,
                self::REQUEST_DATA_HEADERS => [
                    "Authorization" => "Bearer {$this->config->getAccessToken($storeId)}",
                ],
            ]
        ];

        $this->subjectReader->debug("Refund Request Data", $result);

        return $result;
    }
}
