<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Util;

use Exception;
use Icinga\Test\BaseTestCase;
use Icinga\Util\Translator;

class TranslatorTest extends BaseTestCase
{
    public function setUp()
    {
        parent::setUp();
        Translator::registerDomain('icingatest', BaseTestCase::$testDir . '/res/locale');
    }

    public function testWhetherGetAvailableLocaleCodesReturnsAllAvailableLocaleCodes()
    {
        $this->assertEquals(
            array('de_DE', 'fr_FR'),
            Translator::getAvailableLocaleCodes(),
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
     * @expectedException \Exception
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
}
