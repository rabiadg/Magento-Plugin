<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Plugin;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Session\SessionStartChecker;

/**
 * Intended to preserve session cookie after submitting POST form from payment gateway to Magento controller.
 *
 * Class TransparentSessionChecker
 * @package TotalProcessing\Opp\Plugin
 */
class PaymentSessionChecker
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var string[]
     */
    private $disableSessionUrls;

    /**
     * @param RequestInterface $request
     * @param array $disableSessionUrls
     */
    public function __construct(
        RequestInterface $request,
        array $disableSessionUrls = []
    ) {
        $this->request = $request;
        $this->disableSessionUrls = $disableSessionUrls;
    }

    /**
     * Prevents session starting while instantiating payment processing.
     *
     * @param SessionStartChecker $subject
     * @param bool $result
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCheck(
        SessionStartChecker $subject,
        bool $result
    ): bool {
        if ($result === false) {
            return false;
        }

        foreach ($this->disableSessionUrls as $url) {
            if (strpos((string)$this->request->getPathInfo(), $url) !== false) {
                return false;
            }
        }

        return true;
    }
}
