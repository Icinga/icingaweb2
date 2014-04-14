<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Form\Preference;

// @codingStandardsIgnoreStart
require_once realpath(ICINGA_APPDIR . '/views/helpers/DateFormat.php');
// @codingStandardsIgnoreEnd

use \Zend_View_Helper_DateFormat;
use Icinga\Test\BaseTestCase;
use Icinga\User\Preferences;

/**
 * Test for general form, mainly testing enable/disable behaviour
 */
class GeneralFormTest extends BaseTestCase
{
    /**
     * Test whether fields using the default values have input disabled
     */
    public function testDisableFormIfUsingDefault()
    {
        $form = $this->createForm('Icinga\Form\Preference\GeneralForm');
        $form->setDateFormatter(new Zend_View_Helper_DateFormat($this->getRequest()));
        $form->setRequest($this->getRequest());
        $form->create();
        $this->assertSame(
            1,
            $form->getElement('timezone')->getAttrib('disabled'),
            'Asserting form elements to be disabled when not set in a preference'
        );
    }

    /**
     *  Test whether fields with preferences are enabled
     */
    public function testEnableFormIfUsingPreference()
    {
        $form = $this->createForm('Icinga\Form\Preference\GeneralForm');
        $form->setDateFormatter(new Zend_View_Helper_DateFormat($this->getRequest()));
        $form->setRequest($this->getRequest());
        $form->setUserPreferences(
            new Preferences(
                array(
                    'app.timezone' => 'Europe/Berlin'
                )
            )
        );
        $form->create();
        $this->assertSame(
            null,
            $form->getElement('timezone')->getAttrib('disabled'),
            'Asserting form elements to be disabled when not set in a preference'
        );
    }
}
