<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Util;

use Exception;
use Icinga\Test\BaseTestCase;
use Icinga\Util\Translator;

class TranslatorWithHardcodedLocaleCodes extends Translator
{
    public static function getAvailableLocaleCodes()
    {
        return array('en_US', 'de_DE', 'de_AT');
    }
}

class TranslatorTest extends BaseTestCase
{
    public function setUp()
    {
        parent::setUp();
        Translator::registerDomain('icingatest', BaseTestCase::$testDir . '/res/locale');
    }

    public function testWhetherGetAvailableLocaleCodesReturnsAllAvailableLocaleCodes()
    {
        $expected = array(Translator::DEFAULT_LOCALE, 'de_DE', 'fr_FR');
        $result = Translator::getAvailableLocaleCodes();

        sort($expected);
        sort($result);

        $this->assertEquals(
            $expected,
            $result,
            'Translator::getAvailableLocaleCodes does not return all available locale codes'
        );
    }

    public function testWhetherSetupLocaleSetsUpTheGivenLocale()
    {
        Translator::setupLocale('de_DE');
        $this->assertContains(
            setlocale(LC_ALL, 0),
            array('de_DE', 'de_DE.UTF-8'),
            'Translator::setupLocale does not properly set up a given locale'
        );
    }

    /**
     * @expectedException Icinga\Exception\IcingaException
     */
    public function testWhetherSetupLocaleThrowsAnExceptionWhenGivenAnInvalidLocale()
    {
        Translator::setupLocale('foobar');
    }

    public function testWhetherSetupLocaleSetsCAsLocaleWhenGivenAnInvalidLocale()
    {
        try {
            Translator::setupLocale('foobar');
            $this->fail('Translator::setupLocale does not throw an exception when given an invalid locale');
        } catch (Exception $e) {
            $this->assertEquals(
                'C',
                setlocale(LC_ALL, 0),
                'Translator::setupLocale does not set C as locale in case the given one is invalid'
            );
        }
    }

    public function testWhetherTranslateReturnsTheCorrectMessageForTheCurrentLocale()
    {
        Translator::setupLocale('de_DE');

        $this->assertEquals(
            'Lorem ipsum dolor sit amet!',
            Translator::translate('Lorem ipsum dolor sit amet', 'icingatest'),
            'Translator::translate does not translate the given message correctly to German'
        );

        Translator::setupLocale('fr_FR');

        $this->assertEquals(
            'Lorem ipsum dolor sit amet?',
            Translator::translate('Lorem ipsum dolor sit amet', 'icingatest'),
            'Translator::translate does not translate the given message correctly to French'
        );
    }

    public function testWhetherSplitLocaleCodeSplitsValidLocalesCorrectly()
    {
        $localeObj = Translator::splitLocaleCode('de_DE');
        $this->assertEquals(
            'de',
            $localeObj->language,
            'Translator::splitLocaleCode does not split the language code correctly'
        );
        $this->assertEquals(
            'DE',
            $localeObj->country,
            'Translator::splitLocaleCode does not split the country code correctly'
        );
    }

    /**
     * @depends testWhetherSplitLocaleCodeSplitsValidLocalesCorrectly
     */
    public function testWhetherSplitLocaleCodeCanHandleEncodingSuffixes()
    {
        $this->assertEquals(
            'US',
            Translator::splitLocaleCode('en_US.UTF-8')->country,
            'Translator::splitLocaleCode does not handle encoding suffixes correctly'
        );
    }

    public function testWhetherSplitLocaleCodeInterpretsInvalidLocaleCodesAsLanguageCodes()
    {
        $this->assertEquals(
            'de',
            Translator::splitLocaleCode('de')->language,
            'Translator::splitLocaleCode does not interpret invalid locale codes as language codes'
        );
        $this->assertEquals(
            'en~US',
            Translator::splitLocaleCode('en~US')->language,
            'Translator::splitLocaleCode does not interpret invalid locale codes as language codes'
        );
    }

    /**
     * @depends testWhetherSplitLocaleCodeSplitsValidLocalesCorrectly
     */
    public function testWhetherSplitLocaleCodeReturnsTheDefaultLocaleWhenGivenCAsLocale()
    {
        $cLocaleObj = Translator::splitLocaleCode('C');
        $defaultLocaleObj = Translator::splitLocaleCode(Translator::DEFAULT_LOCALE);
        $this->assertEquals(
            $defaultLocaleObj->language,
            $cLocaleObj->language,
            'Translator::splitLocaleCode does not return the default language code when given C as locale'
        );
        $this->assertEquals(
            $defaultLocaleObj->country,
            $cLocaleObj->country,
            'Translator::splitLocaleCode does not return the default country code when given C as locale'
        );
    }

