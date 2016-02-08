<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Navigation;

use Icinga\Data\Filter\Filter;
use Icinga\Exception\QueryException;
use Icinga\Forms\Navigation\NavigationItemForm;

class ActionForm extends NavigationItemForm
{
    /**
     * {@inheritdoc}
     */
    public function createElements(array $formData)
    {
        parent::createElements($formData);

        $this->addElement(
            'text',
            'filter',
            array(
                'allowEmpty'    => true,
                'label'         => $this->translate('Filter'),
                'description'   => $this->translate(
                    'Display this action only for objects matching this filter. Leave it blank'
                    . ' if you want this action being displayed regardless of the object'
                )
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($formData)
    {
        if (! parent::isValid($formData)) {
            return false;
        }

        if (($filterString = $this->getValue('filter')) !== null) {
            $filter = Filter::matchAll();
            $filter->setAllowedFilterColumns(array(
                'host_name',
                'hostgroup_name',
                'instance_name',
                'service_description',
                'servicegroup_name',
                'contact_name',
                'contactgroup_name',
                function ($c) {
                    return preg_match('/^_(?:host|service)_/', $c);
                }
            ));

            try {
                $filter->addFilter(Filter::fromQueryString($filterString));
            } catch (QueryException $_) {
                $this->getElement('filter')->addError(sprintf(
                    $this->translate('Invalid filter provided. You can only use the following columns: %s'),
                    implode(', ', array(
                        'instance_name',
                        'host_name',
                        'hostgroup_name',
                        'service_description',
                        'servicegroup_name',
                        'contact_name',
                        'contactgroup_name',
                        '_(host|service)_<customvar-name>'
                    ))
                ));
                return false;
            }
        }

        return true;
    }
}
