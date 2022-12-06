<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Http;

use Magento\Framework\HTTP\ZendClient;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use TotalProcessing\Opp\Gateway\Request\BaseRequestDataBuilder;

/**
 * Class TransferFactory
 * @package TotalProcessing\Opp\Gateway\Http
 */
class TransferFactory implements TransferFactoryInterface
{
    /**
     * @var TransferBuilder
     */
    protected $transferBuilder;

    /**
     * Constructor
     *
     * @param TransferBuilder $transferBuilder
     */
    public function __construct(
        TransferBuilder $transferBuilder
    ) {
        $this->transferBuilder = $transferBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $request): TransferInterface
    {
        if (isset($request[BaseRequestDataBuilder::REQUEST_DATA_NAMESPACE])) {
            $requestData = $request[BaseRequestDataBuilder::REQUEST_DATA_NAMESPACE];
            $method = $requestData[BaseRequestDataBuilder::REQUEST_DATA_METHOD] ?? ZendClient::GET;
            $url = $requestData[BaseRequestDataBuilder::REQUEST_DATA_URL] ?? '';
            $headers = $requestData[BaseRequestDataBuilder::REQUEST_DATA_HEADERS] ?? [];
            $encode = $requestData[BaseRequestDataBuilder::REQUEST_ENCODE] ?? false;
            unset($request[BaseRequestDataBuilder::REQUEST_DATA_NAMESPACE]);
        }

        return $this->transferBuilder
            ->setUri($url ?? '')
            ->setMethod($method ?? ZendClient::GET)
            ->setHeaders($headers ?? [])
            ->setBody($request[BaseRequestDataBuilder::REQUEST_DATA_RAW_BODY] ?? $request)
            ->shouldEncode($encode ?? false)
            ->build();
    }
}
