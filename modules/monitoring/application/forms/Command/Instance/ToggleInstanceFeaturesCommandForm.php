<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Command\Instance;

use Icinga\Module\Monitoring\Command\Instance\ToggleInstanceFeatureCommand;
use Icinga\Module\Monitoring\Forms\Command\CommandForm;
use Icinga\Web\Notification;

/**
 * Form for enabling or disabling features of Icinga instances
 */
class ToggleInstanceFeaturesCommandForm extends CommandForm
{
    /**
     * Instance status
     *
     * @var object
     */
    protected $status;

    /**
     * (non-PHPDoc)
     * @see \Zend_Form::init() For the method documentation.
     */
    public function init()
    {
        $this->setUseFormAutosubmit();
        $this->setAttrib('class', 'inline instance-features');
    }

    /**
     * Set the instance status
     *
     * @param   object $status
     *
     * @return  $this
     */
    public function setStatus($status)
    {
        $this->status = (object) $status;
        return $this;
    }

    /**
     * Get the instance status
     *
     * @return object
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::createElements() For the method documentation.
     */
    public function createElements(array $formData = array())
    {
        $notificationDescription = null;
        $isIcinga2 = $this->getBackend()->isIcinga2($this->status->program_version);

        if (! $isIcinga2) {
            if ((bool) $this->status->notifications_enabled) {
                if ($this->hasPermission('monitoring/command/feature/instance')) {
                    $notificationDescription = sprintf(
                        '<a aria-label="%1$s" class="action-link" title="%1$s" href="%2$s" data-base-target="_next">%3$s</a>',
                        $this->translate('Disable notifications for a specific time on a program-wide basis'),
                        $this->getView()->href('monitoring/health/disable-notifications'),
                        $this->translate('Disable temporarily')
                    );
                } else {
                    $notificationDescription = null;
                }
            } elseif ($this->status->disable_notif_expire_time) {
                $notificationDescription = sprintf(
                    $this->translate('Notifications will be re-enabled in <strong>%s</strong>'),
                    $this->getView()->timeUntil($this->status->disable_notif_expire_time)
                );
            }
        }

        $toggleDisabled = $this->hasPermission('monitoring/command/feature/instance') ? null : '';

        $this->addElement(
            'checkbox',
            ToggleInstanceFeatureCommand::FEATURE_ACTIVE_HOST_CHECKS,
            array(
                'label'         =>  $this->translate('Active Host Checks'),
                'autosubmit'    => true,
                'disabled'      => $toggleDisabled
            )
        );
        $this->addElement(
            'checkbox',
            ToggleInstanceFeatureCommand::FEATURE_ACTIVE_SERVICE_CHECKS,
            array(
                'label'         =>  $this->translate('Active Service Checks'),
                'autosubmit'    => true,
                'disabled'      => $toggleDisabled
            )
        );
        $this->addElement(
            'checkbox',
            ToggleInstanceFeatureCommand::FEATURE_EVENT_HANDLERS,
            array(
                'label'         => $this->translate('Event Handlers'),
                'autosubmit'    => true,
                'disabled'      => $toggleDisabled
            )
        );
        $this->addElement(
            'checkbox',
            ToggleInstanceFeatureCommand::FEATURE_FLAP_DETECTION,
            array(
                'label'         => $this->translate('Flap Detection'),
                'autosubmit'    => true,
                'disabled'      => $toggleDisabled
            )
        );
        $this->addElement(
            'checkbox',
            ToggleInstanceFeatureCommand::FEATURE_NOTIFICATIONS,
            array(
                'label'         => $this->translate('Notifications'),
                'autosubmit'    => true,
                'description'   => $notificationDescription,
                'decorators'    => array(
                    array('Label', array('tag'=>'span', 'separator' => '', 'class' => 'control-label')),
                    array(
                        'Description',
                        array('tag' => 'span', 'class' => 'description', 'escape' => false)
                    ),
                    array(array('labelWrap' => 'HtmlTag'), array('tag' => 'div', 'class' => 'control-label-group')),
                    array('ViewHelper', array('separator' => '')),
                    array('Errors', array('separator' => '')),
                    array('HtmlTag', array('tag' => 'div', 'class' => 'control-group'))
                ),
                'disabled'      => $toggleDisabled
            )
        );

        if (! $isIcinga2) {
            $this->addElement(
                'checkbox',
                ToggleInstanceFeatureCommand::FEATURE_HOST_OBSESSING,
                array(
                    'label'         => $this->translate('Obsessing Over Hosts'),
                    'autosubmit'    => true,
                    'disabled'      => $toggleDisabled
                )
            );
            $this->addElement(
                'checkbox',
                ToggleInstanceFeatureCommand::FEATURE_SERVICE_OBSESSING,
                array(
                    'label'         => $this->translate('Obsessing Over Services'),
                    'autosubmit'    => true,
                    'disabled'      => $toggleDisabled
                )
            );
            $this->addElement(
                'checkbox',
                ToggleInstanceFeatureCommand::FEATURE_PASSIVE_HOST_CHECKS,
                array(
                    'label'         =>  $this->translate('Passive Host Checks'),
                    'autosubmit'    => true,
                    'disabled'      => $toggleDisabled
                )
            );
            $this->addElement(
                'checkbox',
                ToggleInstanceFeatureCommand::FEATURE_PASSIVE_SERVICE_CHECKS,
                array(
                    'label'         =>  $this->translate('Passive Service Checks'),
                    'autosubmit'    => true,
                    'disabled'      => $toggleDisabled
                )
            );
        }

        $this->addElement(
            'checkbox',
            ToggleInstanceFeatureCommand::FEATURE_PERFORMANCE_DATA,
            array(
                'label'         =>  $this->translate('Performance Data'),
                'autosubmit'    => true,
                'disabled'      => $toggleDisabled
            )
        );
    }

