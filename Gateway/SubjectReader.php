<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Psr\Log\LoggerInterface;
use TotalProcessing\Opp\Model\System\Config\ScheduleType;

/**
 * Class SubjectReader
 * @package TotalProcessing\Opp\Gateway
 */
class SubjectReader
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * SubjectReader constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->logger->setBacktraceLimit(5);
    }

    /**
     * Reads amount from subject
     *
     * @param array $subject
     * @return int|string
     */
    public function readAmount(array $subject)
    {
        if (!isset($subject['amount']) || !is_numeric($subject['amount'])) {
            $msg = 'Amount should be provided';
            $this->logger->critical($msg, $subject);
            throw new \InvalidArgumentException($msg);
        }

        $this->debug("Amount", ['amount' => $subject['amount']]);
        return $subject['amount'];
    }

    /**
     * Reads currency from subject
     *
     * @param array $subject
     * @return string
     */
    public function readCurrency(array $subject): string
    {
        if (!isset($subject['currencyCode']) || !is_string($subject['currencyCode'])) {
            $msg = 'Currency code should be provided.';
            $this->logger->critical($msg, $subject);
            throw new \InvalidArgumentException($msg);
        }

        $this->debug("Currency", ['currencyCode' => $subject['currencyCode']]);
        return $subject['currencyCode'];
    }

    /**
     * Reads payment from subject
     *
     * @param array $subject
     * @return PaymentDataObjectInterface
     */
    public function readPayment(array $subject): PaymentDataObjectInterface
    {
        if (!isset($subject['payment'])
            || !$subject['payment'] instanceof PaymentDataObjectInterface
        ) {
            $msg = 'Payment data object should be provided';
            $this->logger->critical($msg, $subject);
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        return $subject['payment'];
    }

    /**
     * Reads schedule type
     *
     * @param array $subject
     * @return array
     */
    public function readScheduleAction(array $subject): array
    {
        $actionPattern = [
            ScheduleType::ACTION_AMOUNT,
            ScheduleType::ACTION_COLLECTION_DAY,
            ScheduleType::ACTION_START_DATE,
            ScheduleType::ACTION_TYPE,
        ];

        if (!isset($subject['schedule'])
            || !is_array($subject['schedule'])
            || array_diff_key($actionPattern, array_keys($subject['schedule']))
        ) {
            $msg = 'Schedule not provided or invalid';
            $this->logger->critical($msg, $subject);
            throw new \InvalidArgumentException($msg);
        }

        return $subject['schedule'];
    }

    /**
     * Reads skip errors
     *
     * @param array $subject
     * @return bool
     */
    public function readSkipErrors(array $subject): bool
    {
        return filter_var($subject['skipErrors'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Reads response from subject
     *
     * @param array $subject
     * @param string|null $namespace
     * @return mixed
     */
    public function readResponse(array $subject, string $namespace = null)
    {
        if (!$subject) {
            throw new \InvalidArgumentException('Response does not exist');
        }

        if ($namespace) {
            if (isset($subject[$namespace])) {
                $subject = $subject[$namespace];
                $this->debug(
                    "[Gateway\SubjectReader] Namespace: " . $namespace,
                    (is_array($subject)) ? $subject : [$subject]
                );
            } else {
                return null;
            }
        } else {
            $this->debug("[Gateway\SubjectReader] FULL Response DATA", $subject);
        }
        return $subject;
    }

    /**
     * Debug message with two lines using provided Logger
     *
     * @param string $msg
     * @param array  $context
     */
    public function debug(string $msg, array $context = []): void
    {
        $this->logger->debug($msg, $context);
    }

    /**
     * @param string $msg
     * @param array  $context
     */
    public function critical(string $msg, array $context): void
    {
        $this->logger->critical($msg, $context);
    }
}
