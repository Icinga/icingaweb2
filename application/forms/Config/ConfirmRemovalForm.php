<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Config;

use Icinga\Web\Form;

/**
 * Form for confirming removal of an object
 */
class ConfirmRemovalForm extends Form
{
    /**
     * The value of the target to remove
     *
     * @var string
     */
    private $removeTarget;

    /**
     * The name of the target parameter to remove
     *
     * @var string
     */
    private $targetName;

    /**
     * Set the remove target in this field to be a hidden field with $name and value $target
     *
     * @param string $name      The name to be set in the hidden field
     * @param string $target    The value to be set in the hidden field
     */
    public function setRemoveTarget($name, $target)
    {
        $this->targetName = $name;
        $this->removeTarget = $target;
    }

    /**
     * Create the confirmation form
     *
     * @see Form::create()
     */
    public function create()
    {
        $this->setName('form_confirm_removal');
        $this->addElement(
            'hidden',
            $this->targetName,
            array(
                'value'     => $this->removeTarget,
                'required'  => true
            )
        );

        $this->addElement(
            'button',
            'btn_submit',
            array(
                'type'      => 'submit',
                'escape'    => false,
                'value'     => '1',
                'class'     => 'btn btn-cta btn-common',
                'label'     => '<i class="icinga-icon-remove"></i> Confirm Removal'
            )
        );
    }
}
