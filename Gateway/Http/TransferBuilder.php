<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Http;

use Magento\Payment\Gateway\Http\Transfer;
use Magento\Payment\Gateway\Http\TransferInterface;
use Psr\Log\LoggerInterface;

/**
 * Class TransferBuilder
 * @package TotalProcessing\Opp\Gateway\Http
 */
class TransferBuilder extends \Magento\Payment\Gateway\Http\TransferBuilder
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * TransferBuilder constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return Transfer|TransferInterface
     */
    public function build()
    {
        $transfer = parent::build();

        $this->logger->debug(
            "Transfer Data",
            [
                "ClientConfig" => $transfer->getClientConfig(),
                "Headers" => $transfer->getHeaders(),
                "Body" => $transfer->getBody(),
                "AuthUsername" => $transfer->getAuthUsername(),
                "AuthPassword" => $transfer->getAuthPassword(),
                "Method" => $transfer->getMethod(),
                "Uri" => $transfer->getUri(),
                "Encode" => $transfer->shouldEncode()
            ]
        );

        return $transfer;
    }


}
