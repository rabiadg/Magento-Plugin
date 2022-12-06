<?php

/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Block\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use TotalProcessing\Opp\Gateway\Config\ApplePay\Config;
use TotalProcessing\Opp\Gateway\Config\Config as BaseConfig;
use TotalProcessing\Opp\Gateway\Request\ApplePay\RegisterDataBuilder;
use TotalProcessing\Opp\Model\Ui\ApplePay\ConfigProvider;
use TotalProcessing\Opp\Model\Ui\ConfigProvider as BaseConfigProvider;

/**
 * Class RegisterMerchantButton
 * @package TotalProcessing\Opp\Block\System\Config
 */
class RegisterMerchantButton extends Field
{
    /**
     * @var string
     */
    protected $_template = 'TotalProcessing_Opp::system/config/registerMerchantButton.phtml';

    /**
     * Constructor
     *
     * @param Context $context
     * @param array $data
     */
    public function __construct(Context $context, array $data = [])
    {
        parent::__construct($context, $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Returns access token id
     *
     * @return string
     */
    public function getAccessTokenId(): string
    {
        return BaseConfig::KEY_ACCESS_TOKEN;
    }

    /**
     * Returns ajax url for collect button
     *
     * @return string
     */
    public function getAjaxUrl(): string
    {
        return $this->getUrl('totalprocessing_opp/merchant/register');
    }

    /**
     * Returns button html
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()
            ->createBlock('Magento\Backend\Block\Widget\Button')
            ->setData([
                'id' => $this->getButtonId(),
                'label' => __('Register Merchant'),
            ]);

        return $button->toHtml();
    }

    /**
     * Returns button id
     *
     * @return string
     */
    public function getButtonId(): string
    {
        return "live_register_merchant_btn";
    }

    /**
     * Returns display name field id
     *
     * @return string
     */
    public function getDisplayNamesId(): string
    {
        return Config::KEY_DISPLAY_NAME;
    }

    /**
     * Returns domain names field id
     *
     * @return string
     */
    public function getDomainNamesId(): string
    {
        return Config::KEY_DOMAIN_NAMES;
    }

    /**
     * Returns entity id field id
     *
     * @return string
     */
    public function getEntityId(): string
    {
        return BaseConfig::KEY_ENTITY_ID;
    }

    /**
     * Environment field id
     *
     * @return string
     */
    public function getEnvironmentId(): string
    {
        return ConfigProvider::CODE . "_" . Config::KEY_ENVIRONMENT;
    }

    /**
     * Returns merchant identifier field id
     *
     * @return string
     */
    public function getMerchantIdentifierId(): string
    {
        return Config::KEY_MERCHANT_IDENTIFIER;
    }

    /**
     * Returns register field id
     *
     * @return string
     */
    public function getRegisterUrlId(): string
    {
        return Config::KEY_REGISTER_URL;
    }

    /**
     * {@inheritdoc}
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }
}
