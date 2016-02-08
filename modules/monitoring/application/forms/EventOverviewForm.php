<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms;

use Icinga\Web\Url;
use Icinga\Web\Form;
use Icinga\Data\Filter\Filter;

/**
 * Configure the filter for the event overview
 */
class EventOverviewForm extends Form
{
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->setName('form_event_overview');
        $this->setDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'div', 'class' => 'hbox')),
            'Form'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function createElements(array $formData)
    {
        $decorators = array(
            array('Label', array('class' => 'optional')),
            'ViewHelper',
            array('HtmlTag', array('tag' => 'div', 'class' => 'hbox-item optionbox')),
        );

        $url = Url::fromRequest()->getAbsoluteUrl();
        $this->addElement(
            'checkbox',
            'statechange',
            array(
                'label' => $this->translate('State Changes'),
                'class' => 'autosubmit',
                'decorators' => $decorators,
                'value' => strpos($url, $this->stateChangeFilter()->toQueryString()) === false ? 0 : 1
            )
        );
        $this->addElement(
            'checkbox',
            'downtime',
            array(
                'label' => $this->translate('Downtimes'),
                'class' => 'autosubmit',
                'decorators' => $decorators,
                'value' => strpos($url, $this->downtimeFilter()->toQueryString()) === false ? 0 : 1
            )
        );
        $this->addElement(
            'checkbox',
            'comment',
            array(
                'label' => $this->translate('Comments'),
                'class' => 'autosubmit',
                'decorators' => $decorators,
                'value' => strpos($url, $this->commentFilter()->toQueryString()) === false ? 0 : 1
            )
        );
        $this->addElement(
            'checkbox',
            'notification',
            array(
                'label' => $this->translate('Notifications'),
                'class' => 'autosubmit',
                'decorators' => $decorators,
                'value' => strpos($url, $this->notificationFilter()->toQueryString()) === false ? 0 : 1
            )
        );
        $this->addElement(
            'checkbox',
            'flapping',
            array(
                'label' => $this->translate('Flapping'),
                'class' => 'autosubmit',
                'decorators' => $decorators,
                'value' => strpos($url, $this->flappingFilter()->toQueryString()) === false ? 0 : 1
            )
        );
    }

    /**
     * Return the corresponding filter-object
     *
     * @returns Filter
     */
    public function getFilter()
    {
        $filters = array();
        if ($this->getValue('statechange', 1)) {
            $filters[] = $this->stateChangeFilter();
        }
        if ($this->getValue('comment', 1)) {
            $filters[] = $this->commentFilter();
        }
        if ($this->getValue('notification', 1)) {
            $filters[] = $this->notificationFilter();
        }
        if ($this->getValue('downtime', 1)) {
            $filters[] = $this->downtimeFilter();
        }
        if ($this->getValue('flapping', 1)) {
            $filters[] = $this->flappingFilter();
        }
        return Filter::matchAny($filters);
    }

    public function stateChangeFilter()
    {
        return Filter::matchAny(
            Filter::expression('type', '=', 'hard_state'),
            Filter::expression('type', '=', 'soft_state')
        );
    }

    public function commentFilter()
    {
        return Filter::matchAny(
            Filter::expression('type', '=', 'comment'),
            Filter::expression('type', '=', 'comment_deleted'),
            Filter::expression('type', '=', 'dt_comment'),
            Filter::expression('type', '=', 'dt_comment_deleted'),
            Filter::expression('type', '=', 'ack')
        );
    }

    public function notificationFilter()
    {
        return Filter::expression('type', '=', 'notify');
    }

    public function downtimeFilter()
    {
        return Filter::matchAny(
            Filter::expression('type', '=', 'downtime_start'),
            Filter::expression('type', '=', 'downtime_end')
        );
    }

    public function flappingFilter()
    {
        return Filter::matchAny(
            Filter::expression('type', '=', 'flapping'),
            Filter::expression('type', '=', 'flapping_deleted')
        );
    }
}
