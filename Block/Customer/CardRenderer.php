<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Block\Customer;

use Magento\Framework\View\Element\Template;
use Magento\Payment\Model\CcConfigProvider;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Block\AbstractCardRenderer;
use TotalProcessing\Opp\Model\Ui\ConfigProvider;

/**
 * Class CardRenderer
 * @package TotalProcessing\Opp\Block\Customer
 */
class CardRenderer extends AbstractCardRenderer
{
    /**
     * @var ConfigProvider
     */
    private $iconsProvider;

    /**
     * @param Template\Context $context
     * @param CcConfigProvider $iconsProvider
     * @param ConfigProvider $configProvider
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        CcConfigProvider $iconsProvider,
        ConfigProvider $configProvider,
        array $data = []
    ) {
        parent::__construct($context, $iconsProvider, $data);
        $this->iconsProvider = $configProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function canRender(PaymentTokenInterface $token): bool
    {
        return $token->getPaymentMethodCode() === ConfigProvider::CODE;
    }

    /**
     * {@inheritdoc}
     */
    public function getNumberLast4Digits(): string
    {
        return $this->getTokenDetails()['maskedCC'];
    }

    /**
     * {@inheritdoc}
     */
    public function getExpDate(): string
    {
        return $this->getTokenDetails()['expirationDate'];
    }

    /**
     * {@inheritdoc}
     */
    public function getIconUrl()
    {
        return $this->getIconForType($this->getTokenDetails()['type'])['url'];
    }

    /**
     * {@inheritdoc}
     */
    public function getIconHeight()
    {
        return $this->getIconForType($this->getTokenDetails()['type'])['height'];
    }

    /**
     * {@inheritdoc}
     */
    public function getIconWidth()
    {
        return $this->getIconForType($this->getTokenDetails()['type'])['width'];
    }

    /**
     * {@inheritdoc}
     */
    protected function getIconForType($type)
    {
        if (isset($this->iconsProvider->getIcons()[$type])) {
            return $this->iconsProvider->getIcons()[$type];
        }

        return [
            'url' => '',
            'width' => 0,
            'height' => 0
        ];
    }
}
