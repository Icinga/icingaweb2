<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Command\Object;

use Icinga\Module\Monitoring\Command\Object\ToggleObjectFeatureCommand;
use Icinga\Module\Monitoring\Object\MonitoredObject;
use Icinga\Web\Notification;
use Icinga\Web\Request;

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
                    'label'         => mt('monitoring', 'Active Checks'),
                    'autosubmit'    => true
                )
            ),
            array(
                'checkbox',
                ToggleObjectFeatureCommand::FEATURE_PASSIVE_CHECKS,
                array(
                    'label'         => mt('monitoring', 'Passive Checks'),
                    'autosubmit'    => true
                )
            ),
            array(
                'checkbox',
                ToggleObjectFeatureCommand::FEATURE_OBSESSING,
                array(
                    'label'         => mt('monitoring', 'Obsessing'),
                    'autosubmit'    => true
                )
            ),
            array(
                'checkbox',
                ToggleObjectFeatureCommand::FEATURE_NOTIFICATIONS,
                array(
                    'label'         => mt('monitoring', 'Notifications'),
                    'autosubmit'    => true
                )
            ),
            array(
                'checkbox',
                ToggleObjectFeatureCommand::FEATURE_EVENT_HANDLER,
                array(
                    'label'         => mt('monitoring', 'Event Handler'),
                    'autosubmit'    => true
                )
            ),
            array(
                'checkbox',
                ToggleObjectFeatureCommand::FEATURE_FLAP_DETECTION,
                array(
                    'label'         => mt('monitoring', 'Flap Detection'),
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
                $element->setDescription(mt('monitoring', 'changed'));
            }
        }
        return $this;
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::onSuccess() For the method documentation.
     */
    public function onSuccess(Request $request)
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
                    $this->getTransport($request)->send($toggleFeature);
                }
            }
        }
        Notification::success(mt('monitoring', 'Toggling feature..'));
        return true;
    }
}
