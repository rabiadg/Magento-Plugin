<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Controller\Adminhtml\Merchant;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use TotalProcessing\Opp\Gateway\Helper\ApplePay\Merchant as MerchantHelper;

/**
 * Class Register
 * @package TotalProcessing\Opp\Controller\Adminhtml\Merchant
 */
class Register extends Action
{
    /**
     * @var MerchantHelper
     */
    private $merchantHelper;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @param Context $context
     * @param MerchantHelper $merchantHelper
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        MerchantHelper $merchantHelper,
        JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->merchantHelper = $merchantHelper;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        /** @var Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();

        try {
            $url = $this->getRequest()->getPostValue('registerUrl');
            $environment = $this->getRequest()->getPostValue('environment');
            $data = $this->getRequest()->getPostValue();
            unset($data['url'], $data['env']);

            $response = $this->merchantHelper->registerMerchant($environment, $url, $data);
        } catch (\Throwable $t) {
            return $resultJson->setData(['success' => false]);
        }

        return $resultJson->setData($response);
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('TotalProcessing_Opp::config');
    }
}
