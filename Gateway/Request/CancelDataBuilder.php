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
use Magento\Payment\Helper\Formatter;
use TotalProcessing\Opp\Gateway\Config\Config;
use TotalProcessing\Opp\Gateway\Helper\Command as CommandHelper;
use TotalProcessing\Opp\Gateway\Helper\PaymentTokenProvider;
use TotalProcessing\Opp\Gateway\Response\CommonHandler;
use TotalProcessing\Opp\Gateway\SubjectReader;
use TotalProcessing\Opp\Model\System\Config\PaymentType;
use TotalProcessing\Opp\Gateway\Helper\MerchantTransactionIdProvider;
use TotalProcessing\Opp\Gateway\Helper\MerchantTransactionIdProviderFactory;

/**
 * Class CancelDataBuilder
 * @package TotalProcessing\Opp\Gateway\Request
 */
class CancelDataBuilder extends BaseRequestDataBuilder
{
    use Formatter;

    /**
     * The entity required to authorize the request
     * <br/>
     * <strong>CONDITIONAL</strong>
     */
    const ENTITY_ID = 'entityId';

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
     * The payment brand for the request
     * <br/>
     * <strong>REQUIRED</strong>
     */
    const PAYMENT_BRAND = 'paymentBrand';

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var CommandHelper
     */
    private $commandHelper;

    /**
     * @var PaymentTokenProvider
     */
    private $paymentTokenProvider;

    /**
     * @var MerchantTransactionIdProviderFactory
     */
    private $merchantTransactionIdProviderFactory;

    /**
     * @param CheckoutSession $checkoutSession
     * @param CommandHelper $commandHelper
     * @param Config $config
     * @param ResourceInterface $moduleResource
     * @param ProductMetadataInterface $productMetadata
     * @param SubjectReader $subjectReader
     * @param PaymentTokenProvider $paymentTokenProvider
     * @param MerchantTransactionIdProviderFactory $merchantTransactionIdProviderFactory
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        CommandHelper $commandHelper,
        Config $config,
        ResourceInterface $moduleResource,
        ProductMetadataInterface $productMetadata,
        SubjectReader $subjectReader,
        PaymentTokenProvider $paymentTokenProvider,
        MerchantTransactionIdProviderFactory $merchantTransactionIdProviderFactory
    ) {
        parent::__construct($config, $moduleResource, $productMetadata, $subjectReader);
        $this->checkoutSession = $checkoutSession;
        $this->commandHelper = $commandHelper;
        $this->paymentTokenProvider = $paymentTokenProvider;
        $this->merchantTransactionIdProviderFactory = $merchantTransactionIdProviderFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function build(array $buildSubject): array
    {
        $this->subjectReader->debug("CANCEL buildSubject data", $buildSubject);

        $paymentDataObject = $this->subjectReader->readPayment($buildSubject);

        $order = $paymentDataObject->getOrder();
        $payment = $paymentDataObject->getPayment();
        $storeId = $order->getStoreId();

        $result = [
            self::ENTITY_ID => $this->config->getEntityId($storeId),
            self::AMOUNT => $this->subjectReader->readAmount($buildSubject),
            self::CURRENCY => $this->subjectReader->readCurrency($buildSubject),
            self::PAYMENT_TYPE => PaymentType::REFUND
        ];

        $this->subjectReader->debug("CANCEL request data", $result);

        return $result;
    }
}
