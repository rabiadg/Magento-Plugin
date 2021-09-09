<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Request;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ResourceInterface;
use Magento\Framework\Serialize\Serializer\Json as Serializer;
use Magento\Payment\Helper\Formatter;
use TotalProcessing\Opp\Gateway\Config\Config;
use TotalProcessing\Opp\Gateway\Helper\PaymentTokenProvider;
use TotalProcessing\Opp\Gateway\SubjectReader;
use TotalProcessing\Opp\Model\System\Config\RecurringType;

/**
 * Class PreAuthorizeSchedulerDataBuilder
 */
class PreAuthorizeSchedulerDataBuilder extends BaseRequestDataBuilder
{
    use Formatter;

    const AMOUNT = 'amt';
    const CREATE_REGISTRATION = "createRegistration";
    const NAME = 'nme';
    const QUANTITY = 'qty';
    const RECURRING_TYPE = 'recurringType';
    const SKU = 'sku';

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var PaymentTokenProvider
     */
    protected $paymentTokenProvider;

    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * PreAuthorizeSchedulerDataBuilder constructor.
     *
     * @param CheckoutSession $checkoutSession
     * @param Config $config
     * @param ResourceInterface $moduleResource
     * @param ProductMetadataInterface $productMetadata
     * @param SubjectReader $subjectReader
     * @param PaymentTokenProvider $paymentTokenProvider
     * @param Serializer $serializer
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        Config $config,
        ResourceInterface $moduleResource,
        ProductMetadataInterface $productMetadata,
        SubjectReader $subjectReader,
        PaymentTokenProvider $paymentTokenProvider,
        Serializer $serializer
    ) {
        parent::__construct($config, $moduleResource, $productMetadata, $subjectReader);
        $this->checkoutSession = $checkoutSession;
        $this->paymentTokenProvider = $paymentTokenProvider;
        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     */
    public function build(array $buildSubject): array
    {
        $this->subjectReader->debug("Pre-Authorize Scheduler buildSubject Data", $buildSubject);

        $storeId = $this->checkoutSession->getQuote()->getStoreId();

        $result = [];

        if (!$this->config->isSchedulerActive($storeId)) {
            return $result;
        }

        $scheduleSkuList = $this->config->getScheduleSkus($storeId);
        $orderItems = $this->checkoutSession->getQuote()->getAllVisibleItems();

        $items = [];
        foreach ($orderItems as $item) {
            if (in_array($item->getSku(), $scheduleSkuList)) {
                $items[] = [
                    self::NAME => (string) $item->getName(),
                    self::SKU => (string) $item->getSku(),
                    self::QUANTITY => (int) $item->getQty(),
                    self::AMOUNT => (float) $this->formatPrice(
                        $item->getPriceInclTax() - $item->getDiscountAmount() / $item->getQty()
                    ),
                ];
            }
        }

        if (!$items) {
            return $result;
        }

        $result['customParameters[' . CustomParameterDataBuilder::TP_JSON . ']'] = $this->serializer->serialize($items);
        $result[self::CREATE_REGISTRATION] = true;
        $result[self::RECURRING_TYPE] = RecurringType::INITIAL;

        $this->subjectReader->debug("Pre-Authorize Scheduler Result: ", $result);

        return $result;
    }
}
