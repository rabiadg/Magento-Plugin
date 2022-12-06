<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Helper\Formatter;
use TotalProcessing\Opp\Gateway\Helper\QuoteHelper;
use TotalProcessing\Opp\Gateway\SubjectReader;
use TotalProcessing\Opp\Model\System\Config\PaymentType;
use TotalProcessing\Opp\Observer\DataAssignObserver;

/**
 * Class PaymentDataBuilder
 * @package TotalProcessing\Opp\Gateway\Request
 */
class PaymentDataBuilder implements BuilderInterface
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
     * Used to populate all or part of the Merchant Name descriptor
     * <br/>
     * <strong>OPTIONAL</strong>
     */
    const DESCRIPTOR = 'descriptor';

    /**
     * Merchant-provided invoice number
     * <br/>
     * <strong>OPTIONAL</strong>
     */
    const MERCHANT_INVOICE_ID = 'merchantInvoiceId';

    /**
     * Merchant-provided additional information
     * <br/>
     * <strong>OPTIONAL</strong>
     */
    const MERCHANT_MEMO = 'merchantMemo';

    /**
     * Merchant-provided reference number. Should be unique
     * <br/>
     * <strong>CONDITIONAL</strong>
     */
    const MERCHANT_TRANSACTION_ID = 'merchantTransactionId';

    /**
     * Overrides the payment type for specific brands
     * <br/>
     * <strong>OPTIONAL</strong>
     */
    const OVERRIDE_PAYMENT_TYPE = 'overridePaymentType';

    /**
     * The payment brand specifies the method of the payment request.
     * <br/>
     * <strong>CONDITIONAL</strong>
     */
    const PAYMENT_BRAND = 'paymentBrand';

    /**
     * The payment type for the request
     * <br/>
     * <strong>REQUIRED</strong>
     */
    const PAYMENT_TYPE = 'paymentType';

    /**
     * The tax amount of the payment request
     * <br/>
     * <strong>OPTIONAL</strong>
     */
    const TAX_AMOUNT = 'taxAmount';

    /**
     * The category of the transaction
     * <br/>
     * <strong>OPTIONAL</strong>
     */
    const TRANSACTION_CATEGORY = 'transactionCategory';

    /**
     * The payment method nonce
     * <br/>
     * <strong>OPTIONAL</strong>
     */
    const PAYMENT_METHOD_NONCE = 'paymentMethodNonce';

    /**
     * @var QuoteHelper
     */
    private $quoteHelper;

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @param QuoteHelper $quoteHelper
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        QuoteHelper $quoteHelper,
        SubjectReader $subjectReader
    ) {
        $this->quoteHelper = $quoteHelper;
        $this->subjectReader = $subjectReader;
    }

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject): array
    {
        $this->subjectReader->debug("buildSubject data", $buildSubject);
        $paymentDataObject = $this->subjectReader->readPayment($buildSubject);

        $payment = $paymentDataObject->getPayment();
        $order = $paymentDataObject->getOrder();
        $quote = $this->quoteHelper->getQuote($order, $payment);

        $params = [
            self::AMOUNT => $this->formatPrice($this->subjectReader->readAmount($buildSubject)),
            self::CURRENCY => $order->getCurrencyCode(),
            self::DESCRIPTOR => null,
            self::MERCHANT_INVOICE_ID => null,
            self::MERCHANT_MEMO => null,
            self::MERCHANT_TRANSACTION_ID => $quote->getOppMerchantTransactionId(),
            self::PAYMENT_BRAND => null,
            self::PAYMENT_TYPE => PaymentType::PRE_AUTHORIZATION,
            self::TAX_AMOUNT => null,
            self::TRANSACTION_CATEGORY => null,
            self::PAYMENT_METHOD_NONCE => $payment->getAdditionalInformation(
                DataAssignObserver::PAYMENT_METHOD_NONCE
            ),
        ];

        $this->subjectReader->debug("Result", $params);
        return array_filter($params, function ($param) {
            return $param !== null;
        });
    }
}
