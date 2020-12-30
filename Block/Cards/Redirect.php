<?php

namespace TotalProcessing\TPCARDS\Block\Cards;

class Redirect extends \Magento\Payment\Block\Form
{
    /**
     * @var \TotalProcessing\TPCARDS\Helper\Data
     */
    private $_helper;

    /**
     * Redirect constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \TotalProcessing\TPCARDS\Helper\Data                  $helper
     * @param array                                            $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \TotalProcessing\TPCARDS\Helper\Data $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_helper = $helper;
    }

    /**
     * @return string
     */
    public function getFormUrl()
    {
        $result = '';
        try {
            $result = $this->_helper->getFormUrl();
        } catch (\Exception $e) {
            // do nothing for now
            $this->_helper->logDebug($e);
            throw($e);
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getFormFields()
    {
        $result = [];
        try {
            $result = $this->_helper->getOTBFormFields();
        } catch (\Exception $e) {
            // do nothing for now
            $this->_helper->logDebug($e);
        }

        return $result;
    }
}
