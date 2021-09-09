<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Helper;


use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Serialize\Serializer\Json as Serializer;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\CustomerTokenManagement;
use Psr\Log\LoggerInterface;
use TotalProcessing\Opp\Gateway\Config\Config;
use TotalProcessing\Opp\Gateway\Response\ThreeDSecureHandler;
use TotalProcessing\Opp\Model\Ui\ConfigProvider;
use Magento\Payment\Gateway\ConfigInterface;
class PaymentTokenProvider
{
    /**
     * @var CustomerTokenManagement
     */
    private $customerTokenManagement;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * PaymentTokenProvider constructor.
     *
     * @param CheckoutSession         $checkoutSession
     * @param ConfigInterface         $config
     * @param CustomerTokenManagement $customerTokenManagement
     * @param Serializer              $serializer
     * @param LoggerInterface         $logger
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        ConfigInterface $config,
        CustomerTokenManagement $customerTokenManagement,
        Serializer $serializer,
        LoggerInterface $logger
    )
    {
        $this->checkoutSession = $checkoutSession;
        /** @var Config config */
        $this->config = $config;
        $this->customerTokenManagement = $customerTokenManagement;
        $this->logger = $logger;
        $this->serializer = $serializer;
    }

    /**
     * Provide user payment tokens which are active, visible and
     *  payment method code is the same as code of the ConfigProvider::CODE
     *
     * @return PaymentTokenInterface[]
     */
    public function getRegistrationIds(): array
    {
        return array_values(
            array_filter(
                $this->customerTokenManagement->getCustomerSessionTokens(),
                function ($token) {
                    return $token->getPaymentMethodCode() === ConfigProvider::CODE;
                }
            )
        );
    }


    /**
     * Provide payment tokens filtered by:
     *  is Brand Types enabled
     *  is 3D Secure Card
     *
     * @param bool $enableBrandFiltering true -> remove cards of not active card brands types from result
     * @param bool $enable3DSecureFiltering true -> remove cards with 3d secure from result
     * @return PaymentTokenInterface[]
     */
    public function getFilteredTokens($enableBrandFiltering = true, $enable3DSecureFiltering = false): array
    {
        // All filters disabled then bypass checking
        if ( ($enableBrandFiltering || $enable3DSecureFiltering) == false) {
            return $this->getRegistrationIds();
        }
        $availableBrands = $this->config->getAvailableBrandTypes($this->checkoutSession->getQuote()->getStoreId()) ?? [];
        $this->logger->debug("Available Brands" , $availableBrands);
        $logger = $this->logger;

        // Filter by active filters
        return array_values(
            array_filter(
                $this->getRegistrationIds(), function ($token) use ($enable3DSecureFiltering, $enableBrandFiltering, $logger, $availableBrands) {

                $details = $this->serializer->unserialize($token->getTokenDetails());
                $logger->debug("Filtering Tokens: token data", ["Details" => $details, " Availability CHECK " => in_array(
                    $details["type"] ?? null, $availableBrands
                ) ]);

                // if filter is true, check json details data
                $status = ((!$enableBrandFiltering)?: in_array(
                    $details["type"] ?? null, $availableBrands
                ))
                // if filter is true, check json details data
                && ((!$enable3DSecureFiltering)?: !filter_var(
                    $details[ThreeDSecureHandler::IS_THREE_D_SECURE] ?? null, FILTER_VALIDATE_BOOLEAN
                ));
                $logger->debug("Result Status" , [$status] );
                return $status;

            })
        );
    }
}
