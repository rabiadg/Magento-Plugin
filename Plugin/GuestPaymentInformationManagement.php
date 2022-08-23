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

/**
 * Class GuestPaymentInformationManagement
 *
 * @TODO Change CommandException message with common message
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
     * Constructor
     *
     * @param GuestCartManagementInterface $cartManagement
     * @param LoggerInterface $logger
     */
    public function __construct(
        GuestCartManagementInterface $cartManagement,
        LoggerInterface $logger
    ) {
        $this->cartManagement = $cartManagement;
        $this->logger = $logger;
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
            $this->logger->debug("Place Order", ["cartId" => $cartId]);

            $orderId = $this->cartManagement->placeOrder($cartId);

            $this->logger->debug("orderId", ["orderId" => $orderId]);
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
