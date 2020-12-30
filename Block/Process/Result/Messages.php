<?php

namespace TotalProcessing\TPCARDS\Block\Process\Result;

class Messages extends \Magento\Framework\View\Element\Messages
{
    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        $messages = $this->messageManager->getMessages(true, 'totalprocessing_messages');
        $this->addMessages($messages);

        return parent::_prepareLayout();
    }
}
