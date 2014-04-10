<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Form\Preference;

use \DateTimeZone;
use \Zend_View_Helper_DateFormat;
use Icinga\Test\BaseTestCase;
use Icinga\User\Preferences;
use Icinga\Util\DateTimeFactory;

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
        DateTimeFactory::setConfig(array('timezone' => new DateTimeZone('UTC')));
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
        DateTimeFactory::setConfig(array('timezone' => new DateTimeZone('UTC')));
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
