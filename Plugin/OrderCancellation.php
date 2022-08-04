<?php
/**
 * Copyright © Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Plugin;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;
use TotalProcessing\Opp\Model\Ui\ApplePay\ConfigProvider as ApplePayConfigProvider;
use TotalProcessing\Opp\Model\Ui\ConfigProvider as DefaultConfigProvider;
use Magento\Checkout\Model\Session as CheckoutSession;

/**
 * Class OrderCancellation
 */
class OrderCancellation
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @param LoggerInterface $logger
     * @param OrderRepositoryInterface $orderRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        LoggerInterface $logger,
        OrderRepositoryInterface $orderRepository,
        CartRepositoryInterface $quoteRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CheckoutSession $checkoutSession
    ) {
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->quoteRepository = $quoteRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Cancels an order if an exception occurs during the order creation.
     *
     * @param CartManagementInterface $subject
     * @param \Closure $proceed
     * @param $cartId
     * @param PaymentInterface $payment
     * @return int
     * @throws \Exception
     */
    public function aroundPlaceOrder(
        CartManagementInterface $subject,
        \Closure $proceed,
        $cartId,
        PaymentInterface $payment = null
    ) {
        try {
            return $proceed($cartId, $payment);
        } catch (\Exception $e) {
            $quote = $this->quoteRepository->get((int) $cartId);
            $payment = $quote->getPayment();
            $paymentCodes = [
                DefaultConfigProvider::CODE,
                DefaultConfigProvider::VAULT_CODE,
                ApplePayConfigProvider::CODE,
            ];
            if (in_array($payment->getMethod(), $paymentCodes)) {
                $this->cancelOrder($cartId, $quote->getReservedOrderId());
            }

            throw $e;
        }
    }

    /**
     * @param $order
     * @return void
     */
    private function doCancelOrder($order): void
    {
        try {
            $order->cancel();
            $this->orderRepository->save($order);
        } catch (\Exception $e) {
            // omit exception
        }
    }

    /**
     * Cancels an order and a payment transaction.
     *
     * @param $cartId
     * @param string|null $incrementId
     * @return bool
     */
    public function cancelOrder($cartId, string $incrementId = null): bool
    {
        // by default set order filter params for search criteria
        $filterField = OrderInterface::INCREMENT_ID;
        $filterValue = $incrementId;

        if (!$incrementId) {
            // try to retrieve order from checkout session
            $order = $this->checkoutSession->getLastRealOrder();
            if ($order->getIncrementId()) {
                $this->doCancelOrder($order);
                return true;
            }

            // set quote filter params for search criteria
            $filterField = OrderInterface::QUOTE_ID;
            $filterValue = $cartId;
        }

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter($filterField, $filterValue)
            ->create();

        $orders = $this->orderRepository
            ->getList($searchCriteria)
            ->getItems();

        if (count($orders) > 1) {
            $order = array_pop($orders);
            $this->doCancelOrder($order);
            return true;
        }

        return false;
    }
}
