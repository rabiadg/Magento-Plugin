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
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\GuestCartManagementInterface;
use Psr\Log\LoggerInterface;

/**
 * Class GuestPaymentInformationManagement
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
     * @throws \Exception
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
        } catch (CouldNotSaveException $e) {
            $this->logger->critical($e->getMessage(), ["cartId" => $cartId]);
            throw new CouldNotSaveException(__($e->getMessage()), $e);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage(), ["cartId" => $cartId]);
            throw new \Exception(__($e->getMessage()));
        }

        return $orderId;
    }
}