    /**
     * @depends testWhetherSplitLocaleCodeSplitsValidLocalesCorrectly
     * @depends testWhetherSplitLocaleCodeInterpretsInvalidLocaleCodesAsLanguageCodes
     */
    public function testWhetherGetPreferredLocaleCodeFavorsPerfectMatches()
    {
        $this->assertEquals(
            'de_DE',
            TranslatorWithHardcodedLocaleCodes::getPreferredLocaleCode('jp,de_DE;q=0.8,de;q=0.6'),
            'Translator::getPreferredLocaleCode does not favor perfect matches'
        );
    }

    /**
     * @depends testWhetherSplitLocaleCodeSplitsValidLocalesCorrectly
     * @depends testWhetherSplitLocaleCodeInterpretsInvalidLocaleCodesAsLanguageCodes
     */
    public function testWhetherGetPreferredLocaleCodeReturnsThePreferredSimilarMatchEvenThoughAPerfectMatchWasFound()
    {
        $this->assertEquals(
            'de_DE',
            TranslatorWithHardcodedLocaleCodes::getPreferredLocaleCode('de_CH,en_US;q=0.8'),
            'Translator::getPreferredLocaleCode does not return the preferred similar match'
        );
    }

    /**
     * @depends testWhetherSplitLocaleCodeSplitsValidLocalesCorrectly
     * @depends testWhetherSplitLocaleCodeInterpretsInvalidLocaleCodesAsLanguageCodes
     */
    public function testWhetherGetPreferredLocaleCodeReturnsAPerfectMatchEvenThoughASimilarMatchWasFound()
    {
        $this->assertEquals(
            'de_AT',
            TranslatorWithHardcodedLocaleCodes::getPreferredLocaleCode('de,de_AT;q=0.5'),
            'Translator::getPreferredLocaleCode does not return a perfect '
            . 'match if a similar match with higher priority was found'
        );
    }

    /**
     * @depends testWhetherSplitLocaleCodeInterpretsInvalidLocaleCodesAsLanguageCodes
     */
    public function testWhetherGetPreferredLocaleCodeReturnsASimilarMatchIfNoPerfectMatchCouldBeFound()
    {
        $this->assertEquals(
            'de_DE',
            TranslatorWithHardcodedLocaleCodes::getPreferredLocaleCode('de,en'),
            'Translator::getPreferredLocaleCode does not return the most preferred similar match'
        );
    }

    /**
     * @depends testWhetherSplitLocaleCodeSplitsValidLocalesCorrectly
     */
    public function testWhetherGetPreferredLocaleCodeReturnsTheDefaultLocaleIfNoMatchCouldBeFound()
    {
        $this->assertEquals(
            Translator::DEFAULT_LOCALE,
            TranslatorWithHardcodedLocaleCodes::getPreferredLocaleCode('fr_FR,jp_JP'),
            'Translator::getPreferredLocaleCode does not return the default locale if no match could be found'
        );
    }

    /**
     * @depends testWhetherSetupLocaleSetsUpTheGivenLocale
     */
    public function testWhetherTranslatePluralReturnsTheSingularForm()
    {
        Translator::setupLocale('de_DE');

        $result = Translator::translatePlural('test service', 'test services', 1, 'icingatest');

        $expected = 'test dienst';

        $this->assertEquals(
            $expected,
            $result,
            'Translator::translatePlural() could not return the translated singular form'
        );
    }

    /**
     * @depends testWhetherSetupLocaleSetsUpTheGivenLocale
     */
    public function testWhetherTranslatePluralReturnsThePluralForm()
    {
        Translator::setupLocale('de_DE');

        $result = Translator::translatePlural('test service', 'test services', 2, 'icingatest');

        $expected = 'test dienste';

        $this->assertEquals(
            $expected,
            $result,
            'Translator::translatePlural() could not return the translated plural form'
        );
    }

    /**
     * @depends testWhetherSetupLocaleSetsUpTheGivenLocale
     */
    public function testWhetherTranslateReturnsTheContextForm()
    {
        Translator::setupLocale('de_DE');

        $result = Translator::translate('context service', 'icingatest', 'test2');

        $expected = 'context dienst test2';

        $this->assertEquals(
            $expected,
            $result,
            'Translator::translate() could not return the translated context form'
        );
    }

    /**
     * @depends testWhetherSetupLocaleSetsUpTheGivenLocale
     */
    public function testWhetherTranslatePluralReturnsTheContextForm()
    {
        Translator::setupLocale('de_DE');

        $result = Translator::translatePlural('context service', 'context services', 3, 'icingatest', 'test-context');

        $expected = 'context plural dienste';

        $this->assertEquals(
            $expected,
            $result,
            'Translator::translatePlural() could not return the translated context form'
        );
    }
}
