<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

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
     * Feature to feature spec map
     *
     * @var string[]
     */
    protected $features;

    /**
     * Feature to feature status map
     *
     * @var int[]
     */
    protected $featureStatus;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->setUseFormAutosubmit();
        $this->setAttrib('class', 'inline object-features');
        $features = array(
            ToggleObjectFeatureCommand::FEATURE_ACTIVE_CHECKS => array(
                'label'         => $this->translate('Active Checks'),
                'permission'    => 'monitoring/command/feature/object/active-checks'
            ),
            ToggleObjectFeatureCommand::FEATURE_PASSIVE_CHECKS => array(
                'label' => $this->translate('Passive Checks'),
                'permission'    => 'monitoring/command/feature/object/passive-checks'
            ),
            ToggleObjectFeatureCommand::FEATURE_OBSESSING => array(
                'label'         => $this->translate('Obsessing'),
                'permission'    => 'monitoring/command/feature/object/obsessing'
            ),
            ToggleObjectFeatureCommand::FEATURE_NOTIFICATIONS => array(
                'label'         => $this->translate('Notifications'),
                'permission'    => 'monitoring/command/feature/object/notifications'
            ),
            ToggleObjectFeatureCommand::FEATURE_EVENT_HANDLER => array(
                'label'         => $this->translate('Event Handler'),
                'permission'    => 'monitoring/command/feature/object/event-handler'
            ),
            ToggleObjectFeatureCommand::FEATURE_FLAP_DETECTION => array(
                'label'         => $this->translate('Flap Detection'),
                'permission'    => 'monitoring/command/feature/object/flap-detection'
            )
        );
        if ($this->getBackend()->isIcinga2()) {
            unset($features[ToggleObjectFeatureCommand::FEATURE_OBSESSING]);
        }
        $this->features = $features;
    }

    /**
     * {@inheritdoc}
     */
    public function createElements(array $formData = array())
    {
        foreach ($this->features as $feature => $spec) {
            $options = array(
                'autosubmit'    => true,
                'disabled'      => $this->hasPermission($spec['permission']) ? null : 'disabled',
                'label'         => $spec['label']
            );
            if ($formData[$feature . '_changed']) {
                $options['description'] = $this->translate('changed');
            }
            if ($formData[$feature] === 2) {
                $options['multiOptions'] = array(
                    $this->translate('disable'),
                    $this->translate('enable'),
                );
                $options['separator'] = '';
                $elementType = 'radio';
            } else {
                $elementType = 'checkbox';
                $options['value'] = $formData[$feature];
            }
            $this->addElement($elementType, $feature, $options);
        }
    }

    /**
     * Load feature status
     *
     * @param   MonitoredObject|object  $object
     *
     * @return  $this
     */
    public function load($object)
    {
        $featureStatus = array();
        foreach (array_keys($this->features) as $feature) {
            $featureStatus[$feature] = $object->{$feature};
            if (isset($object->{$feature . '_changed'})) {
                $featureStatus[$feature . '_changed'] = (bool) $object->{$feature . '_changed'};
            } else {
                $featureStatus[$feature . '_changed'] = false;
            }
        }
        $this->create($featureStatus);
        $this->featureStatus = $featureStatus;

        return $this;
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::onSuccess() For the method documentation.
     */
    public function onSuccess()
    {
        $notifications = array(
            ToggleObjectFeatureCommand::FEATURE_ACTIVE_CHECKS => array(
                $this->translate('Enabling active checks..'),
                $this->translate('Disabling active checks..')
            ),
            ToggleObjectFeatureCommand::FEATURE_PASSIVE_CHECKS => array(
                $this->translate('Enabling passive checks..'),
                $this->translate('Disabling passive checks..')
            ),
            ToggleObjectFeatureCommand::FEATURE_OBSESSING => array(
                $this->translate('Enabling obsessing..'),
                $this->translate('Disabling obsessing..')
            ),
            ToggleObjectFeatureCommand::FEATURE_NOTIFICATIONS => array(
                $this->translate('Enabling notifications..'),
                $this->translate('Disabling notifications..')
            ),
            ToggleObjectFeatureCommand::FEATURE_EVENT_HANDLER => array(
                $this->translate('Enabling event handler..'),
                $this->translate('Disabling event handler..')
            ),
            ToggleObjectFeatureCommand::FEATURE_FLAP_DETECTION => array(
                $this->translate('Enabling flap detection..'),
                $this->translate('Disabling flap detection..')
            )
        );

        foreach ($this->getValues() as $feature => $enabled) {
            if ($this->getElement($feature)->getAttrib('disabled') !== null
                || $enabled === null
                || (int) $enabled === (int) $this->featureStatus[$feature]
            ) {
                continue;
            }
            foreach ($this->objects as $object) {
                /** @var \Icinga\Module\Monitoring\Object\MonitoredObject $object */
                if ((bool) $object->{$feature} !== (bool) $enabled) {
                    $toggleFeature = new ToggleObjectFeatureCommand();
                    $toggleFeature
                        ->setFeature($feature)
                        ->setObject($object)
                        ->setEnabled($enabled);
                    $this->getTransport($this->request)->send($toggleFeature);
                }
            }
            Notification::success(
                $notifications[$feature][$enabled ? 0 : 1]
            );
        }

        return true;
    }
}
