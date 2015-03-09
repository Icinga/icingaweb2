<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Command\Instance;

use Icinga\Module\Monitoring\Command\Instance\ToggleInstanceFeatureCommand;
use Icinga\Module\Monitoring\Forms\Command\CommandForm;
use Icinga\Web\Notification;

/**
 * Form for enabling or disabling features of Icinga objects, i.e. hosts or services
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
        $this->setTitle($this->translate('Feature Commands'));
        $this->setAttrib('class', 'inline instance-features');
        $this->loadDefaultDecorators()->getDecorator('description')->setTag('h2');
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
        if ((bool) $this->status->notifications_enabled) {
            if ($this->hasPermission('monitoring/command/feature/instance')) {
                $notificationDescription = sprintf(
                    '<a aria-label="%1$s" title="%1$s" href="%2$s" data-base-target="_next">%3$s</a>',
                    $this->translate('Disable notifications for a specific time on a program-wide basis'),
                    $this->getView()->href('monitoring/process/disable-notifications'),
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
        } else {
            $notificationDescription = null;
        }
        $toggleDisabled = $this->hasPermission('monitoring/command/feature/instance') ? null : '';
        $this->addElements(array(
            array(
                'checkbox',
                ToggleInstanceFeatureCommand::FEATURE_ACTIVE_HOST_CHECKS,
                array(
                    'label'         =>  $this->translate('Active Host Checks Being Executed'),
                    'autosubmit'    => true,
                    'disabled'      => $toggleDisabled
                )
            ),
            array(
                'checkbox',
                ToggleInstanceFeatureCommand::FEATURE_ACTIVE_SERVICE_CHECKS,
                array(
                    'label'         =>  $this->translate('Active Service Checks Being Executed'),
                    'autosubmit'    => true,
                    'disabled'      => $toggleDisabled
                )
            ),
            array(
                'checkbox',
                ToggleInstanceFeatureCommand::FEATURE_EVENT_HANDLERS,
                array(
                    'label'         => $this->translate('Event Handlers Enabled'),
                    'autosubmit'    => true,
                    'disabled'      => $toggleDisabled
                )
            ),
            array(
                'checkbox',
                ToggleInstanceFeatureCommand::FEATURE_FLAP_DETECTION,
                array(
                    'label'         => $this->translate('Flap Detection Enabled'),
                    'autosubmit'    => true,
                    'disabled'      => $toggleDisabled
                )
            ),
            array(
                'checkbox',
                ToggleInstanceFeatureCommand::FEATURE_NOTIFICATIONS,
                array(
                    'label'         => $this->translate('Notifications Enabled'),
                    'autosubmit'    => true,
                    'description'   => $notificationDescription,
                    'decorators'    => array(
                        'ViewHelper',
                        'Errors',
                        array(
                            'Description',
                            array('tag' => 'span', 'class' => 'description', 'escape' => false)
                        ),
                        'Label',
                        array('HtmlTag', array('tag' => 'div'))
                    ),
                    'disabled'      => $toggleDisabled
                )
            ),
            array(
                'checkbox',
                ToggleInstanceFeatureCommand::FEATURE_HOST_OBSESSING,
                array(
                    'label'         => $this->translate('Obsessing Over Hosts'),
                    'autosubmit'    => true,
                    'disabled'      => $toggleDisabled
                )
            ),
            array(
                'checkbox',
                ToggleInstanceFeatureCommand::FEATURE_SERVICE_OBSESSING,
                array(
                    'label'         => $this->translate('Obsessing Over Services'),
                    'autosubmit'    => true,
                    'disabled'      => $toggleDisabled
                )
            ),
            array(
                'checkbox',
                ToggleInstanceFeatureCommand::FEATURE_PASSIVE_HOST_CHECKS,
                array(
                    'label'         =>  $this->translate('Passive Host Checks Being Accepted'),
                    'autosubmit'    => true,
                    'disabled'      => $toggleDisabled
                )
            ),
            array(
                'checkbox',
                ToggleInstanceFeatureCommand::FEATURE_PASSIVE_SERVICE_CHECKS,
                array(
                    'label'         =>  $this->translate('Passive Service Checks Being Accepted'),
                    'autosubmit'    => true,
                    'disabled'      => $toggleDisabled
                )
            ),
            array(
                'checkbox',
                ToggleInstanceFeatureCommand::FEATURE_PERFORMANCE_DATA,
                array(
                    'label'         =>  $this->translate('Performance Data Being Processed'),
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
        foreach ($this->getValues() as $feature => $enabled) {
            $toggleFeature = new ToggleInstanceFeatureCommand();
            $toggleFeature
                ->setFeature($feature)
                ->setEnabled($enabled);
            $this->getTransport($this->request)->send($toggleFeature);
        }
        Notification::success($this->translate('Toggling feature..'));
        return true;
    }
}
