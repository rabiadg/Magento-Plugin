<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Helper\ApplePay;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Payment\Gateway\Http\ConverterException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use TotalProcessing\Opp\Gateway\Config\ApplePay\Config;
use TotalProcessing\Opp\Gateway\Http\Converter\JsonConverter;
use TotalProcessing\Opp\Gateway\Request\ApplePay\PreAuthorizeDataBuilder as DataBuilder;
use TotalProcessing\Opp\Model\System\Config\ApplePay\Initiative;
use TotalProcessing\Opp\Model\System\Config\Environment;

/**
 * Class Merchant
 * @package TotalProcessing\Opp\Gateway\Helper\ApplePay
 */
class Merchant
{
    /**
     * @var ZendClientFactory
     */
    private $clientFactory;
    /**
     * @var Config
     */
    private $config;

    /**
     * @var JsonConverter
     */
    private $jsonConverter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Config $config
     * @param ZendClientFactory $clientFactory
     * @param LoggerInterface $logger
     * @param JsonConverter $jsonConverter
     * @param RequestInterface $request
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Config $config,
        ZendClientFactory $clientFactory,
        LoggerInterface $logger,
        JsonConverter $jsonConverter,
        RequestInterface $request,
        StoreManagerInterface $storeManager
    ) {
        $this->config = $config;
        $this->clientFactory = $clientFactory;
        $this->logger = $logger;
        $this->jsonConverter = $jsonConverter;
        $this->request = $request;
        $this->storeManager = $storeManager;
    }


    /**
     * Registering the merchant with the payment gateway
     *
     * @param $environment
     * @param $url
     * @param array $data
     * @return array|false[]
     * @throws ConverterException
     * @throws \Throwable
     */
    public function registerMerchant($environment, $url, array $data)
    {
        try {
            if ($environment == Environment::ENVIRONMENT_SANDBOX) {
                $data["entityId_test"] = $data['entityId'] ?? '';
                $data["accessToken_test"] = $data['accessToken'] ?? '';
                unset($data["accessToken"], $data["entityId"]);
            }

            $this->logger->debug("Register Merchant data", $data);

            $client = $this->clientFactory->create();
            $client
                ->setMethod(ZendClient::POST)
                ->setHeaders(["Content-Type" => 'application/x-www-form-urlencoded'])
                ->setParameterPost($data)
                ->setUri($url);

            $response = $client->request();

            $this->logger->debug("Response", [$response->getBody()]);

            $result = $this->jsonConverter->convert($response->getBody());

            if ($result['status'] == true && ($merchantId = ($result['partnerInternalMerchantIdentifier'] ?? null))) {
                return [
                    "status" => true,
                    "merchantIdentifier" => $merchantId
                ];
            } else {
                return ["status" => false];
            }
        } catch (\Throwable $t) {
            $this->logger->critical($t->getMessage(), $t->getTrace());
            throw $t;
        }
    }

    /**
     * Completing the merchant validation process
     *
     * @param $validationUrl
     * @return array
     * @throws ConverterException
     * @throws NoSuchEntityException
     * @throws \Throwable
     */
    public function completeValidation($validationUrl)
    {
        try {
            $storeId = $this->storeManager->getStore()->getId();

            $data = [
                DataBuilder::MERCHANT_IDENTIFIER => $this->config->getMerchantIdentifier($storeId),
                DataBuilder::DISPLAY_NAME => $this->config->getDisplayName($storeId),
                DataBuilder::INITIATIVE_CONTEXT => $this->request->getServer("HTTP_HOST"),
                DataBuilder::INITIATIVE => Initiative::WEB
            ];

            $queryParams = [
                DataBuilder::VALIDATION_URL => $validationUrl
            ];

            $url = rtrim($this->config->getApiUrl($storeId), '/')
                . DataBuilder::SESSION_CREATE_PATH
                . "?" . http_build_query($queryParams);

            $this->logger->debug("Complete Merchant Validation query params", $queryParams);
            $this->logger->debug("Complete Merchant Validation data", $data);

            $client = $this->clientFactory->create();
            $client
                ->setMethod(ZendClient::POST)
                ->setRawData(json_encode($data))
                ->setHeaders(["Content-Type" => 'application/json'])
                ->setUri($url);

            $response = $client->request();

            $this->logger->debug("Response", [$response->getBody()]);

            $result = $this->jsonConverter->convert($response->getBody());

            if (isset($result['statusCode']) && ($result['statusCode'] < 200 || (int) $result['statusCode'] > 299)) {
                throw new \Exception("Can't complete merchant validation");
            }

            return $result;
        } catch (\Throwable $t) {
            $this->logger->critical($t->getMessage(), $t->getTrace());
            throw $t;
        }
    }
}
