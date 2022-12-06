<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Helper;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\CartRepositoryInterface;;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\ItemFactory as QuoteItemFactory;
use Magento\Quote\Model\ResourceModel\Quote\Item as QuoteItemResourceModel;

/**
 * Class QuoteHelper
 */
class QuoteHelper
{
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var QuoteItemFactory
     */
    protected $quoteItemFactory;

    /**
     * @var QuoteItemResourceModel
     */
    protected $quoteItemResourceModel;

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * BasicDataBuilder constructor.
     *
     * @param CheckoutSession $checkoutSession
     * @param QuoteItemFactory $quoteItemFactory
     * @param QuoteItemResourceModel $quoteItemResourceModel
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        QuoteItemFactory $quoteItemFactory,
        QuoteItemResourceModel $quoteItemResourceModel,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->quoteItemFactory = $quoteItemFactory;
        $this->quoteItemResourceModel = $quoteItemResourceModel;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Return quote
     *
     * @param OrderAdapterInterface $orderAdapter
     * @param InfoInterface $payment
     * @return CartInterface|Quote
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getQuote(OrderAdapterInterface $orderAdapter, InfoInterface $payment): CartInterface
    {
        $isInstantPurchase = filter_var(
            $payment->getAdditionalInformation('instant-purchase'),
            FILTER_VALIDATE_BOOL
        );

        if ($isInstantPurchase) {
            /** @var \Magento\Sales\Model\Order\Item $orderItem */
            $orderItem = current($orderAdapter->getItems());
            /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
            $quoteItem = $this->quoteItemFactory->create();
            $this->quoteItemResourceModel->load($quoteItem, $orderItem->getQuoteItemId());

            if ($quoteItem->isObjectNew()) {
                throw new \Exception("Can't find quote item");
            }

            $quote = $this->quoteRepository->get($quoteItem->getQuoteId());
        } else {
            $quote = $this->checkoutSession->getQuote();
        }

        if (!$quote) {
            throw new \Exception("Can't find quote");
        }

        return $quote;
    }
}
