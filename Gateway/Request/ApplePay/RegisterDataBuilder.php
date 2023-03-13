<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Request\ApplePay;

/**
 * Class RegisterDataBuilder
 */
class RegisterDataBuilder extends AbstractDataBuilder
{
    const ACCESS_TOKEN = 'accessToken';
    const ACCESS_TOKEN_TEST = 'accessToken_test';
    const ENTITY_ID = 'entityId';
    const ENTITY_ID_TEST = 'entityId_test';
    const DISPLAY_NAME = 'displayName';
    const DOMAIN_NAMES = 'domainNames';

    /**
     * {@inheritdoc}
     */
    public function build(array $buildSubject): array
    {
        return [];
    }
}
