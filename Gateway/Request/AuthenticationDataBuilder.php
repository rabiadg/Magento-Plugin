<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use TotalProcessing\Opp\Gateway\Config\Config;
use TotalProcessing\Opp\Gateway\SubjectReader;

/**
 * Class AuthenticationDataBuilder
 * @package TotalProcessing\Opp\Gateway\Request
 */
class AuthenticationDataBuilder implements BuilderInterface
{
    /**
     * The entity required to authorize the request
     * <br/>
     * <strong>CONDITIONAL</strong>
     */
    const ENTITY_ID = 'entityId';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @param Config $config
     * @param SubjectReader $subjectReader
     */
    public function __construct(Config $config, SubjectReader $subjectReader)
    {
        $this->config = $config;
        $this->subjectReader = $subjectReader;
    }

    /**
     * {@inheritdoc}
     */
    public function build(array $buildSubject): array
    {
        $this->subjectReader->debug("AUTHENTICATION buildSubject", $buildSubject);

        $paymentDataObject = $this->subjectReader->readPayment($buildSubject);
        $order = $paymentDataObject->getOrder();

        $result = [
            self::ENTITY_ID => $this->config->getEntityId($order->getStoreId()),
        ];

        $this->subjectReader->debug("AUTHENTICATION request", $result);

        return $result;
    }
}
