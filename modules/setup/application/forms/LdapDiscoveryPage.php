<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Setup\Form;

use Icinga\Web\Form;
use Icinga\Forms\LdapDiscoveryForm;

/**
 * Wizard page to define the connection details for a LDAP resource
 */
class LdapDiscoveryPage extends Form
{
    /**
     * @var LdapDiscoveryForm
     */
    private $discoveryForm;

    /**
     * Initialize this page
     */
    public function init()
    {
        $this->setName('setup_ldap_discovery');
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'note',
            'title',
            array(
                'value'         => mt('setup', 'LDAP Discovery', 'setup.page.title'),
                'decorators'    => array(
                    'ViewHelper',
                    array('HtmlTag', array('tag' => 'h2'))
                )
            )
        );
        $this->addElement(
            'note',
            'description',
            array(
                'value' => mt(
                    'setup',
                    'You can use this page to discover LDAP or ActiveDirectory servers ' .
                    ' for authentication. If you don\' want to execute a discovery, just skip this step.'
                )
            )
        );

        $this->discoveryForm = new LdapDiscoveryForm();
        $this->addElements($this->discoveryForm->createElements($formData)->getElements());
        $this->getElement('domain')->setRequired(
            isset($formData['skip_validation']) === false || ! $formData['skip_validation']
        );

        $this->addElement(
            'checkbox',
            'skip_validation',
            array(
                'required'      => true,
                'label'         => mt('setup', 'Skip'),
                'description'   => mt('setup', 'Do not discover LDAP servers and enter all settings manually.')
            )
        );
    }

    /**
     * Validate the given form data and check whether a BIND-request is successful
     *
     * @param   array   $data   The data to validate
     *
     * @return  bool
     */
    public function isValid($data)
    {
        if (false === parent::isValid($data)) {
            return false;
        }

        if (! $data['skip_validation'] && false === $this->discoveryForm->isValid($data)) {
            return false;
        }

        return true;
    }

    public function getValues($suppressArrayNotation = false)
    {
        if (! isset($this->discoveryForm) || ! $this->discoveryForm->hasSuggestion()) {
            return null;
        }
        return array(
            'domain' => $this->getValue('domain'),
            'type' => $this->discoveryForm->isAd() ?
                    LdapDiscoveryConfirmPage::TYPE_AD : LdapDiscoveryConfirmPage::TYPE_MISC,
            'resource' => $this->discoveryForm->suggestResourceSettings(),
            'backend' => $this->discoveryForm->suggestBackendSettings()
        );
    }
}
