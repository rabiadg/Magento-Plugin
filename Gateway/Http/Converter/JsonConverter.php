<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Http\Converter;

use Magento\Payment\Gateway\Http\ConverterException;
use Magento\Payment\Gateway\Http\ConverterInterface;
use Psr\Log\LoggerInterface;

/**
 * Class JsonConverter
 */
class JsonConverter implements ConverterInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * JsonConverter constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function convert($response): array
    {
        $msg = "The gateway response format was incorrect. Verify the format and try again.";
        if (!is_string($response)) {

            $this->logger->debug("NotString. " . $msg, [$response]);
            throw new ConverterException(__($msg));
        }

        try {
            $convertedResponse = json_decode($response, true);
        } catch (\Exception $e) {
            $this->logger->debug("NotJson. " . $msg, [$response]);
            throw new ConverterException(__($msg));
        }
        $error = json_last_error();
        if ($error !== JSON_ERROR_NONE) {

            $this->logger->debug("JsonError. " . $msg, [$response, $error] );
            throw new ConverterException(__($msg));
        }

        return $convertedResponse;
    }
}
