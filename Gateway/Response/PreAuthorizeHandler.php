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
 * Class PreAuthorizeHandler
 * @package TotalProcessing\Opp\Gateway\Response
 */
class PreAuthorizeHandler implements HandlerInterface
{
    const CHECKOUT_ID = 'id';
    const BUILD_NUMBER = 'buildNumber';
    const NDC = 'ndc';
    const RESULT = 'result';
    const TIMESTAMP = 'timestamp';

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
        $this->checkoutSession->setCheckoutId($checkoutId ?? '');
    }
}
