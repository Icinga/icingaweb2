<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Forms\Command\Object;

use Icinga\Module\Monitoring\Command\Object\ToggleObjectFeatureCommand;
use Icinga\Module\Monitoring\Object\MonitoredObject;
use Icinga\Web\Notification;

/**
 * Form for enabling or disabling features of Icinga objects, i.e. hosts or services
 */
class ToggleObjectFeaturesCommandForm extends ObjectsCommandForm
{
    /**
     * (non-PHPDoc)
     * @see \Zend_Form::init() For the method documentation.
     */
    public function init()
    {
        $this->setAttrib('class', 'inline object-features');
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::createElements() For the method documentation.
     */
    public function createElements(array $formData = array())
    {
        $this->addElements(array(
            array(
                'checkbox',
                ToggleObjectFeatureCommand::FEATURE_ACTIVE_CHECKS,
                array(
                    'label'         => $this->translate('Active Checks'),
                    'autosubmit'    => true
                )
            ),
            array(
                'checkbox',
                ToggleObjectFeatureCommand::FEATURE_PASSIVE_CHECKS,
                array(
                    'label'         => $this->translate('Passive Checks'),
                    'autosubmit'    => true
                )
            ),
            array(
                'checkbox',
                ToggleObjectFeatureCommand::FEATURE_OBSESSING,
                array(
                    'label'         => $this->translate('Obsessing'),
                    'autosubmit'    => true
                )
            ),
            array(
                'checkbox',
                ToggleObjectFeatureCommand::FEATURE_NOTIFICATIONS,
                array(
                    'label'         => $this->translate('Notifications'),
                    'autosubmit'    => true
                )
            ),
            array(
                'checkbox',
                ToggleObjectFeatureCommand::FEATURE_EVENT_HANDLER,
                array(
                    'label'         => $this->translate('Event Handler'),
                    'autosubmit'    => true
                )
            ),
            array(
                'checkbox',
                ToggleObjectFeatureCommand::FEATURE_FLAP_DETECTION,
                array(
                    'label'         => $this->translate('Flap Detection'),
                    'autosubmit'    => true
                )
            )
        ));
        return $this;
    }

    /**
     * Load feature status
     *
     * @param   MonitoredObject $object
     *
     * @return  $this
     */
    public function load(MonitoredObject $object)
    {
        $this->create();
        foreach ($this->getValues() as $feature => $enabled) {
            $element = $this->getElement($feature);
            $element->setChecked($object->{$feature});
            if ((bool) $object->{$feature . '_changed'} === true) {
                $element->setDescription($this->translate('changed'));
            }
        }
        return $this;
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::onSuccess() For the method documentation.
     */
    public function onSuccess()
    {
        foreach ($this->objects as $object) {
            /** @var \Icinga\Module\Monitoring\Object\MonitoredObject $object */
            foreach ($this->getValues() as $feature => $enabled) {
                if ((bool) $object->{$feature} !== (bool) $enabled) {
                    $toggleFeature = new ToggleObjectFeatureCommand();
                    $toggleFeature
                        ->setFeature($feature)
                        ->setObject($object)
                        ->setEnabled($enabled);
                    $this->getTransport($this->request)->send($toggleFeature);
                }
            }
        }
        Notification::success($this->translate('Toggling feature..'));
        return true;
    }
}
