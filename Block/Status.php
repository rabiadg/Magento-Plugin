<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Block;

use Magento\Framework\View\Element\Template;

/**
 * Class Status
 */
class Status extends Template
{
    public function __construct(
        Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_logger->debug("HIT", $data);
    }
}
