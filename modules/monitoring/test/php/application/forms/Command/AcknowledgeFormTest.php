<?php
// @codingStandardsIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Test\Monitoring\Forms\Command;

require_once realpath('library/Icinga/Web/Form/BaseFormTest.php');
require_once realpath(__DIR__ . '/../../../../../../../modules/monitoring/application/forms/Command/CommandForm.php');
require_once realpath(__DIR__ . '/../../../../../../../modules/monitoring/application/forms/Command/AcknowledgeForm.php');
require_once realpath(__DIR__ . '/../../../../../../../library/Icinga/Util/ConfigAwareFactory.php');
require_once realpath(__DIR__ . '/../../../../../../../library/Icinga/Util/DateTimeFactory.php');

use \DateTimeZone;
use \Monitoring\Form\Command\AcknowledgeForm; // Used by constant FORMCLASS
use \Icinga\Util\DateTimeFactory;
use \Test\Icinga\Web\Form\BaseFormTest;

class AcknowledgeFormTest extends BaseFormTest
{
    const FORMCLASS = 'Monitoring\Form\Command\AcknowledgeForm';

    /**
     * Set up the default time zone
     *
     * Utilizes singleton DateTimeFactory
     *
     * @backupStaticAttributes enabled
     */
    public function setUp()
    {
        date_default_timezone_set('UTC');
        DateTimeFactory::setConfig(array('timezone' => new DateTimeZone('UTC')));
    }

    public function testFormValid()
    {
        $form = $this->getRequestForm(array(
            'author'        => 'Author',
            'comment'       => 'Comment',
            'persistent'    => '0',
            'expire'        => '0',
            'sticky'        => '0',
            'notify'        => '0',
            'btn_submit'    => 'Submit'
        ), self::FORMCLASS);

        $this->assertTrue(
            $form->isSubmittedAndValid(),
            'Legal request data without expire time must be considered valid'
        );

        $formWithExpireTime = $this->getRequestForm(array(
            'author'        => 'Author',
            'comment'       => 'Comment',
            'persistent'    => '0',
            'expire'        => '1',
            'expiretime'    => '10/07/2013 5:32 PM',
            'sticky'        => '0',
            'notify'        => '0',
            'btn_submit'    => 'Submit'
        ), self::FORMCLASS);

        $this->assertTrue(
            $formWithExpireTime->isSubmittedAndValid(),
            'Legal request data with expire time must be considered valid'
        );
    }

    public function testFormInvalidWhenCommentMissing()
    {
        $form = $this->getRequestForm(array(
            'author'        => 'Author',
            'comment'       => '',
            'persistent'    => '0',
            'expire'        => '0',
            'sticky'        => '0',
            'notify'        => '0',
            'btn_submit'    => 'Submit'
        ), self::FORMCLASS);

        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Missing comment must be considered not valid'
        );
    }

    public function testFormInvalidWhenExpireTimeMissingAndExpireSet()
    {
        $form = $this->getRequestForm(array(
            'author'        => 'Author',
            'comment'       => 'Comment',
            'persistent'    => '0',
            'expire'        => '1',
            'sticky'        => '0',
            'notify'        => '0',
            'btn_submit'    => 'Submit'
        ), self::FORMCLASS);

        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'If expire is set and expire time is missing, the form must not be valid'
        );
    }

    public function testFormInvalidWhenExpireTimeIsIncorrectAndExpireSet()
    {
        $form = $this->getRequestForm(array(
            'author'        => 'Author',
            'comment'       => 'Comment',
            'persistent'    => '0',
            'expire'        => '1',
            'expiretime'    => 'Not a date',
            'sticky'        => '0',
            'notify'        => '0',
            'btn_submit'    => 'Submit'
        ), self::FORMCLASS);

        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'If expire is set and expire time is incorrect, the form must not be valid'
        );
    }
}
// @codingStandardsIgnoreStop
