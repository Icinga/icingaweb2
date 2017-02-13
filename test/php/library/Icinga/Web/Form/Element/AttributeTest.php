<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Web\Form\Element;

use Icinga\Web\Form;
use Icinga\Test\BaseTestCase;

class AttributeTest extends BaseTestCase
{
    public function testWhetherDefaultValuesAreStoredCorrectly()
    {
        $form = new Form();
        $form->addElement(
            'text',
            'test',
            array(
                'value' => 'testvalue'
            )
        );

        $this->assertEquals(
            'testvalue',
            $form->getDefaultValue('test'),
            'Form: Default values are not stored correctly'
        );
    }

    /**
     * @depends testWhetherDefaultValuesAreCachedCorrectly
     */
    public function testWhetherDefaultValuesAreIgnoredCorrectlyInMethodGetValues()
    {
        $form = new Form();
        $form->addElement(
            'text',
            'test',
            array(
                'value'         => 'testvalue',
                'ignoreDefault' => true
            )
        );
        $form->addElement(
            'text',
            'test2',
            array(
                'value' => 'testvalue2'
            )
        );

        $values = $form->getValues();
        $this->assertNull(
            $values['test'],
            'Form: getValues does not ignore default values correctly'
        );
        $this->assertEquals(
            'testvalue2',
            $values['test2'],
            'Form: getValues should not ignore default values without attribute ignoreDefault'
        );
    }
}