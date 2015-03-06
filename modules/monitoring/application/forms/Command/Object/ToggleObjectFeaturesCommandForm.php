<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

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
        $this->setUseFormAutosubmit();
        $this->setTitle('Feature Commands');
        $this->setAttrib('class', 'inline object-features');
        $this->loadDefaultDecorators()->getDecorator('description')->setTag('h4');
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::createElements() For the method documentation.
     */
    public function createElements(array $formData = array())
    {
        $toggleDisabled = $this->hasPermission('monitoring/command/feature/instance')  ? null : '';
        $this->addElements(array(
            array(
                'checkbox',
                ToggleObjectFeatureCommand::FEATURE_ACTIVE_CHECKS,
                array(
                    'label'         => $this->translate('Active Checks'),
                    'autosubmit'    => true,
                    'disabled'      => $toggleDisabled
                )
            ),
            array(
                'checkbox',
                ToggleObjectFeatureCommand::FEATURE_PASSIVE_CHECKS,
                array(
                    'label'         => $this->translate('Passive Checks'),
                    'autosubmit'    => true,
                    'disabled'      => $toggleDisabled
                )
            ),
            array(
                'checkbox',
                ToggleObjectFeatureCommand::FEATURE_OBSESSING,
                array(
                    'label'         => $this->translate('Obsessing'),
                    'autosubmit'    => true,
                    'disabled'      => $toggleDisabled
                )
            ),
            array(
                'checkbox',
                ToggleObjectFeatureCommand::FEATURE_NOTIFICATIONS,
                array(
                    'label'         => $this->translate('Notifications'),
                    'autosubmit'    => true,
                    'disabled'      => $toggleDisabled
                )
            ),
            array(
                'checkbox',
                ToggleObjectFeatureCommand::FEATURE_EVENT_HANDLER,
                array(
                    'label'         => $this->translate('Event Handler'),
                    'autosubmit'    => true,
                    'disabled'      => $toggleDisabled
                )
            ),
            array(
                'checkbox',
                ToggleObjectFeatureCommand::FEATURE_FLAP_DETECTION,
                array(
                    'label'         => $this->translate('Flap Detection'),
                    'autosubmit'    => true,
                    'disabled'      => $toggleDisabled
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
        $this->assertPermission('monitoring/command/feature/object');
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
