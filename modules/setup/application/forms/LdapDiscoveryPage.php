<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

namespace Icinga\Module\Setup\Forms;

use Icinga\Web\Form;
use Icinga\Forms\LdapDiscoveryForm;
use Icinga\Protocol\Ldap\Discovery;
use Icinga\Module\Setup\Forms\LdapDiscoveryConfirmPage;

/**
 * Wizard page to define the connection details for a LDAP resource
 */
class LdapDiscoveryPage extends Form
{
    /**
     * @var Discovery
     */
    private $discovery;

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
                'value'         => $this->translate('LDAP Discovery', 'setup.page.title'),
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
                'value' => $this->translate(
                    'You can use this page to discover LDAP or ActiveDirectory servers ' .
                    ' for authentication. If you don\' want to execute a discovery, just skip this step.'
                )
            )
        );

        $discoveryForm = new LdapDiscoveryForm();
        $this->addElements($discoveryForm->createElements($formData)->getElements());
        $this->getElement('domain')->setRequired(
            isset($formData['skip_validation']) === false || ! $formData['skip_validation']
        );

        $this->addElement(
            'checkbox',
            'skip_validation',
            array(
                'required'      => true,
                'label'         => $this->translate('Skip'),
                'description'   => $this->translate('Do not discover LDAP servers and enter all settings manually.')
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
        if ($data['skip_validation']) {
            return true;
        }

        if (isset($data['domain'])) {
            $this->discovery = Discovery::discoverDomain($data['domain']);
            if ($this->discovery->isSuccess()) {
                return true;
            }
        }
        $this->addError(
            sprintf($this->translate('Could not find any LDAP servers on the domain "%s".'), $data['domain'])
        );
        return false;
    }

    /**
     * Suggest settings based on the underlying discovery
     *
     * @param bool $suppressArrayNotation
     *
     * @return array|null
     */
    public function getValues($suppressArrayNotation = false)
    {
        if (! isset($this->discovery) || ! $this->discovery->isSuccess()) {
            return null;
        }
        $disc = $this->discovery;
        return array(
            'domain' => $this->getValue('domain'),
            'type' => $disc->isAd() ? LdapDiscoveryConfirmPage::TYPE_AD : LdapDiscoveryConfirmPage::TYPE_MISC,
            'resource' => $disc->suggestResourceSettings(),
            'backend' => $disc->suggestBackendSettings()
        );
    }
}
