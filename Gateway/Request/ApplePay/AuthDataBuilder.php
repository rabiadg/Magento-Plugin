<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Request\ApplePay;

use Magento\Framework\HTTP\ZendClient;
use TotalProcessing\Opp\Gateway\Request\BaseRequestDataBuilder as DataBuilder;

/**
 * Class AuthDataBuilder
 * @package TotalProcessing\Opp\Gateway\Request\ApplePay
 */
class AuthDataBuilder extends AbstractDataBuilder
{
    const ENTITY_ID = 'entityId';

    /**
     * {@inheritdoc}
     */
    public function build(array $buildSubject): array
    {
        $this->subjectReader->debug("buildSubject Data", $buildSubject);

        $paymentDataObject = $this->subjectReader->readPayment($buildSubject);

        $order = $paymentDataObject->getOrder();
        $storeId = $order->getStoreId();

        $result = [
            self::ENTITY_ID => $this->config->getEntityId($storeId),
            DataBuilder::REQUEST_DATA_NAMESPACE => [
                DataBuilder::REQUEST_DATA_METHOD => ZendClient::POST,
                DataBuilder::REQUEST_DATA_HEADERS => [
                    "Content-Type" => "application/x-www-form-urlencoded",
                ],
            ],
        ];

        $this->subjectReader->debug("Auth Data Builder", $result);

        return $result;
    }
}
