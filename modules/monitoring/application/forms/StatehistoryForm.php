<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form;

use \Zend_Form;
use Icinga\Web\Form;
use Icinga\Data\Filter\Filter;

/**
 * Configure the filter for the statehistorysummary
 */
class StatehistoryForm extends Form
{
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
     * Create the confirmation form
     *
     * @see Form::create()
     */
    public function create()
    {
        $this->addElement(
            'select',
            'from',
            array(
                'label' => t('From'),
                'value' => $this->getRequest()->getParam('from', strtotime('3 months ago')),
                'multiOptions' => array(
                    strtotime('midnight 3 months ago') => t('3 Months'),
                    strtotime('midnight 4 months ago') => t('4 Months'),
                    strtotime('midnight 8 months ago') => t('8 Months'),
                    strtotime('midnight 12 months ago') => t('1 Year'),
                    strtotime('midnight 24 months ago') => t('2 Years')
                ),
                'class' => 'autosubmit'
            )
        );
        $this->addElement(
            'select',
            'to',
            array(
                'label' => t('To'),
                'value' => $this->getRequest()->getParam('to', time()),
                'multiOptions' => array(
                    time() => t('Today')
                ),
                'class' => 'autosubmit'
            )
        );

        $objectType = $this->getRequest()->getParam('objecttype', 'services');
        $this->addElement(
            'select',
            'objecttype',
            array(
                'label' => t('Object type'),
                'value' => $objectType,
                'multiOptions' => array(
                    'services' => t('Services'),
                    'hosts' => t('Hosts')
                ),
                'class' => 'autosubmit'
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
                    'label' => t('State'),
                    'value' => $serviceState,
                    'multiOptions' => array(
                        'cnt_critical_hard' => t('Critical'),
                        'cnt_warning_hard' => t('Warning'),
                        'cnt_unknown_hard' => t('Unknown'),
                        'cnt_ok' => t('Ok')
                    ),
                    'class' => 'autosubmit'
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
                    'label' => t('State'),
                    'value' => $hostState,
                    'multiOptions' =>  array(
                        'cnt_up' => t('Up'),
                        'cnt_down_hard' => t('Down'),
                        'cnt_unreachable_hard' => t('Unreachable')
                    ),
                    'class' => 'autosubmit'
                )
            );
        }

        $this->enableAutoSubmit(array('from', 'objecttype', 'state'));
        $this->addElement(
            'button',
            'btn_submit',
            array(
                'type'      => 'submit',
                'escape'    => false,
                'value'     => '1',
                'class'     => 'btn btn-cta btn-common',
                'label'     => t('Apply')
            )
        );
    }
}
