<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use TotalProcessing\Opp\Gateway\SubjectReader;
use TotalProcessing\Opp\Model\ResourceModel\Quote as ResourceQuote;
use TotalProcessing\Opp\Gateway\Request\CustomParameterDataBuilder;

/**
 * Class PaymentStatusCommonHandler
 * @package TotalProcessing\Opp\Gateway\Response
 */
class PaymentStatusCommonHandler implements HandlerInterface
{
    /**
     * The identifier of the payment request that can be used to reference the payment later.
     * You get this as the field id of a payment's response and then can use it as referencedPaymentId
     * in the backoffice tutorial or as the {id} part of the URL for sending referencing requests.
     */
    const PAYMENT_ID = 'id';

    /**
     * The payment type of the request.
     */
    const PAYMENT_TYPE = 'paymentType';

    /**
     * The payment brand of the request.
     */
    const PAYMENT_BRAND = 'paymentBrand';

    /**
     * The amount of the request.
     */
    const AMOUNT = 'amount';

    /**
     * The currency of the request.
     */
    const CURRENCY = 'currency';

    /**
     * Custom parameters of the request.
     */
    const CUSTOM_PARAMETERS = 'customParameters';

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var ResourceQuote
     */
    private $resourceQuote;

    /**
     * @param CheckoutSession $checkoutSession
     * @param SubjectReader $subjectReader
     * @param ResourceQuote $resourceQuote
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        SubjectReader $subjectReader,
        ResourceQuote $resourceQuote
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->subjectReader = $subjectReader;
        $this->resourceQuote = $resourceQuote;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(array $handlingSubject, array $response)
    {
        if ($paymentId = $this->subjectReader->readResponse($response, self::PAYMENT_ID)) {
            $quoteId = $this->checkoutSession->getQuote()->getId();
            if (!$quoteId) {
                $customParameters = $this->subjectReader->readResponse($response, self::CUSTOM_PARAMETERS);
                if (!empty($customParameters)) {
                    $quoteId = $this->subjectReader->readResponse(
                        $customParameters,
                        CustomParameterDataBuilder::QUOTE_ID
                    );
                }
            }
            $this->resourceQuote->updatePaymentId((string)$paymentId, (int)$quoteId);
        }
    }
}