    /**
     * Load feature status
     *
     * @param   object $instanceStatus
     *
     * @return  $this
     */
    public function load($instanceStatus)
    {
        $this->create();
        foreach ($this->getValues() as $feature => $enabled) {
            $this->getElement($feature)->setChecked($instanceStatus->{$feature});
        }

        return $this;
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::onSuccess() For the method documentation.
     */
    public function onSuccess()
    {
        $this->assertPermission('monitoring/command/feature/instance');

        $notifications = array(
            ToggleInstanceFeatureCommand::FEATURE_ACTIVE_HOST_CHECKS => array(
                $this->translate('Enabling active host checks..'),
                $this->translate('Disabling active host checks..')
            ),
            ToggleInstanceFeatureCommand::FEATURE_ACTIVE_SERVICE_CHECKS => array(
                $this->translate('Enabling active service checks..'),
                $this->translate('Disabling active service checks..')
            ),
            ToggleInstanceFeatureCommand::FEATURE_EVENT_HANDLERS => array(
                $this->translate('Enabling event handlers..'),
                $this->translate('Disabling event handlers..')
            ),
            ToggleInstanceFeatureCommand::FEATURE_FLAP_DETECTION => array(
                $this->translate('Enabling flap detection..'),
                $this->translate('Disabling flap detection..')
            ),
            ToggleInstanceFeatureCommand::FEATURE_NOTIFICATIONS => array(
                $this->translate('Enabling notifications..'),
                $this->translate('Disabling notifications..')
            ),
            ToggleInstanceFeatureCommand::FEATURE_HOST_OBSESSING => array(
                $this->translate('Enabling obsessing over hosts..'),
                $this->translate('Disabling obsessing over hosts..')
            ),
            ToggleInstanceFeatureCommand::FEATURE_SERVICE_OBSESSING => array(
                $this->translate('Enabling obsessing over services..'),
                $this->translate('Disabling obsessing over services..')
            ),
            ToggleInstanceFeatureCommand::FEATURE_PASSIVE_HOST_CHECKS => array(
                $this->translate('Enabling passive host checks..'),
                $this->translate('Disabling passive host checks..')
            ),
            ToggleInstanceFeatureCommand::FEATURE_PASSIVE_SERVICE_CHECKS => array(
                $this->translate('Enabling passive service checks..'),
                $this->translate('Disabling passive service checks..')
            ),
            ToggleInstanceFeatureCommand::FEATURE_PERFORMANCE_DATA => array(
                $this->translate('Enabling performance data..'),
                $this->translate('Disabling performance data..')
            )
        );

        foreach ($this->getValues() as $feature => $enabled) {
            if ((bool) $this->status->{$feature} !== (bool) $enabled) {
                $toggleFeature = new ToggleInstanceFeatureCommand();
                $toggleFeature
                    ->setFeature($feature)
                    ->setEnabled($enabled);
                $this->getTransport($this->request)->send($toggleFeature);

                Notification::success(
                    $notifications[$feature][$enabled ? 0 : 1]
                );
            }
        }

        return true;
    }
}
