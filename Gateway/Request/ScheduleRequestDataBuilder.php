<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Request;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ResourceInterface;
use Magento\Framework\Serialize\Serializer\Json as Serializer;
use Magento\Payment\Helper\Formatter;
use TotalProcessing\Opp\Gateway\Config\Config;
use TotalProcessing\Opp\Gateway\Response\CommonHandler;
use TotalProcessing\Opp\Gateway\SubjectReader;
use TotalProcessing\Opp\Model\System\Config\ScheduleType;

/**
 * Class ScheduleRequestDataBuilder
 */
class ScheduleRequestDataBuilder extends BaseRequestDataBuilder
{
    use Formatter;

    const ADDRESS1 = 'address1';
    const AMOUNT = 'amount';
    const CITY = 'city';
    const COLLECTION_DAY = 'collectionDay';
    const CURRENCY = 'currency';
    const EMAIL = 'email';
    const FIRST_NAME = 'firstName';
    const MERCHANT_UUID = 'merchantUuid';
    const MOBILE = 'mobile';
    const PAYMENT_FREQUENCY = 'paymentFrequency';
    const POST_CODE = 'postcode';
    const REGISTRATION = 'registration';
    const START_DATE = 'startDate';
    const SURNAME = 'surname';

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * ScheduleRequestDataBuilder constructor.
     *
     * @param Config $config
     * @param ResourceInterface $moduleResource
     * @param ProductMetadataInterface $productMetadata
     * @param Serializer $serializer
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        Config $config,
        ResourceInterface $moduleResource,
        ProductMetadataInterface $productMetadata,
        Serializer $serializer,
        SubjectReader $subjectReader
    ) {
        parent::__construct($config, $moduleResource, $productMetadata, $subjectReader);
        $this->serializer = $serializer;
    }

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject): array
    {
        $this->subjectReader->debug("Schedule buildSubject data", $buildSubject);

        $paymentDataObject = $this->subjectReader->readPayment($buildSubject);
        $scheduleAction = $this->subjectReader->readScheduleAction($buildSubject);

        $order = $paymentDataObject->getOrder();
        $billingAddress = $order->getBillingAddress();
        $payment = $paymentDataObject->getPayment();

        $storeId = $order->getStoreId();

        $registration = $this->serializer->unserialize($payment->getAdditionalInformation(CommonHandler::RESPONSE));

        $registration["merchantTransactionId"] = $order->getOrderIncrementId();
        $registration['customer'] = [
            "givenName" => $billingAddress->getFirstname(),
            "surname" =>   $billingAddress->getLastname(),
            "merchantCustomerId" => $order->getCustomerId() ?: $registration['id'] ?? null,
            "mobile" => $billingAddress->getTelephone(),
            "email" => $billingAddress->getEmail(),
        ];

        $params = [
            self::MERCHANT_UUID => $this->config->getSenderId($storeId),
            self::REGISTRATION => $this->serializer->serialize($registration),
            self::FIRST_NAME => $billingAddress->getFirstname(),
            self::SURNAME => $billingAddress->getLastname(),
            self::MOBILE => $billingAddress->getTelephone(),
            self::EMAIL => $billingAddress->getEmail(),
            self::ADDRESS1 => $billingAddress->getStreetLine1(),
            self::POST_CODE => $billingAddress->getPostcode(),
            self::CITY => $billingAddress->getCity(),
            self::AMOUNT => $this->formatPrice($scheduleAction[ScheduleType::ACTION_AMOUNT]),
            self::CURRENCY => $order->getCurrencyCode(),
            self::PAYMENT_FREQUENCY => $scheduleAction[ScheduleType::ACTION_TYPE],
            self::START_DATE => $scheduleAction[ScheduleType::ACTION_START_DATE],
            self::COLLECTION_DAY => $scheduleAction[ScheduleType::ACTION_COLLECTION_DAY],
        ];

        $this->subjectReader->debug("Schedule Data", $params);

        return $params;
    }
}
