<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Controller\ApplePay;

use TotalProcessing\Opp\Controller\AbstractAction;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

/**
 * Class Merchant
 * @package TotalProcessing\Opp\Controller\ApplePay
 */
class Merchant extends AbstractAction
{
    /**
     * Generates .well-known/apple-developer-merchantid-domain-association data and returns it as result
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        /** @var Raw $resultRaw */
        $resultRaw = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $resultRaw->setContents(
            $this->applePayConfig->getMerchantIdDomainAssociation($this->checkoutSession->getStoreId())
        );
        $resultRaw->setHeader('Content-Type', 'text/plain');
        return $resultRaw;
    }
}
