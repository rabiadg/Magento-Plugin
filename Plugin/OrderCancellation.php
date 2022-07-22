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
     * @param LoggerInterface $logger
     * @param OrderRepositoryInterface $orderRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        LoggerInterface $logger,
        OrderRepositoryInterface $orderRepository,
        CartRepositoryInterface $quoteRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->quoteRepository = $quoteRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
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
                $incrementId = $quote->getReservedOrderId();
                if ($incrementId) {
                    $this->cancelOrder($incrementId);
                }
            }

            throw $e;
        }
    }

    /**
     * Cancels an order and a payment transaction.
     *
     * @param string $incrementId
     * @return bool
     */
    public function cancelOrder(string $incrementId): bool
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(OrderInterface::INCREMENT_ID, $incrementId)
            ->create();

        $orders = $this->orderRepository
            ->getList($searchCriteria)
            ->getItems();

        if (count($orders) > 1) {
            $order = array_pop($orders);
            $order->cancel();
            $this->orderRepository->save($order);

            return true;
        }

        return false;
    }
}
