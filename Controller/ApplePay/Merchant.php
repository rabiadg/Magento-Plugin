<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Controller\ApplePay;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Session\SessionManagerInterface;
use TotalProcessing\Opp\Gateway\Config\ApplePay\Config;

/**
 * Class Merchant
 */
class Merchant extends Action
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var SessionManagerInterface
     */
    private $session;

    /**
     * Merchant constructor.
     *
     * @param Config $config
     * @param Context $context
     * @param SessionManagerInterface $session
     */
    public function __construct(
        Config $config,
        Context $context,
        SessionManagerInterface $session
    ) {
        parent::__construct($context);
        $this->session = $session;
        $this->config = $config;
    }

    /**
     * Generates .well-known/apple-developer-merchantid-domain-association data and returns it as result
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $page = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $page->setContents($this->config->getMerchantIdDomainAssociation($this->session->getStoreId()));
        $page->setHeader('Content-Type', 'text/plain');
        return $page;
    }
}
