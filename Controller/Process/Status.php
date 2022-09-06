<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Controller\Process;

/**
 * Class Status
 * @package TotalProcessing\Opp\Controller\Process
 */
class Status extends BaseAction
{
    /**
     * Set redirect.
     *
     * @return void
     */
    public function execute()
    {
        $this->logger->debug(
            "Request Params before checking " . $this->checkoutSession->getQuote()->getPayment()->getMethod(),
            $this->getRequest()->getParams()
        );

        $this->_view->loadLayout();
        $layout = $this->_view->getLayout();
        $block = $layout->getBlock("opp_process_status");

        echo $block->toHtml();
    }
}
