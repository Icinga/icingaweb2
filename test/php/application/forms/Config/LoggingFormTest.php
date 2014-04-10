<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Form\Config;

use \Zend_Config;
Use Icinga\Test\BaseTestCase;

/**
 * Test for the authentication provider form
 */
class LoggingFormTest extends BaseTestCase
{
    /**
     * Test the logging form to be correctly populated from configuration
     */
    public function testLoggingFormPopulation()
    {
        $form = $this->createForm('Icinga\Form\Config\LoggingForm');
        $config = new Zend_Config(
            array(
                'logging' => array(
                    'enable'    => 1,
                    'target'    => '/some/path',
                    'verbose'   => 0,
                    'type'      => 'stream',
                    'debug'     => array(
                        'enable'    => 1,
                        'target'    => '/some/debug/path',
                        'type'      => 'stream'
                    )
                )
            )
        );
        $form->setConfiguration($config);
        $form->setBaseDir('basedir');
        $form->create();

        $this->assertEquals(
            '0',
            $form->getValue('logging_app_verbose'),
            'Asserting the logging verbose tick not to be set'
        );
        $this->assertEquals(
            '/some/path',
            $form->getValue('logging_app_target'),
            'Asserting the logging path to be set'
        );
        $this->assertEquals(
            1,
            $form->getValue('logging_debug_enable'),
            'Asserting the debug log enable tick to be set'
        );
        $this->assertEquals(
            '/some/debug/path',
            $form->getValue('logging_debug_target'),
            'Asserting the debug log path to be set'
        );
    }

    /**
     * Test the logging form to create correct modified configurations when submit
     */
    public function testCorrectConfigCreation()
    {
        $form = $this->createForm(
            'Icinga\Form\Config\LoggingForm',
            array(
                'logging_enable'        => 1,
                'logging_app_target'    => 'some/new/target',
                'logging_app_verbose'   => 1,
                'logging_debug_enable'  => 0,
                'logging_debug_target'  => 'a/new/target'
            )
        );
        $baseConfig = new Zend_Config(
            array(
                'global' => array(
                    'option' => 'value'
                ),
                'logging' => array(
                    'enable'    => 1,
                    'target'    => '/some/path',
                    'verbose'   => 0,
                    'type'      => 'stream',
                    'debug'     => array(
                        'enable'    => 1,
                        'target'    => '/some/debug/path',
                        'type'      => 'stream'
                    )
                )
            )
        );
        $form->setConfiguration($baseConfig);
        $form->setBaseDir('basedir');
        $form->create();
        $form->populate($this->getRequest()->getParams());
        $config = $form->getConfig();
        $this->assertEquals(
            'value',
            $config->global->option,
            'Asserting global options not to be altered when changing log'
        );
        $this->assertEquals(
            1,
            $config->logging->enable,
            'Asserting logging to stay enabled when enable is ticked'
        );
        $this->assertEquals(
            'some/new/target',
            $config->logging->target,
            'Asserting target modifications to be applied'
        );
        $this->assertEquals(
            1,
            $config->logging->verbose,
            'Asserting ticking the verbose checkbox to be applied'
        );
        $this->assertEquals(
            'stream',
            $config->logging->type,
            'Asserting the type to stay "stream"'
        );
        $this->assertEquals(
            0,
            $config->logging->debug->enable,
            'Asserting debug log to be disabled'
        );
        $this->assertEquals(
            'a/new/target',
            $config->logging->debug->target,
            'Asserting the debug log target modifications to be applied'
        );
    }
}
