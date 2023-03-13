<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Controller\Process;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Session\SessionManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Frame
 */
abstract class BaseAction extends Action
{
    protected $checkoutSession;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * BaseAction constructor.
     *
     * @param Context                 $context
     * @param LoggerInterface         $logger
     * @param SessionManagerInterface $checkoutSession
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        SessionManagerInterface $checkoutSession
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->checkoutSession = $checkoutSession;
    }
}
