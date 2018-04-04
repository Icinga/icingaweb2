<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

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
            'textarea',
            'url',
            array(
                'allowEmpty'    => true,
                'label'         => $this->translate('Url'),
                'description'   => $this->translate(
                    'The url of this navigation item. Leave blank if only the name should be displayed.'
                    . ' For urls with username and password and for all external urls,'
                    . ' make sure to prepend an appropriate protocol identifier (e.g. http://example.tld)'
                ),
                'validators'    => array(
                    array(
                        'Callback',
                        false,
                        array(
                            'callback' => function ($url) {
                                // Matches if the given url contains obviously
                                // a username but not any protocol identifier
                                return !preg_match('#^((?=[^/@]).)+@.*$#', $url);
                            },
                            'messages' => array(
                                'callbackValue' => $this->translate(
                                    'Missing protocol identifier'
                                )
                            )
                        )
                    )
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
        // The regex here specifically matches the port-macro as it's the only one preventing Url::fromPath() from
        // successfully parsing the given url. Any other macro such as for the scheme or host simply gets identified
        // as path which is just fine in this case.
        if (isset($values['url']) && $values['url'] && !preg_match('~://.+:\d*?(\$.+\$)~', $values['url'])) {
            $url = Url::fromPath($values['url']);
            if ($url->getBasePath() === $this->getRequest()->getBasePath()) {
                $values['url'] = $url->getRelativeUrl();
            } else {
                $values['url'] = $url->getAbsoluteUrl();
            }
        }

        return $values;
    }
}
