<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Command\Object;

use Icinga\Module\Monitoring\Command\Object\DeleteDowntimeCommand;
use \Icinga\Module\Monitoring\Forms\Command\CommandForm;
use Icinga\Web\Notification;

/**
 * Form for deleting host or service downtimes
 */
class DeleteDowntimesCommandForm extends CommandForm
{
    /**
     * The downtimes to delete on success
     *
     * @var array
     */
    protected $downtimes;
    
    /**
     * (non-PHPDoc)
     * @see \Zend_Form::init() For the method documentation.
     */
    public function init()
    {
        $this->setAttrib('class', 'inline');
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::createElements() For the method documentation.
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
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::getSubmitLabel() For the method documentation.
     */
    public function getSubmitLabel()
    {
        return $this->translatePlural('Remove', 'Remove All', count($this->downtimes));
    }
    
    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::onSuccess() For the method documentation.
     */
    public function onSuccess()
    {
        foreach ($this->downtimes as $downtime) {
            $delDowntime = new DeleteDowntimeCommand();
            $delDowntime->setDowntimeId($downtime->id);
            $delDowntime->setIsService(isset($downtime->service_description));
            $this->getTransport($this->request)->send($delDowntime);
        }
        $redirect = $this->getElement('redirect')->getValue();
        if (! empty($redirect)) {
            $this->setRedirectUrl($redirect);
        }
        Notification::success($this->translate('Deleting downtime.'));
        return true;
    }
    
    /**
     * Set the downtimes to be deleted upon success
     *
     * @param type $downtimes
     *
     * @return $this
     */
    public function setDowntimes(array $downtimes)
    {
        $this->downtimes = $downtimes;
        return $this;
    }
}
