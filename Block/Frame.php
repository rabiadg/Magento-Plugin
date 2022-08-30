<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Block;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;

/**
 * Class Frame
 * @package TotalProcessing\Opp\Block
 */
class Frame extends Template
{
    /**
     * @return Frame
     */
    protected function _prepareLayout()
    {
        $this->pageConfig->addPageAsset('jquery/jquery.min.js');
        return parent::_prepareLayout();
    }

    /**
     * @param $store
     * @return int
     * @throws NoSuchEntityException
     */
    public function getStoreId($store = null)
    {
        return $this->_storeManager->getStore($store)->getId();
    }
}
