<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Navigation;

use Icinga\Web\Form;
use Icinga\Web\Url;

class NavigationItemForm extends Form
{
    /**
     * Whether to create a select input to choose a parent for a navigation item of a particular type
     *
     * @var bool
     */
    protected $requiresParentSelection = false;

    /**
     * Return whether to create a select input to choose a parent for a navigation item of a particular type
     *
     * @return  bool
     */
    public function requiresParentSelection()
    {
        return $this->requiresParentSelection;
    }

    /**
     * {@inheritdoc}
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'select',
            'target',
            array(
                'allowEmpty'    => true,
                'label'         => $this->translate('Target'),
                'description'   => $this->translate('The target where to open this navigation item\'s url'),
                'multiOptions'  => array(
                    '_blank'    => $this->translate('New Window'),
                    '_next'     => $this->translate('New Column'),
                    '_main'     => $this->translate('Single Column'),
                    '_self'     => $this->translate('Current Column')
                )
            )
        );

        $this->addElement(
            'text',
            'url',
            array(
                'allowEmpty'    => true,
                'label'         => $this->translate('Url'),
                'description'   => $this->translate(
                    'The url of this navigation item. Leave blank if you only want the'
                    . ' name being displayed. For external urls, make sure to prepend'
                    . ' an appropriate protocol identifier (e.g. http://example.tld)'
                )
            )
        );

        $this->addElement(
            'text',
            'icon',
            array(
                'allowEmpty'    => true,
                'label'         => $this->translate('Icon'),
                'description'   => $this->translate(
                    'The icon of this navigation item. Leave blank if you do not want a icon being displayed'
                )
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getValues($suppressArrayNotation = false)
    {
        $values = parent::getValues($suppressArrayNotation);
        if (isset($values['url']) && $values['url']) {
            $url = Url::fromPath($values['url']);
            if (! $url->isExternal() && ($relativePath = $url->getRelativeUrl())) {
                $values['url'] = $relativePath;
            }
        }

        return $values;
    }
}
