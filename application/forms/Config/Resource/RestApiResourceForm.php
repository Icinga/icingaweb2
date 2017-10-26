<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config\Resource;

use DateTime;
use DateTimeZone;
use ErrorException;
use Icinga\Util\TimezoneDetect;
use Icinga\Web\Form;
use Icinga\Web\Form\Validator\RestApiUrlValidator;
use Icinga\Web\Form\Validator\TlsCertValidator;
use Icinga\Web\Url;
use Zend_Form_Element;
use Zend_Form_Element_Checkbox;
use Zend_Form_Element_Hidden;

/**
 * Form class for adding/modifying ReST API resources
 */
class RestApiResourceForm extends Form
{
    /**
     * Not neccessarily present checkboxes
     *
     * @var string[]
     */
    protected $optionalCheckboxes = array(
        'force_creation',
        'tls_server_insecure',
        'tls_server_discover_rootca',
        'tls_server_accept_rootca',
        'tls_server_accept_cn'
    );

    /**
     * Form elements which have to be above all others, in this order
     *
     * @var string[]
     */
    protected $priorizedElements = array(
        'force_creation',
        'tls_server_insecure',
        'tls_server_discover_rootca',
        'tls_server_rootca_info',
        'tls_server_accept_rootca',
        'tls_server_accept_cn'
    );

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
                    'ignore'        => true,
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

        $this->ensureOnlyCheckboxes(array_intersect($this->optionalCheckboxes, array_keys($formData)));

        if (isset($formData['tls_server_rootca_cert'])) {
            $this->addRootCaCertCache();
        }

