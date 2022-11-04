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
use TotalProcessing\Opp\Gateway\SubjectReader;
use TotalProcessing\Opp\Model\System\Config\PaymentType;

/**
 * Class DebitDataBuilder
 * @package TotalProcessing\Opp\Gateway\Request
 */
class DebitDataBuilder extends BaseRequestDataBuilder
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
     * <strong>OPTIONAL</strong>
     */
    const PAYMENT_BRAND = 'paymentBrand';

    /**
     * The identifier of the registration request
     * <br/>
     * <strong>REQUIRED</strong>
     */
    const REGISTRATIONS_ID = 'id';

    /**
     * Stored (registered) cards namespace
     * <br/>
     * <strong>OPTIONAL</strong>
     */
    const REGISTRATIONS_NAMESPACE = 'registrations';

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
     * @param CheckoutSession $checkoutSession
     * @param CommandHelper $commandHelper
     * @param Config $config
     * @param ResourceInterface $moduleResource
     * @param ProductMetadataInterface $productMetadata
     * @param SubjectReader $subjectReader
     * @param PaymentTokenProvider $paymentTokenProvider
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        CommandHelper $commandHelper,
        Config $config,
        ResourceInterface $moduleResource,
        ProductMetadataInterface $productMetadata,
        SubjectReader $subjectReader,
        PaymentTokenProvider $paymentTokenProvider
    ) {
        parent::__construct($config, $moduleResource, $productMetadata, $subjectReader);
        $this->checkoutSession = $checkoutSession;
        $this->commandHelper = $commandHelper;
        $this->paymentTokenProvider = $paymentTokenProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function build(array $buildSubject): array
    {
        $this->subjectReader->debug("DEBIT buildSubject data", $buildSubject);

        $quote = $this->checkoutSession->getQuote();
        $storeId = $quote->getStoreId();
        $quoteId = $quote->getId();

        $version = "Magento v.{$this->productMetadata->getVersion()} "
            . " / Module TotalProcessing OPP v."
            . $this->moduleResource->getDataVersion("TotalProcessing_Opp");

        $result = [
            self::ENTITY_ID => $this->config->getEntityId($storeId),
            self::AMOUNT => $this->formatPrice($this->subjectReader->readAmount($buildSubject)),
            self::CURRENCY => $this->subjectReader->readCurrency($buildSubject),
            self::PAYMENT_TYPE => PaymentType::DEBIT,
            PaymentDataBuilder::MERCHANT_TRANSACTION_ID => $quote->getOppMerchantTransactionId(),
            "customParameters[" . CustomParameterDataBuilder::PLUGIN . "]" => $version,
            "customParameters[" . CustomParameterDataBuilder::QUOTE_ID . "]" => $quoteId,
            "customParameters[" . CustomParameterDataBuilder::RETURN_URL . "]" => $this->config->getSource(),
        ];

        $billingAddress = $quote->getBillingAddress();
        if ($customerName = trim($billingAddress->getName())) {
            $result[CardDataBuilder::CARD_HOLDER] = $customerName;
        }

        if (!$this->commandHelper->isSchedulerActive()) {
            $i = 0;
            foreach ($this->paymentTokenProvider->getFilteredTokens() as $token) {
                $result[self::REGISTRATIONS_NAMESPACE . "[$i]." . self::REGISTRATIONS_ID] = $token->getGatewayToken();
                $i++;
            }
        }

        $this->subjectReader->debug("DEBIT request data", $result);

        return $result;
    }
}
