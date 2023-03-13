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
use Magento\Store\Model\StoreManagerInterface;
use TotalProcessing\Opp\Gateway\Helper\ApplePay\Merchant as MerchantHelper;
use TotalProcessing\Opp\Gateway\Config\Config;

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
     * @var Config
     */
    private $config;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Context $context
     * @param MerchantHelper $merchantHelper
     * @param JsonFactory $resultJsonFactory
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        MerchantHelper $merchantHelper,
        JsonFactory $resultJsonFactory,
        Config $config,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->merchantHelper = $merchantHelper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->config = $config;
        $this->storeManager = $storeManager;
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
            $this->preparePostData($data);
            $response = $this->merchantHelper->registerMerchant($environment, $url, $data);
        } catch (\Throwable $t) {
            return $resultJson->setData(['success' => false]);
        }

        return $resultJson->setData($response);
    }

    /**
     * @param array $data
     * @return void
     */
    private function preparePostData(array &$data)
    {
        $storeId = $this->storeManager->getStore()->getId();
        if (isset($data['entityId']) && $data['entityId'] == '******') {
            // encrypt value
            $data['entityId'] = $this->config->getEntityId($storeId);
        }
        if (isset($data['accessToken']) && $data['accessToken'] == '******') {
            // encrypt value
            $data['accessToken'] = $this->config->getAccessToken($storeId);
        }
        unset($data['url'], $data['env']);
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('TotalProcessing_Opp::config');
    }
}