        return $this->priorizeElements();
    }

    /**
     * Ensure that only the given checkboxes are present
     *
     * @return $this
     */
    protected function ensureOnlyCheckboxes(array $checkboxes = array())
    {
        foreach (array_diff($this->optionalCheckboxes, $checkboxes) as $checkbox) {
            $this->removeElement($checkbox);
        }

        foreach ($checkboxes as $checkbox) {
            $element = $this->getElement($checkbox);

            if ($element === null) {
                switch ($checkbox) {
                    case 'force_creation':
                        $this->addElement('checkbox', 'force_creation', array(
                            'ignore'        => true,
                            'label'         => $this->translate('Force Changes'),
                            'description'   => $this->translate(
                                'Check this box to enforce changes without connectivity validation'
                            )
                        ));
                        break;

                    case 'tls_server_insecure':
                        $this->addElement('checkbox', 'tls_server_insecure', array(
                            'label'         => $this->translate('Insecure Connection'),
                            'description'   => $this->translate(
                                'Don\'t validate the remote\'s TLS certificate chain at all'
                            )
                        ));
                        break;

                    case 'tls_server_discover_rootca':
                        $this->addElement('checkbox', 'tls_server_discover_rootca', array(
                            'ignore'        => true,
                            'label'         => $this->translate('Discover Root CA'),
                            'description'   => $this->translate(
                                'Discover the remote\'s TLS certificate\'s root CA'
                                    . ' (makes sense only in case of an isolated PKI)'
                            )
                        ));
                        break;

                    case 'tls_server_accept_rootca':
                        $this->addElement('checkbox', 'tls_server_accept_rootca', array(
                            'ignore'        => true,
                            'label'         => $this->translate('Accept the remote\'s root CA'),
                            'description'   => $this->translate('Trust the remote\'s TLS certificate\'s root CA')
                        ));
                        break;

                    case 'tls_server_accept_cn':
                        $this->addElement('checkbox', 'tls_server_accept_cn', array(
                            'ignore'        => true,
                            'label'         => $this->translate('Accept the remote\'s CN'),
                            'description'   => $this->translate('Accept the remote\'s TLS certificate\'s CN')
                        ));
                        break;
                }
            }
        }

        return $this;
    }

    /**
     * Add form element with the given TLS root CA certificate's info
     *
     * @param   array   $cert
     */
    protected function addRootCaInfo($cert)
    {
        $timezoneDetect = new TimezoneDetect();
        $timeZone = new DateTimeZone(
            $timezoneDetect->success() ? $timezoneDetect->getTimezoneName() : date_default_timezone_get()
        );
        $view = $this->getView();

        $subject = array();
        foreach ($cert['parsed']['subject'] as $key => $value) {
            $subject[] = $view->escape("$key = " . var_export($value, true));
        }

        $this->addElement(
            'note',
            'tls_server_rootca_info',
            array(
                'ignore'    => true,
                'escape'    => false,
                'label'     => $this->translate('Root CA'),
                'value'     => sprintf(
                    '<table class="name-value-list">' . str_repeat('<tr><td>%s</td><td>%s</td></tr>', 5) . '</table>',
                    $view->escape($this->translate('Subject', 'x509.certificate')),
                    implode('<br>', $subject),
                    $view->escape($this->translate('Valid from', 'x509.certificate')),
                    $view->escape(
                        DateTime::createFromFormat('U', $cert['parsed']['validFrom_time_t'])
                            ->setTimezone($timeZone)
                            ->format(DateTime::ISO8601)
                    ),
                    $view->escape($this->translate('Valid until', 'x509.certificate')),
                    $view->escape(
                        DateTime::createFromFormat('U', $cert['parsed']['validTo_time_t'])
                            ->setTimezone($timeZone)
                            ->format(DateTime::ISO8601)
                    ),
                    $view->escape($this->translate('SHA256 fingerprint', 'x509.certificate')),
                    $view->escape(
                        implode(' ', str_split(strtoupper(openssl_x509_fingerprint($cert['x509'], 'sha256')), 2))
                    ),
                    $view->escape($this->translate('SHA1 fingerprint', 'x509.certificate')),
                    $view->escape(
                        implode(' ', str_split(strtoupper(openssl_x509_fingerprint($cert['x509'], 'sha1')), 2))
                    )
                )
            )
        );
    }

    /**
     * Add and return form element for the discovered TLS root CA certificate
     *
     * @return Zend_Form_Element_Hidden
     */
    protected function addRootCaCertCache()
    {
        $element = $this->getElement('tls_server_rootca_cert');
        if ($element === null) {
            $this->addElement(
                'hidden',
                'tls_server_rootca_cert',
                array('validators' => array(new TlsCertValidator()))
            );

            return $this->getElement('tls_server_rootca_cert');
        }

        return $element;
    }

    /**
     * Reorder form elements as needed
     */
    protected function priorizeElements()
    {
        $priorizedElements = array();
        foreach ($this->priorizedElements as $priorizedElement) {
            $element = $this->getElement($priorizedElement);
            if ($element !== null) {
                $element->setOrder(null);
                $priorizedElements[] = $element;
            }
        }

        $nextOrder = -1;
        foreach ($priorizedElements as $priorizedElement) {
            /** @var Zend_Form_Element $priorizedElement */
            $priorizedElement->setOrder(++$nextOrder);
        }

        foreach ($this->getElements() as $name => $element) {
            $this->_order[$name] = $element->getOrder();
        }

        return $this;
    }

    public function isValid($formData)
    {
        if (! parent::isValid($formData)) {
            return false;
        }

        $result = $this->isEndpointValid();
        $this->priorizeElements();
        return $result;
    }

    /**
     * Return whether the configured endpoint is valid
     *
     * @return bool
     */
    protected function isEndpointValid()
    {
        if ($this->isBoxChecked('force_creation')) {
            return true;
        }

        if (Url::fromPath($this->getValue('baseurl'))->getScheme() === 'https') {
            if (! $this->probeInsecureTlsConnection()) {
                $this->ensureOnlyCheckboxes(array('force_creation'));
                return false;
            }

            if ($this->isBoxChecked('tls_server_insecure')) {
                return true;
            }

            if ($this->isBoxChecked('tls_server_discover_rootca')) {
                $this->removeElement('tls_server_rootca_cert');

                $certs = $this->fetchServerTlsCertChain();
                if ($certs === false) {
                    return false;
                }

                if ($certs['leaf']['parsed']['subject']['CN'] === $certs['leaf']['parsed']['issuer']['CN']) {
                    $this->error($this->translate('The remote didn\'t provide any non-self-signed TLS certificate'));
                    return false;
                }

                if (! isset($certs['root'])) {
                    $this->error($this->translate('The remote didn\'t provide any root CA certificate'));
                    return false;
                }

                $this->ensureOnlyCheckboxes(array(
                    'force_creation',
                    'tls_server_insecure',
                    'tls_server_discover_rootca',
                    'tls_server_accept_rootca'
                ));
                $this->addRootCaInfo($certs['root']);
                $this->addRootCaCertCache()->setValue($certs['root']['x509']);
                $this->getElement('tls_server_discover_rootca')->setValue(null);
                return false;
            }

            $rootCaCert = $this->getValue('tls_server_rootca_cert');

            if (! $this->probeSecureTlsConnection(
                $rootCaCert !== null && $this->isBoxChecked('tls_server_accept_rootca') ? $rootCaCert : null
            )) {
                $checkboxes = array('force_creation', 'tls_server_insecure', 'tls_server_discover_rootca');
                if ($rootCaCert !== null) {
                    $this->addRootCaInfo(array(
                        'x509'      => $rootCaCert,
                        'parsed'    => openssl_x509_parse($rootCaCert),
                    ));
                    $checkboxes[] = 'tls_server_accept_rootca';
                }

                $this->ensureOnlyCheckboxes($checkboxes);
                return false;
            }
        } elseif (! $this->probeTcpConnection()) {
            $this->ensureOnlyCheckboxes(array('force_creation'));
            return false;
        }

        return true;
    }

    /**
     * Return whether a TCP connection to the remote is possible and eventually add form errors
     *
     * @return bool
     */
    protected function probeTcpConnection()
    {
        try {
            fclose(stream_socket_client('tcp://' . $this->getTcpEndpoint()));
        } catch (ErrorException $element) {
            $this->error($element->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Return whether an insecure TLS connection to the remote is possible and eventually add form errors
     *
     * @return bool
     */
    protected function probeInsecureTlsConnection()
    {
        try {
            fclose($this->createTlsStream(stream_context_create($this->includeTlsClientIdentity(array('ssl' => array(
                'verify_peer'       => false,
                'verify_peer_name'  => false
            ))))));
        } catch (ErrorException $element) {
            $this->error($element->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Return whether a secure TLS connection to the remote is possible and eventually add form errors
     *
     * TODO: custom root CA
     *
     * @return bool
     */
    protected function probeSecureTlsConnection()
    {
        try {
            fclose($this->createTlsStream(stream_context_create($this->includeTlsClientIdentity(array()))));
        } catch (ErrorException $element) {
            $this->error($element->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Add the TLS client certificate to use (if any) to the given stream context options and return them
     *
     * @param   array   $contextOptions
     *
     * @return  array
     */
    protected function includeTlsClientIdentity(array $contextOptions)
    {
        if ($this->getValue('tls_client_identity') !== null) {
            $contextOptions['ssl']['local_cert'] = null; // TODO
        }
        
        return $contextOptions;
    }

    /**
     * Create a TLS stream to the remote with the the given stream context 
     *
     * @param   resource    $context
     *
     * @return  resource
     */
    protected function createTlsStream($context)
    {
        return stream_socket_client(
            'tls://' . $this->getTcpEndpoint(),
            $errno,
            $errstr,
            ini_get('default_socket_timeout'),
            STREAM_CLIENT_CONNECT,
            $context
        );
    }

    /**
     * Get <HOST>:<PORT>
     *
     * @return string
     */
    protected function getTcpEndpoint()
    {
        $baseurl = Url::fromPath($this->getValue('baseurl'));
        $port = $baseurl->getPort();

        return $baseurl->getHost() . ':' . ($port === null ? '443' : $port);
    }

    /**
     * Return whether the given checkbox is present and checked
     *
     * @param   string  $name
     *
     * @return  bool
     */
    protected function isBoxChecked($name)
    {
        /** @var Zend_Form_Element_Checkbox $checkbox */
        $checkbox = $this->getElement($name);
        return $checkbox !== null && $checkbox->isChecked();
    }

    /**
     * Try to fetch the remote's TLS certificate chain
     *
     * @return array|false
     */
    protected function fetchServerTlsCertChain()
    {
        $context = stream_context_create($this->includeTlsClientIdentity(array('ssl' => array(
            'verify_peer'               => false,
            'verify_peer_name'          => false,
            'capture_peer_cert_chain'   => true
        ))));

        try {
            fclose($this->createTlsStream($context));
        } catch (ErrorException $e) {
            $this->error($e->getMessage());
            return false;
        }

        $params = stream_context_get_params($context);
        $rawChain = $params['options']['ssl']['peer_certificate_chain'];
        $chain = array('leaf' => array('x509' => null));

        openssl_x509_export(reset($rawChain), $chain['leaf']['x509']);

        if (count($rawChain) > 1) {
            $chain['root'] = array('x509' => null);
            openssl_x509_export(end($rawChain), $chain['root']['x509']);
        }

        foreach ($chain as & $cert) {
            $cert['parsed'] = openssl_x509_parse($cert['x509']);
        }

        if (isset($chain['root'])
            && $chain['root']['parsed']['subject']['CN'] !== $chain['root']['parsed']['issuer']['CN']
        ) {
            unset($chain['root']);
        }

        return $chain;
    }
}
