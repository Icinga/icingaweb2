<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Form\Config\Authentication;

use Icinga\Test\BaseTestCase;
use Icinga\Form\Config\Authentication\BaseBackendForm;

class BackendForm extends BaseBackendForm
{
    public $is_valid;

    public function getConfig()
    {
        // Need to be declared as being abstract otherwise
    }

    public function isValidAuthenticationBackend()
    {
        return $this->is_valid;
    }
}

class BaseBackendFormTest extends BaseTestCase
{
    public function testIsForceCreationCheckboxBeingAdded()
    {
        $form = new BackendForm();
        $form->is_valid = false;

        $this->assertFalse($form->isValid(array()));
        $this->assertNotNull(
            $form->getElement('backend_force_creation'),
            'Checkbox to force a backend\'s creation is not being added though the backend is invalid'
        );
    }

    public function testIsForceCreationCheckboxNotBeingAdded()
    {
        $form = new BackendForm();
        $form->is_valid = true;

        $this->assertTrue($form->isValid(array()));
        $this->assertNull(
            $form->getElement('backend_force_creation'),
            'Checkbox to force a backend\'s creation is being added though the backend is valid'
        );
    }

    public function testIsTheFormValidIfForceCreationTrue()
    {
        $form = new BackendForm();
        $form->is_valid = false;

        $this->assertTrue(
            $form->isValid(array('backend_force_creation' => 1)),
            'BaseBackendForm with invalid backend is not valid though force creation is set'
        );
    }
}
