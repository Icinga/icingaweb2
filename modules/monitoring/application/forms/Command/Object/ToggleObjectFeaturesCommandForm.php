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
     * Feature to label map
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
     * (non-PHPDoc)
     * @see \Zend_Form::init() For the method documentation.
     */
    public function init()
    {
        $this->setUseFormAutosubmit();
        $this->setAttrib('class', 'inline object-features');

        $features = array(
            ToggleObjectFeatureCommand::FEATURE_ACTIVE_CHECKS => $this->translate('Active Checks'),
            ToggleObjectFeatureCommand::FEATURE_PASSIVE_CHECKS => $this->translate('Passive Checks'),
            ToggleObjectFeatureCommand::FEATURE_OBSESSING => $this->translate('Obsessing'),
            ToggleObjectFeatureCommand::FEATURE_NOTIFICATIONS => $this->translate('Notifications'),
            ToggleObjectFeatureCommand::FEATURE_EVENT_HANDLER => $this->translate('Event Handler'),
            ToggleObjectFeatureCommand::FEATURE_FLAP_DETECTION => $this->translate('Flap Detection')
        );

        if (preg_match('~^v2\.\d+\.\d+.*$~', $this->getIcingaVersion())) {
            unset($features[ToggleObjectFeatureCommand::FEATURE_OBSESSING]);
        }

        $this->features = $features;
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::createElements() For the method documentation.
     */
    public function createElements(array $formData = array())
    {
        $toggleDisabled = $this->hasPermission('monitoring/command/feature/object')  ? null : '';

        foreach ($this->features as $feature => $label) {
            $options = array(
                'autosubmit'    => true,
                'disabled'      => $toggleDisabled,
                'label'         => $label
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
        $this->assertPermission('monitoring/command/feature/object');

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
            if ($enabled === null
                || (int) $enabled === (int) $this->featureStatus[$feature]
            ) {
                // Ignore unchanged features
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

    /**
     * Fetch and return the program version of the current instance
     *
     * @return  string
     */
    protected function getIcingaVersion()
    {
        return $this->getBackend()->select()->from('programstatus', array('program_version'))->fetchOne();
    }
}
