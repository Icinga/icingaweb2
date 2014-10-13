<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Setup;

use Icinga\Protocol\Dns;
use Icinga\Web\Form;
use Icinga\Web\Form\Element\Note;
use Icinga\Form\LdapDiscoveryForm;
use Icinga\Form\Config\Resource\LdapResourceForm;
use Icinga\Web\Request;

/**
 * Wizard page to define the connection details for a LDAP resource
 */
class LdapDiscoveryConfirmPage extends Form
{
    const TYPE_AD = 'MS ActiveDirectory';
    const TYPE_MISC = 'LDAP';

    private $infoTemplate = <<< 'EOT'
<br/>
  Found LDAP server on {domain}
<ul>
  <li><b>Type:</b>    {type}</li>
  <li><b>Port:</b>    {port}</li>
  <li><b>Root DN:</b> {root_dn}</li>
  <li><b>User-Class:</b> {user_class}</li>
  <li><b>User-Attribue:</b> {user_attribute}</li>
</ul>
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
    }

    /**
     * Set the resource configuration to use
     *
     * @param   array   $config
     *
     * @return  self
     */
    public function setResourceConfig(array $config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * Return the resource configuration as Zend_Config object
     *
     * @return  Zend_Config
     */
    public function getResourceConfig()
    {
        return new Zend_Config($this->config);
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $resource = $this->config['resource'];
        $backend = $this->config['backend'];
        $html = $this->infoTemplate;
        $html = str_replace('{domain}', $this->config['domain'], $html);
        $html = str_replace('{type}', $this->config['type'], $html);
        $html = str_replace('{hostname}', $resource['hostname'], $html);
        $html = str_replace('{port}', $resource['port'], $html);
        $html = str_replace('{root_dn}', $resource['root_dn'], $html);
        $html = str_replace('{user_attribute}', $backend['user_name_attribute'], $html);
        $html = str_replace('{user_class}', $backend['user_class'], $html);

        $this->addElement(
            new Note(
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
            )
        );

        $this->addElement(
            'checkbox',
            'confirm',
            array(
                'value' => '1',
                'label' => t('Use this configuration?')
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
