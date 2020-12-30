<?php

namespace TotalProcessing\TPCARDS\Controller\Cards;

class Redirect extends \Magento\Framework\App\Action\Action
{
    /**
     * Redirect to cards.
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->getLayout()->initMessages();
        $this->_view->renderLayout();
    }
}
