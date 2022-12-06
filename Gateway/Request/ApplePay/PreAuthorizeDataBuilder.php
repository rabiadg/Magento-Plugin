<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Request\ApplePay;

/**
 * Class PreAuthorizeDataBuilder
 * @package TotalProcessing\Opp\Gateway\Request\ApplePay
 */
class PreAuthorizeDataBuilder extends AbstractDataBuilder
{
    const DISPLAY_NAME = 'displayName';
    const INITIATIVE = 'initiative';
    const INITIATIVE_CONTEXT = 'initiativeContext';
    const MERCHANT_IDENTIFIER = 'merchantIdentifier';
    const SESSION_CREATE_PATH = '/session';
    const VALIDATION_URL = 'validationUrl';

    /**
     * {@inheritdoc}
     */
    public function build(array $buildSubject): array
    {
        return [];
    }
}
