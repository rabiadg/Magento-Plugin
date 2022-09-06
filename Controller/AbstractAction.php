<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Controller;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Session\SessionManagerInterface;
use Psr\Log\LoggerInterface;
use TotalProcessing\Opp\Gateway\Config\ApplePay\Config as ApplePayConfig;
use TotalProcessing\Opp\Gateway\Helper\ApplePay\Merchant as ApplePayMerchantHelper;

/**
 * Class AbstractAction
 * @package TotalProcessing\Opp\Controller
 */
abstract class AbstractAction implements BaseActionInterface
{
    /**
     * @var ResultFactory
     */
    protected $resultFactory;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var SessionManagerInterface
     */
    protected $checkoutSession;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ApplePayConfig
     */
    protected $applePayConfig;

    /**
     * @var ApplePayMerchantHelper
     */
    protected $applePayMerchantHelper;

    /**
     * @param ResultFactory $resultFactory
     * @param RequestInterface $request
     * @param SessionManagerInterface $checkoutSession
     * @param LoggerInterface $logger
     * @param ApplePayConfig $applePayConfig
     * @param ApplePayMerchantHelper $applePayMerchantHelper
     */
    public function __construct(
        ResultFactory $resultFactory,
        RequestInterface $request,
        SessionManagerInterface $checkoutSession,
        LoggerInterface $logger,
        ApplePayConfig $applePayConfig,
        ApplePayMerchantHelper $applePayMerchantHelper
    ) {
        $this->resultFactory = $resultFactory;
        $this->request = $request;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
        $this->applePayConfig = $applePayConfig;
        $this->applePayMerchantHelper = $applePayMerchantHelper;
    }
}
