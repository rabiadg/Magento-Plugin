<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Controller\ApplePay;

use TotalProcessing\Opp\Controller\AbstractAction;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class MerchantSession
 * @package TotalProcessing\Opp\Controller\ApplePay
 */
class MerchantSession extends AbstractAction
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        /** @var Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $data = $this->applePayMerchantHelper->completeValidation(
                $this->request->getParam('validationUrl')
            );
            $resultJson->setData($data);
        } catch (\Throwable $t) {
            $resultJson->setStatusHeader(400);
        }

        return $resultJson;
    }
}
