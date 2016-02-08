<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Forms;

use Icinga\Data\ConfigObject;
use Icinga\Web\Form;

/**
 * Wizard page to define the connection details for a LDAP resource
 */
class LdapDiscoveryConfirmPage extends Form
{
    const TYPE_AD = 'MS ActiveDirectory';
    const TYPE_MISC = 'LDAP';

    private $infoTemplate = <<< 'EOT'
<table><tbody>
  <tr><td><strong>Type:</strong></td><td>{type}</td></tr>
  <tr><td><strong>Port:</strong></td><td>{port}</td></tr>
  <tr><td><strong>Root DN:</strong></td><td>{root_dn}</td></tr>
  <tr><td><strong>User Object Class:</strong></td><td>{user_class}</td></tr>
  <tr><td><strong>User Name Attribute:</strong></td><td>{user_attribute}</td></tr>
</tbody></table>
EOT;

    /**
     * The previous configuration
     *
     * @var array
     */
    private $config;

    /**
     * Initialize this page
     */
    public function init()
    {
        $this->setName('setup_ldap_discovery_confirm');
        $this->setTitle($this->translate('LDAP Discovery Results', 'setup.page.title'));
    }

    /**
     * Set the resource configuration to use
     *
     * @param   array   $config
     *
     * @return  $this
     */
    public function setResourceConfig(array $config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * Return the resource configuration as Config object
     *
     * @return  ConfigObject
     */
    public function getResourceConfig()
    {
        return new ConfigObject($this->config);
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $resource = $this->config['resource'];
        $backend = $this->config['backend'];
        $html = $this->infoTemplate;
        $html = str_replace('{type}', $this->config['type'], $html);
        $html = str_replace('{hostname}', $resource['hostname'], $html);
        $html = str_replace('{port}', $resource['port'], $html);
        $html = str_replace('{root_dn}', $resource['root_dn'], $html);
        $html = str_replace('{user_attribute}', $backend['user_name_attribute'], $html);
        $html = str_replace('{user_class}', $backend['user_class'], $html);

        $this->addDescription(sprintf(
            $this->translate('The following directory service has been found on domain "%s".'),
            $this->config['domain']
        ));

        $this->addElement(
            'note',
            'suggestion',
            array(
                'value'         => $html,
                'decorators'    => array(
                    'ViewHelper',
                    array(
                        'HtmlTag', array('tag' => 'div')
                    )
                )
            )
        );

        $this->addElement(
            'checkbox',
            'confirm',
            array(
                'value' => '1',
                'label' => $this->translate('Use this configuration?')
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
        return true;
    }

    public function getValues($suppressArrayNotation = false)
    {
        if ($this->getValue('confirm') === '1') {
            // use configuration
            return $this->config;
        }
        return null;
    }
}
