<?php
/**
 * Copyright © Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Model\System\Config;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Locale
 * @package TotalProcessing\Opp\Model\System\Config
 */
class Locale implements OptionSourceInterface
{
    const DEFAULT = '';
    const ARABIC = 'ar';
    const BASQUE = 'eu';
    const BELGIAN = 'be';
    const BULGARIAN = 'bg';
    const CATALAN = 'ca';
    const CHINESE_SIMPLIFIED = 'cn';
    const CHINESE_TRADITIONAL = 'zh';
    const CZECH = 'cz';
    const DANISH = 'da';
    const DUTCH = 'nl';
    const GERMAN = 'de';
    const GREEK = 'el';
    const ENGLISH = 'en';
    const ESTONIAN = 'et';
    const FINNISH = 'fi';
    const FRENCH = 'fr';
    const HUNGARIAN = 'hu';
    const INDONESIAN = 'id';
    const ITALIAN = 'it';
    const JAPANESE = 'ja';
    const KOREAN = 'ko';
    const LITHUANIAN = 'lt';
    const LATVIAN = 'lv';
    const NORWEGIAN = 'no';
    const POLISH = 'pl';
    const PORTUGUESE = 'pt';
    const ROMANIAN = 'ro';
    const RUSSIAN = 'ru';
    const SLOVAK = 'sk';
    const SLOVENE = 'sl';
    const SPANISH = 'es';
    const SWEDISH = 'sv';
    const TURKISH = 'tr';

    /**
     * @var string[]
     */
    protected $supportedLanguages = [
        self::DEFAULT => 'Default',
        self::ARABIC => 'Arabic',
        self::BASQUE => 'Basque',
        self::BULGARIAN => 'Bulgarian',
        self::CATALAN => 'Catalan',
        self::CHINESE_SIMPLIFIED => 'Chinese (Simplified)',
        self::CHINESE_TRADITIONAL => 'Chinese (Traditional)',
        self::CZECH => 'Czech',
        self::DANISH => 'Danish',
        self::DUTCH => 'Dutch',
        self::ENGLISH => 'English',
        self::ESTONIAN => 'Estonian',
        self::GERMAN => 'German',
        self::GREEK => 'Greek',
        self::FINNISH => 'Finnish',
        self::FRENCH => 'French',
        self::BELGIAN => 'French (Belgium)',
        self::HUNGARIAN => 'Hungarian',
        self::INDONESIAN => 'Indonesian',
        self::ITALIAN => 'Italian',
        self::JAPANESE => 'Japanese',
        self::KOREAN => 'Korean',
        self::LATVIAN => 'Latvian',
        self::LITHUANIAN => 'Lithuanian',
        self::NORWEGIAN => 'Norwegian',
        self::POLISH => 'Polish',
        self::PORTUGUESE => 'Portuguese',
        self::ROMANIAN => 'Romanian',
        self::RUSSIAN => 'Russian',
        self::SLOVAK => 'Slovak',
        self::SLOVENE => 'Slovene',
        self::SPANISH => 'Spanish',
        self::SWEDISH => 'Swedish',
        self::TURKISH => 'Turkish',
    ];

    /**
     * @var string[]
     */
    protected $specificLanguagesMapList = [
        'fr_BE' => self::BELGIAN,
        'zh_Hans_CN' => self::CHINESE_SIMPLIFIED,
        'cs_CZ' => self::CZECH,
        'nb_NO' => self::NORWEGIAN,
        'nn_NO' => self::NORWEGIAN,
    ];

    /**
     * {@inheritdoc}
     */
    public function toOptionArray(): array
    {
        return array_map(
            function ($value, $label) {
                return ['value' => $value, 'label' => __($label)->getText()];
            },
            array_keys($this->supportedLanguages),
            $this->supportedLanguages
        );
    }

    /**
     * Returns Total Processing locale code based on magento locale code
     *
     * @param string $locale
     * @return string|null
     */
    public function getLocale(string $locale): ?string
    {
        if (array_key_exists($locale, $this->specificLanguagesMapList)) {
            return $this->specificLanguagesMapList[$locale];
        }

        $lang = explode('_', $locale)[0];

        if (array_key_exists($lang, $this->supportedLanguages)) {
            return $lang;
        }

        return null;
    }
}
