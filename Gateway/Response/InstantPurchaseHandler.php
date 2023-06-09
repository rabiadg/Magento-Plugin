<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Response;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Model\InfoInterface;
use TotalProcessing\Opp\Gateway\Request\CustomParameterDataBuilder;
use TotalProcessing\Opp\Gateway\SubjectReader;
use TotalProcessing\Opp\Gateway\Helper\PaymentTokenProvider;

/**
 * Class InstantPurchaseHandler
 * @package TotalProcessing\Opp\Gateway\Response
 */
class InstantPurchaseHandler implements HandlerInterface
{
    const AMOUNT = 'amount';
    const CURRENCY = 'currency';
    const PAYMENT_TYPE = 'paymentType';
    const CARD_LAST4_DIGITS = 'maskedCC';
    const EXPIRATION_DATE = 'expirationDate';
    const BRAND_TYPE = 'type';
    const CUSTOM_PARAM_PUBLIC_HASH = 'SHOPPER_card_public_hash';

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var PaymentTokenProvider
     */
    private $paymentTokenProvider;

    /**
     * @param CheckoutSession $checkoutSession
     * @param SubjectReader $subjectReader
     * @param PaymentTokenProvider $paymentTokenProvider
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        SubjectReader $subjectReader,
        PaymentTokenProvider $paymentTokenProvider
    ) {
        $this->subjectReader = $subjectReader;
        $this->checkoutSession = $checkoutSession;
        $this->paymentTokenProvider = $paymentTokenProvider;
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     * @throws \Exception
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDataObject = $this->subjectReader->readPayment($handlingSubject);
        $customParameters = $this->subjectReader->readResponse(
            $response, CustomParametersHandler::CUSTOM_PARAMETERS_NAMESPACE
            ) ?? [];
        $publicHash = $customParameters[CustomParameterDataBuilder::PUBLIC_HASH] ?? '';

        if (!$publicHash) {
            $msg = "Public hash is missing, cant token details.";
            $this->subjectReader->critical($msg, ["customParameters" => $customParameters]);
            throw new \Exception($msg);
        }

        $this->subjectReader->debug("Public Hash", [$publicHash]);

        $payment = $paymentDataObject->getPayment();
        ContextHelper::assertOrderPayment($payment);

        $payment->setAdditionalInformation(
            self::AMOUNT,
            $this->subjectReader->readResponse($response, self::AMOUNT)
        );

        $payment->setAdditionalInformation(
            self::CURRENCY,
            $this->subjectReader->readResponse($response, self::CURRENCY)
        );

        $this->setCardDetails($payment, $publicHash);
    }

    /**
     * @param InfoInterface $payment
     * @param string $publicHash
     * @return void
     */
    public function setCardDetails(InfoInterface $payment, string $publicHash)
    {
        $tokens = $this->paymentTokenProvider->getFilteredTokens();
        foreach ($tokens as $token) {
            if ($token->getPublicHash() === $publicHash) {
                $creditCard = json_decode($token->getTokenDetails(), true);

                $this->subjectReader->debug("CARD DETAILS", $creditCard);
                $payment->setCcLast4($creditCard[self::CARD_LAST4_DIGITS]);
                $expirationDate = explode("/", $creditCard[self::EXPIRATION_DATE]);
                $payment->setCcExpMonth(trim($expirationDate[0]));
                $payment->setCcExpYear(trim($expirationDate[1]));
                $payment->setAdditionalInformation(
                    PaymentDetailsHandler::BASIC_PAYMENT_BRAND,
                    $creditCard[self::BRAND_TYPE]
                );
                $payment->setAdditionalInformation(
                    CardDetailsHandler::CARD_NUMBER,
                    'xxxx-' . $creditCard[self::CARD_LAST4_DIGITS]
                );
                break;
            }
        }
    }
}
