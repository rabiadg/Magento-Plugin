<?php
/**
 * Copyright © Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Plugin;

use Closure;
use Magento\Checkout\Model\GuestPaymentInformationManagement as CheckoutGuestPaymentInformationManagement;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\GuestCartManagementInterface;
use Psr\Log\LoggerInterface;
use TotalProcessing\Opp\Gateway\Request\TransactionCheckDataBuilder;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;

/**
 * Class GuestPaymentInformationManagement
 * @TODO Change CommandException message with common message
 *
 * @package TotalProcessing\Opp\Plugin
 */
class GuestPaymentInformationManagement
{
    /**
     * @var CheckoutGuestPaymentInformationManagement
     */
    private $cartManagement;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MaskedQuoteIdToQuoteIdInterface
     */
    private $maskedQuoteIdToQuoteId;

    /**
     * @param GuestCartManagementInterface $cartManagement
     * @param LoggerInterface $logger
     * @param MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
     */
    public function __construct(
        GuestCartManagementInterface $cartManagement,
        LoggerInterface $logger,
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
    ) {
        $this->cartManagement = $cartManagement;
        $this->logger = $logger;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
    }

    /**
     * @param CheckoutGuestPaymentInformationManagement $subject
     * @param Closure $proceed
     * @param $cartId
     * @param $email
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @return int
     * @throws CouldNotSaveException
     */
    public function aroundSavePaymentInformationAndPlaceOrder(
        CheckoutGuestPaymentInformationManagement $subject,
        \Closure $proceed,
        $cartId,
        $email,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ) {
        $this->logger->debug("Save Payment", ["cartId" => $cartId]);
        $subject->savePaymentInformation($cartId, $email, $paymentMethod, $billingAddress);

        try {
            /**
             * Save cart ID for TransactionCheckDataBuilder for further processing (if needed).
             * In some cases (detected in Safari) at one of the processes during placing an order, for some reason(s),
             * the checkout session is interrupted, which causes the loss of the necessary information like merchantTransactionId
             * This causes a 'invalid or missing parameter' gateway error.
             * @see \TotalProcessing\Opp\Gateway\Request\PaymentDataBuilder::MERCHANT_TRANSACTION_ID
             */
            TransactionCheckDataBuilder::$cartId = $this->maskedQuoteIdToQuoteId->execute($cartId);
            $this->logger->debug("Place Order", ["cartId" => $cartId]);

            $orderId = $this->cartManagement->placeOrder($cartId);

            $this->logger->debug("orderId", ["orderId" => $orderId]);
            TransactionCheckDataBuilder::$cartId = null;
        } catch (CommandException $e) {
            $this->logger->critical($e->getMessage(), ["cartId" => $cartId]);
            throw new CouldNotSaveException(__($e->getMessage()), $e);
        } catch (LocalizedException $e) {
            $this->logger->critical($e->getMessage(), ["cartId" => $cartId]);
            throw new CouldNotSaveException(__($e->getMessage()), $e);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage(), ["cartId" => $cartId]);
            throw new CouldNotSaveException(
                __('An error occurred on the server. Please try to place the order again.'),
                $e
            );
        }

        return $orderId;
    }
}
