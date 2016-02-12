<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Forms;

use Exception;
use Zend_Validate_NotEmpty;
use Icinga\Exception\IcingaException;
use Icinga\Web\Form;
use Icinga\Web\Form\ErrorLabeller;
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
        $this->setTitle($this->translate('LDAP Discovery', 'setup.page.title'));
        $this->addDescription($this->translate(
            'You can use this page to discover LDAP or ActiveDirectory servers ' .
            ' for authentication. If you don\' want to execute a discovery, just skip this step.'
        ));
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $discoveryForm = new LdapDiscoveryForm();
        $this->addElements($discoveryForm->createElements($formData)->getElements());

        $this->addElement(
            'checkbox',
            'skip_validation',
            array(
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
        if (isset($data['skip_validation']) && $data['skip_validation']) {
            return true;
        }

        if (isset($data['domain']) && $data['domain']) {
            try {
                $this->discovery = Discovery::discoverDomain($data['domain']);
                if ($this->discovery->isSuccess()) {
                    return true;
                }
            } catch (Exception $e) {
                $this->error(sprintf(
                    $this->translate('Could not find any LDAP servers on the domain "%s". An error occurred: %s'),
                    $data['domain'],
                    IcingaException::describe($e)
                ));
            }
        } else {
            $labeller = new ErrorLabeller(array('element' => $this->getElement('domain')));
            $this->getElement('domain')->addError($labeller->translate(Zend_Validate_NotEmpty::IS_EMPTY));
        }

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
