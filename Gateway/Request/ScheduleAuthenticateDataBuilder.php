<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Request;

use Magento\Framework\HTTP\ZendClient;

/**
 * Class ScheduleAuthenticateDataBuilder
 * @package TotalProcessing\Opp\Gateway\Request
 */
class ScheduleAuthenticateDataBuilder extends BaseRequestDataBuilder
{
    /**
     * @inheritdoc
     */
    public function build(array $buildSubject): array
    {
        $this->subjectReader->debug("Schedule Authenticate buildSubject data", $buildSubject);

        $paymentDataObject = $this->subjectReader->readPayment($buildSubject);

        $order = $paymentDataObject->getOrder();
        $storeId = $order->getStoreId();

        $params = [
            self::REQUEST_DATA_NAMESPACE => [
                self::REQUEST_DATA_METHOD => ZendClient::POST,
                self::REQUEST_DATA_URL => rtrim($this->config->getScheduleApiUrl($storeId)),
                self::REQUEST_DATA_HEADERS => [
                    "Authorization" => "Bearer {$this->config->getScheduleAccessToken($storeId)}",
                ],
            ]
        ];

        $this->subjectReader->debug("Schedule Authenticate Data", $params);

        return $params;
    }
}
