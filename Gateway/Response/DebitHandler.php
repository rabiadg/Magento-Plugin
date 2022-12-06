<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Response;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Psr\Log\LoggerInterface;
use TotalProcessing\Opp\Gateway\SubjectReader;

/**
 * Class DebitHandler
 * @package TotalProcessing\Opp\Gateway\Response
 */
class DebitHandler implements HandlerInterface
{
    const CHECKOUT_ID = 'id';

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param SubjectReader $subjectReader
     * @param CheckoutSession $checkoutSession
     * @param LoggerInterface $logger
     */
    public function __construct(
        SubjectReader $subjectReader,
        CheckoutSession $checkoutSession,
        LoggerInterface $logger
    ) {
        $this->subjectReader = $subjectReader;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(array $handlingSubject, array $response)
    {
        $checkoutId = $this->subjectReader->readResponse($response, self::CHECKOUT_ID);
        if (!$checkoutId) {
            throw new \InvalidArgumentException('Checkout can\'t be initialized.');
        }
        $this->checkoutSession->setCheckoutId($checkoutId);
    }
}
