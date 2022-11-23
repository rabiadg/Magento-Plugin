<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Controller\Process;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Payment\Gateway\Command\CommandException;
use Psr\Log\LoggerInterface;
use TotalProcessing\Opp\Gateway\Helper\PaymentStatusResolver;

/**
 * Class Status
 * @package TotalProcessing\Opp\Controller\Process
 */
class Status extends BaseAction
{
    /**
     * @var PaymentStatusResolver
     */
    private $paymentStatusResolver;

    /**
     * @param Context $context
     * @param LoggerInterface $logger
     * @param SessionManagerInterface $checkoutSession
     * @param PaymentStatusResolver $paymentStatusResolver
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        SessionManagerInterface $checkoutSession,
        PaymentStatusResolver $paymentStatusResolver
    ) {
        parent::__construct(
            $context,
            $logger,
            $checkoutSession
        );
        $this->paymentStatusResolver = $paymentStatusResolver;
    }

    /**
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        try {
            $this->paymentStatusResolver->resolve();
        } catch (CommandException $e) {
            $message = $e->getMessage();
            $this->logger->critical($message);
            $this->_forward('error', null, null, ['error_message' => $message]);
            return;
        }

        $this->_view->loadLayout();
        $layout = $this->_view->getLayout();
        $block = $layout->getBlock("opp_process_status");

        echo $block->toHtml();
    }
}
