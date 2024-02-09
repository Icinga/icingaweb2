<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Command\Object;

use Icinga\Module\Monitoring\Command\Object\DeleteDowntimeCommand;
use Icinga\Module\Monitoring\Exception\CommandTransportException;
use Icinga\Module\Monitoring\Forms\Command\CommandForm;
use Icinga\Web\Notification;

/**
 * Form for deleting host or service downtimes
 */
class DeleteDowntimesCommandForm extends CommandForm
{
    /**
     * The downtimes to delete
     *
     * @var array
     */
    protected $downtimes;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->setAttrib('class', 'inline');
    }

    /**
     * Set the downtimes to delete
     *
     * @param   iterable $downtimes
     *
     * @return  $this
     */
    public function setDowntimes($downtimes)
    {
        $this->downtimes = $downtimes;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function createElements(array $formData = array())
    {
        $this->addElements(array(
            array(
                'hidden',
                'redirect',
                array('decorators' => array('ViewHelper'))
            )
        ));
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubmitLabel()
    {
        return $this->translatePlural('Remove', 'Remove All', count($this->downtimes));
    }

    /**
     * {@inheritdoc}
     */
    public function onSuccess()
    {
        foreach ($this->downtimes as $downtime) {
            $delDowntime = new DeleteDowntimeCommand();
            $delDowntime
                ->setDowntimeId($downtime->id)
                ->setDowntimeName($downtime->name)
                ->setAuthor($this->Auth()->getUser()->getUsername())
                ->setIsService(isset($downtime->service_description));

            try {
                $this->getTransport($this->request)->send($delDowntime);
            } catch (CommandTransportException $e) {
                // Negative lookahead because there may be messages from other endpoints with different status codes
                if (preg_match('/Can\'t send external Icinga command: (?!404)/', $e->getMessage())) {
                    throw $e;
                }
            }
        }
        $redirect = $this->getElement('redirect')->getValue();
        if (! empty($redirect)) {
            $this->setRedirectUrl($redirect);
        }
        Notification::success(
            $this->translatePlural('Deleting downtime..', 'Deleting downtimes..', count($this->downtimes))
        );
        return true;
    }
}
