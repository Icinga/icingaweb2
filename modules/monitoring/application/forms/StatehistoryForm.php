<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms;

use Icinga\Web\Form;
use Icinga\Data\Filter\Filter;

/**
 * Configure the filter for the event grid
 */
class StatehistoryForm extends Form
{
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->setName('form_event_overview');
        $this->setSubmitLabel($this->translate('Apply'));
    }

    /**
     * Return the corresponding filter-object
     *
     * @returns Filter
     */
    public function getFilter()
    {
        $baseFilter = Filter::matchAny(
            Filter::expression('type', '=', 'hard_state')
        );

        if ($this->getValue('objecttype', 'hosts') === 'hosts') {
            $objectTypeFilter = Filter::expression('object_type', '=', 'host');
        } else {
            $objectTypeFilter = Filter::expression('object_type', '=', 'service');
        }

        $states = array(
            'cnt_down_hard'         => Filter::expression('state', '=', '1'),
            'cnt_unreachable_hard'  => Filter::expression('state', '=', '2'),
            'cnt_up'                => Filter::expression('state', '=', '0'),
            'cnt_critical_hard'     => Filter::expression('state', '=', '2'),
            'cnt_warning_hard'      => Filter::expression('state', '=', '1'),
            'cnt_unknown_hard'      => Filter::expression('state', '=', '3'),
            'cnt_ok'                => Filter::expression('state', '=', '0')
        );
        $state = $this->getValue('state', 'cnt_critical_hard');
        $stateFilter =  $states[$state];
        if (in_array($state, array('cnt_ok', 'cnt_up'))) {
            return Filter::matchAll($objectTypeFilter, $stateFilter);
        }
        return Filter::matchAll($baseFilter, $objectTypeFilter, $stateFilter);
    }

    /**
     * {@inheritdoc}
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'select',
            'from',
            array(
                'label' => $this->translate('From'),
                'value' => $this->getRequest()->getParam('from', strtotime('3 months ago')),
                'multiOptions' => array(
                    strtotime('midnight 3 months ago') => $this->translate('3 Months'),
                    strtotime('midnight 4 months ago') => $this->translate('4 Months'),
                    strtotime('midnight 8 months ago') => $this->translate('8 Months'),
                    strtotime('midnight 12 months ago') => $this->translate('1 Year'),
                    strtotime('midnight 24 months ago') => $this->translate('2 Years')
                )
            )
        );
        $this->addElement(
            'select',
            'to',
            array(
                'label' => $this->translate('To'),
                'value' => $this->getRequest()->getParam('to', time()),
                'multiOptions' => array(
                    time() => $this->translate('Today')
                )
            )
        );

        $objectType = $this->getRequest()->getParam('objecttype', 'services');
        $this->addElement(
            'select',
            'objecttype',
            array(
                'label' => $this->translate('Object type'),
                'value' => $objectType,
                'multiOptions' => array(
                    'services' => $this->translate('Services'),
                    'hosts' => $this->translate('Hosts')
                )
            )
        );
        if ($objectType === 'services') {
            $serviceState = $this->getRequest()->getParam('state', 'cnt_critical_hard');
            if (in_array($serviceState, array('cnt_down_hard', 'cnt_unreachable_hard', 'cnt_up'))) {
                $serviceState = 'cnt_critical_hard';
            }
            $this->addElement(
                'select',
                'state',
                array(
                    'label' => $this->translate('State'),
                    'value' => $serviceState,
                    'multiOptions' => array(
                        'cnt_critical_hard' => $this->translate('Critical'),
                        'cnt_warning_hard' => $this->translate('Warning'),
                        'cnt_unknown_hard' => $this->translate('Unknown'),
                        'cnt_ok' => $this->translate('Ok')
                    )
                )
            );
        } else {
            $hostState = $this->getRequest()->getParam('state', 'cnt_down_hard');
            if (in_array($hostState, array('cnt_ok', 'cnt_critical_hard', 'cnt_warning', 'cnt_unknown'))) {
                $hostState = 'cnt_down_hard';
            }
            $this->addElement(
                'select',
                'state',
                array(
                    'label' => $this->translate('State'),
                    'value' => $hostState,
                    'multiOptions' =>  array(
                        'cnt_up' => $this->translate('Up'),
                        'cnt_down_hard' => $this->translate('Down'),
                        'cnt_unreachable_hard' => $this->translate('Unreachable')
                    )
                )
            );
        }
    }
}
