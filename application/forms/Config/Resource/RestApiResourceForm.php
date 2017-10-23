<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config\Resource;

use ErrorException;
use Icinga\Web\Form;
use Icinga\Web\Form\Validator\RestApiUrlValidator;
use Icinga\Web\Url;

/**
 * Form class for adding/modifying ReST API resources
 */
class RestApiResourceForm extends Form
{
    public function init()
    {
        $this->setName('form_config_resource_restapi');
    }

    public function createElements(array $formData)
    {
        $this->addElement(
            'text',
            'name',
            array(
                'required'      => true,
                'label'         => $this->translate('Resource Name'),
                'description'   => $this->translate('The unique name of this resource')
            )
        );

        $this->addElement(
            'text',
            'baseurl',
            array(
                'label'         => $this->translate('Base URL'),
                'description'   => $this->translate('http[s]://<HOST>[:<PORT>][/<BASE_LOCATION>]'),
                'required'      => true,
                'validators'    => array(new RestApiUrlValidator())
            )
        );

        $this->addElement(
            'text',
            'username',
            array(
                'label'         => $this->translate('Username'),
                'description'   => $this->translate(
                    'A user with access to the above URL via HTTP basic authentication'
                )
            )
        );

        $this->addElement(
            'password',
            'password',
            array(
                'label'         => $this->translate('Password'),
                'description'   => $this->translate('The above user\'s password')
            )
        );

        $tlsClientIdentities = array(
            // TODO
        );

        if (empty($tlsClientIdentities)) {
            $this->addElement(
                'note',
                'tls_client_identities_missing',
                array(
                    'label'         => $this->translate('TLS Client Identity'),
                    'description'   => $this->translate('TLS X509 client certificate with its private key (PEM)'),
                    'escape'        => false,
                    'value'         => sprintf(
                        $this->translate(
                            'There aren\'t any TLS client identities you could choose from, but you can %sadd some%s.'
                        ),
                        sprintf(
                            '<a data-base-target="_next" href="#" title="%s" class="highlighted">', // TODO
                            $this->translate('Add TLS client identity')
                        ),
                        '</a>'
                    )
                )
            );
        } else {
            $this->addElement(
                'select',
                'tls_client_identity',
                array(
                    'label'         => $this->translate('TLS Client Identity'),
                    'description'   => $this->translate('TLS X509 client certificate with its private key (PEM)'),
                    'multiOptions'  => array_merge(
                        array('' => $this->translate('(none)')),
                        $tlsClientIdentities
                    ),
                    'value'         => ''
                )
            );
        }

        // TODO: remote TLS cert chain discovery

        return $this;
    }

    public function isValid($formData)
    {
        if (! parent::isValid($formData)) {
            return false;
        }

        if (Url::fromPath($this->getValue('baseurl'))->getScheme() === 'https') {
            $serverTlsCertChain = $this->fetchServerTlsCertChain();
            if ($serverTlsCertChain === false) {
                return false;
            }

            // TODO: remote TLS cert chain review
        }

        return true;
    }

    /**
     * Try to fetch the remote's TLS certificate chain
     *
     * @return string[]|false
     */
    protected function fetchServerTlsCertChain()
    {
        $tlsOpts = array(
            'verify_peer'               => false,
            'verify_peer_name'          => false,
            'capture_peer_cert_chain'   => true
        );

        if ($this->getValue('tls_client_identity') !== null) {
            $tlsOpts['local_cert'] = null; // TODO
        }

        $errno = null;
        $errstr = null;
        $context = stream_context_create(array('ssl' => $tlsOpts));
        $baseurl = Url::fromPath($this->getValue('baseurl'));
        $port = $baseurl->getPort();

        try {
            fclose(stream_socket_client(
                'tls://' . $baseurl->getHost() . ':' . (
                    $port === null ? $baseurl->getScheme() === 'https' ? '443' : '80' : $port
                ),
                $errno,
                $errstr,
                ini_get('default_socket_timeout'),
                STREAM_CLIENT_CONNECT,
                $context
            ));
        } catch (ErrorException $e) {
            $this->addError($e->getMessage());
            return false;
        }

        $certs = array();
        $params = stream_context_get_params($context);
        foreach ($params['options']['ssl']['peer_certificate_chain'] as $index => $cert) {
            $certs[$index] = null;
            openssl_x509_export($cert, $certs[$index]);
        }

        return $certs;
    }
}
