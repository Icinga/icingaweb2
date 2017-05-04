<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Announcement;

use DateTime;
use Icinga\Authentication\Auth;
use Icinga\Data\Filter\Filter;
use Icinga\Forms\RepositoryForm;

/**
 * Create, update and delete announcements
 */
class AnnouncementForm extends RepositoryForm
{
    /**
     * {@inheritDoc}
     */
    protected function createInsertElements(array $formData)
    {
        $this->addElement(
            'text',
            'author',
            array(
                'disabled'  => true,
                'required'  => true,
                'value'     => Auth::getInstance()->getUser()->getUsername()
            )
        );
        $this->addElement(
            'textarea',
            'message',
            array(
                'description'   => $this->translate('The message to display to users'),
                'label'         => $this->translate('Message'),
                'required'      => true
            )
        );
        $this->addElement(
            'dateTimePicker',
            'start',
            array(
                'description'   => $this->translate('The time to display the announcement from'),
                'label'         => $this->translate('Start'),
                'placeholder'   => new DateTime('tomorrow'),
                'required'      => true
            )
        );
        $this->addElement(
            'dateTimePicker',
            'end',
            array(
                'description'   => $this->translate('The time to display the announcement until'),
                'label'         => $this->translate('End'),
                'placeholder'   => new DateTime('tomorrow +1day'),
                'required'      => true
            )
        );

        $this->setTitle($this->translate('Create a new announcement'));
        $this->setSubmitLabel($this->translate('Create'));
    }
    /**
     * {@inheritDoc}
     */
    protected function createUpdateElements(array $formData)
    {
        $this->createInsertElements($formData);
        $this->setTitle(sprintf($this->translate('Edit announcement %s'), $this->getIdentifier()));
        $this->setSubmitLabel($this->translate('Save'));
    }

    /**
     * {@inheritDoc}
     */
    protected function createDeleteElements(array $formData)
    {
        $this->setTitle(sprintf($this->translate('Remove announcement %s?'), $this->getIdentifier()));
        $this->setSubmitLabel($this->translate('Yes'));
    }

    /**
     * {@inheritDoc}
     */
    protected function createFilter()
    {
        return Filter::where('id', $this->getIdentifier());
    }

    /**
     * {@inheritDoc}
     */
    protected function getInsertMessage($success)
    {
        return $success
            ? $this->translate('Announcement created')
            : $this->translate('Failed to create announcement');
    }

    /**
     * {@inheritDoc}
     */
    protected function getUpdateMessage($success)
    {
        return $success
            ? $this->translate('Announcement updated')
            : $this->translate('Failed to update announcement');
    }

    /**
     * {@inheritDoc}
     */
    protected function getDeleteMessage($success)
    {
        return $success
            ? $this->translate('Announcement removed')
            : $this->translate('Failed to remove announcement');
    }
}
