<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Helper;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use TotalProcessing\Opp\Gateway\Config\Config;
use TotalProcessing\Opp\Model\System\Config\MerchantTransactionIdType;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * Class MerchantTransactionIdManager
 * @package TotalProcessing\Opp\Gateway\Helper
 */
class MerchantTransactionIdProvider
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @param CheckoutSession $checkoutSession
     * @param Config $config
     * @param CartRepositoryInterface $cartRepository
     * @param $type
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        Config $config,
        CartRepositoryInterface $cartRepository
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->config = $config;
        $this->cartRepository = $cartRepository;
    }

    /**
     * Get merchant transaction ID based on chosen type.
     *
     * @param Quote $quote
     * @return mixed|string|null
     * @throws LocalizedException
     */
    public function execute($quote = null)
    {
        if (null === $quote) {
            try {
                $quote = $this->checkoutSession->getQuote();
            } catch (LocalizedException $e) {
                throw new LocalizedException(__('Can\'t init current quote to resolve transaction ID.'));
            }
        }

        $type = $this->config->getMerchantTransactionIdType($quote->getStoreId());
        if (
            ($type == MerchantTransactionIdType::UUID)
            && ($merchantTransactionId = $quote->getOppMerchantTransactionId())
        ) {
            return $merchantTransactionId;
        }
        if ($type == MerchantTransactionIdType::INCREMENT_ID) {
            $merchantTransactionId = $quote->getReservedOrderId();
            if (!$merchantTransactionId) {
                $quote->reserveOrderId();
                $this->cartRepository->save($quote);
                $merchantTransactionId = $quote->getReservedOrderId();
            }
            return $merchantTransactionId;
        }

        return null;
    }
}
