<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Controller\ApplePay;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use TotalProcessing\Opp\Gateway\Helper\ApplePay\Merchant as MerchantHelper;

/**
 * Class Merchant
 */
class MerchantSession extends Action
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
     * Constructor
     *
     * @param MerchantHelper $merchantHelper
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        MerchantHelper $merchantHelper,
        Context $context,
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
        $result = $this->resultJsonFactory->create();

        try {
            $data = $this->merchantHelper->completeValidation($this->getRequest()->getParam('validationUrl'));

            $result->setData($data);
        } catch (\Throwable $t) {
            $result->setStatusHeader(400);
        }

        return $result;
    }
}
