<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Form\Config;

use \DateTimeZone;
use \DOMDocument;
use \Zend_Config;
use \Zend_View;
use \Zend_View_Helper_DateFormat;
use Icinga\Util\DateTimeFactory;
use Icinga\Test\BaseTestCase;

class GeneralFormTest extends BaseTestCase
{
    private function isHiddenElement($value, $htmlString)
    {
        $html = new DOMDocument();
        $html->loadHTML($htmlString);
        $hidden = $html->getElementsByTagName('noscript');

        foreach ($hidden as $node) {
            foreach ($node->childNodes as $child) {
                if ($child->hasAttributes() === false) {
                    continue;
                }
                if (strpos($child->attributes->getNamedItem('id')->value, $value . '-element') !== false) {
                    return true;
                }
            }
        }
        return false;
    }
    /**
     *
     */
    public function testCorrectFieldPopulation()
    {
        DateTimeFactory::setConfig(array('timezone' => new DateTimeZone('UTC')));
        $form = $this->createForm('Icinga\Form\Config\GeneralForm');
        $form->setDateFormatter(new Zend_View_Helper_DateFormat($this->getRequest()));
        $form->setConfiguration(
            new Zend_Config(
                array(
                    'global' => array(
                        'environment'       => 'development',
                        'timezone'          => 'Europe/Berlin',
                        'indexModule'       => 'monitoring',
                        'indexController'   => 'dashboard',
                        'moduleFolder'      => '/my/module/path',
                        'dateFormat'        => 'd-m/Y',
                        'timeFormat'        => 'A:i'
                    ),
                    'preferences' => array(
                        'type'              => 'ini',
                        'configPath'        => './my/path'
                    )
                )
            )
        );
        $form->setResources(
            array(
                'db'    => array(
                    'type' => 'db'
                )
            )
        );
        $form->setConfigDir('/tmp');

        $form->create();
        $this->assertEquals(
            1,
            $form->getValue('environment'),
            'Asserting the checkbox for devlopment being set to true'
        );
        $this->assertEquals(
            'Europe/Berlin',
            $form->getValue('timezone'),
            'Asserting the correct timezone to be displayed'
        );
        $this->assertEquals(
            '/my/module/path',
            $form->getValue('module_folder'),
            'Asserting the correct module folder to be set'
        );
        $this->assertEquals(
            'd-m/Y',
            $form->getValue('date_format'),
            'Asserting the correct data format to be set'
        );
        $this->assertEquals(
            'A:i',
            $form->getValue('time_format'),
            'Asserting the correct time to be set'
        );
        $this->assertEquals(
            'ini',
            $form->getValue('preferences_type'),
            'Asserting the correct preference type to be set'
        );
        $this->assertEquals(
            './my/path',
            $form->getValue('preferences_ini_path'),
            'Asserting the correct ini path to be set'
        );
        $this->assertEquals(
            '',
            $form->getValue('preferences_db_resource'),
            'Asserting the database resource not to be set'
        );
    }

    public function testCorrectConditionalIniFieldRendering()
    {
        DateTimeFactory::setConfig(array('timezone' => new DateTimeZone('UTC')));
        $form = $this->createForm('Icinga\Form\Config\GeneralForm');
        $form->setDateFormatter(new Zend_View_Helper_DateFormat($this->getRequest()));
        $form->setConfiguration(
            new Zend_Config(
                array(
                    'preferences' => array(
                        'type'              => 'ini',
                        'configPath'        => './my/path'
                    )
                )
            )
        );
        $form->setConfigDir('/tmp');
        $form->setResources(
            array(
                'db'    => array(
                    'type' => 'db'
                )
            )
        );
        $form->create();

        $view = new Zend_View();

        $this->assertFalse(
            $this->isHiddenElement('preferences_ini_path', $form->render($view)),
            "Asserting the ini path field to be displayed when an ini preference is set"
        );
        $this->assertTrue(
            $this->isHiddenElement('preferences_db_resource', $form->render($view)),
            "Asserting the db resource to be hidden when an ini preference is set"
        );
    }

    public function testCorrectConditionalDbFieldRendering()
    {
        DateTimeFactory::setConfig(array('timezone' => new DateTimeZone('UTC')));
        $form = $this->createForm('Icinga\Form\Config\GeneralForm');
        $form->setDateFormatter(new Zend_View_Helper_DateFormat($this->getRequest()));
        $form->setConfiguration(
            new Zend_Config(
                array(
                    'preferences' => array(
                        'type'              => 'db',
                        'configPath'        => './my/path',
                        'resource'          => 'my_resource'
                    )
                )
            )
        );
        $form->setConfigDir('/tmp');
        $form->setResources(
            array(
                'db'    => array(
                    'type' => 'db'
                )
            )
        );
        $form->create();
        $view = new Zend_View();

        $this->assertTrue(
            $this->isHiddenElement('preferences_ini_path', $form->render($view)),
            "Asserting the ini path field to be hidden when db preference is set"
        );
        $this->assertFalse(
            $this->isHiddenElement('preferences_ini_resource', $form->render($view)),
            "Asserting the db resource to be displayed when db preference is set"
        );
    }
}
