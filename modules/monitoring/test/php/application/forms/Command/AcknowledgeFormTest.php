<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Test\Monitoring\Forms\Command;

require_once realpath(__DIR__ . '/../../../../../../../library/Icinga/Test/BaseTestCase.php');

use Icinga\Test\BaseTestCase;

require_once BaseTestCase::$moduleDir . '/monitoring/application/forms/Command/CommandForm.php';
require_once BaseTestCase::$moduleDir . '/monitoring/application/forms/Command/AcknowledgeForm.php';
require_once BaseTestCase::$libDir .  '/Util/ConfigAwareFactory.php';
require_once BaseTestCase::$libDir .  '/Util/DateTimeFactory.php';

use \DateTimeZone;
use Icinga\Util\DateTimeFactory;

class AcknowledgeFormTest extends BaseTestCase
{
    const FORM_CLASS = 'Icinga\Module\Monitoring\Form\Command\AcknowledgeForm';

    /**
     * Set DateTimeFactory's time zone to UTC
     *
     * Utilizes singleton DateTimeFactory
     *
     * @backupStaticAttributes enabled
     */
    public function setUp()
    {
        DateTimeFactory::setConfig(array('timezone' => new DateTimeZone('UTC')));
    }

    public function testFormValid()
    {
        $form = $this->createForm(
            self::FORM_CLASS,
            array(
                'author'        => 'Author',
                'comment'       => 'Comment',
                'persistent'    => '0',
                'expire'        => '0',
                'sticky'        => '0',
                'notify'        => '0',
                'btn_submit'    => 'Submit'
            )
        );
        $this->assertTrue(
            $form->isSubmittedAndValid(),
            'Legal request data without expire time must be considered valid'
        );

        $formWithExpireTime = $this->createForm(
            self::FORM_CLASS,
            array(
                'author'        => 'Author',
                'comment'       => 'Comment',
                'persistent'    => '0',
                'expire'        => '1',
                'expiretime'    => '10/07/2013 5:32 PM',
                'sticky'        => '0',
                'notify'        => '0',
                'btn_submit'    => 'Submit'
            )
        );
        $this->assertTrue(
            $formWithExpireTime->isSubmittedAndValid(),
            'Legal request data with expire time must be considered valid'
        );
    }

    public function testFormInvalidWhenCommentMissing()
    {
        $form = $this->createForm(
            self::FORM_CLASS,
            array(
                'author'        => 'Author',
                'comment'       => '',
                'persistent'    => '0',
                'expire'        => '0',
                'sticky'        => '0',
                'notify'        => '0',
                'btn_submit'    => 'Submit'
            )
        );
        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Missing comment must be considered not valid'
        );
    }

    public function testFormInvalidWhenExpireTimeMissingAndExpireSet()
    {
        $form = $this->createForm(
            self::FORM_CLASS,
            array(
                'author'        => 'Author',
                'comment'       => 'Comment',
                'persistent'    => '0',
                'expire'        => '1',
                'sticky'        => '0',
                'notify'        => '0',
                'btn_submit'    => 'Submit'
            )
        );
        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'If expire is set and expire time is missing, the form must not be valid'
        );
    }

    public function testFormInvalidWhenExpireTimeIsIncorrectAndExpireSet()
    {
        $form = $this->createForm(
            self::FORM_CLASS,
            array(
                'author'        => 'Author',
                'comment'       => 'Comment',
                'persistent'    => '0',
                'expire'        => '1',
                'expiretime'    => 'Not a date',
                'sticky'        => '0',
                'notify'        => '0',
                'btn_submit'    => 'Submit'
            )
        );
        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'If expire is set and expire time is incorrect, the form must not be valid'
        );
    }
}
