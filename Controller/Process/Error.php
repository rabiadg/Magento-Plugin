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
class Error extends BaseAction
{
    /**
     * @return void
     */
    public function execute()
    {
        $this->_view->loadLayout(false);
        $block = $this->_view->getLayout()->getBlock('opp.process.error');
        $block->setErrorMessage($this->getRequest()->getParam('error_message'));
        $this->_view->renderLayout();
    }

    /**
     * @return void
     */
    private function terminate($message)
    {
        $response = $this->getResponse();
        $response->clearHeaders()
            ->setHttpResponseCode(500)
            ->setHeader('Content-Type', 'text/plain')
            ->setBody($message)
            ->sendResponse();
    }
}
