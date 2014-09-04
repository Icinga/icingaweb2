<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Command\Instance;

use Icinga\Module\Monitoring\Form\Command\CommandForm;
use Icinga\Web\Notification;
use Icinga\Web\Request;

/**
 * Base class for forms enabling/disabling features of an Icinga instance
 */
abstract class ToggleFeatureCommandForm extends CommandForm
{
    /**
     * @var string
     */
    protected $feature;

    /**
     * @var string
     */
    protected $label;

    /**
     * Get the command which is to be sent to an Icinga instance
     *
     * @return \Icinga\Module\Monitoring\Command\ToggleFeature
     */
    abstract public function getCommand();

    /**
     * Set the feature the form enables or disables
     *
     * @param   string $feature
     * @param   string $label
     *
     * @return  $this
     */
    public function setFeature($feature, $label)
    {
        $this->feature = $feature;
        $this->label = $label;
        return $this;
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::createElements() For the method documentation.
     */
    public function createElements(array $formData = array())
    {
        if (isset($formData[$this->feature])) {
            $enabled = (bool) $formData[$this->feature];
        } else {
            $enabled = (bool) $this->backend
                ->select()
                ->from(
                    'programstatus',
                    array($this->feature)
                )
                ->getQuery()
                ->fetchRow();
        }
        $this->addElement(
            'checkbox',
            $this->feature,
            array(
                'label'         => $this->label,
                'autosubmit'    => $this->inline,
                'value'         => $enabled
            )
        );
        return $this;
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::onSuccess() For the method documentation.
     */
    public function onSuccess(Request $request)
    {
        $toggleFeature = $this->getCommand();
        (bool) $request->getParam($this->feature) === true ? $toggleFeature->enable() : $toggleFeature->disable();
        $this->getTransport($request)->send($toggleFeature);
        Notification::success(mt('monitoring', 'Command sent'));
        return true;
    }
}
