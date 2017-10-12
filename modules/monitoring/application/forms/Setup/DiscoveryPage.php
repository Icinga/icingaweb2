<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Setup;

use DateTime;
use DateTimeZone;
use ErrorException;
use Icinga\Module\Monitoring\Util\TlsClient;
use Icinga\Module\Monitoring\Web\Form\Validator\TlsCertFileValidator;
use Icinga\Module\Monitoring\Web\Form\Validator\TlsCertValidator;
use Icinga\Module\Monitoring\Web\Form\Validator\TlsKeyFileValidator;
use Icinga\Module\Monitoring\Web\Form\Validator\TlsKeyValidator;
use Icinga\Util\TimezoneDetect;
use Icinga\Web\Form;
use Icinga\Web\Form\Element\Note;
use Zend_Form_Element_Checkbox;

class DiscoveryPage extends Form
{
    public function init()
    {
        $this->setName('setup_monitoring_discovery');
        $this->setTitle($this->translate('Monitoring Backend Discovery', 'setup.page.title'));
        $this->addDescription($this->translate(
            'You can use this page to discover all monitoring backend settings.'
                . ' If you don\'t want to execute a discovery, just skip this step.'
        ));
    }

    public function createElements(array $formData)
    {
        $this->addElement(
            'checkbox',
            'skip',
            array(
                'label'         => $this->translate('Skip'),
                'description'   => $this->translate('Do not discover anything and enter all settings manually.')
            )
        );

        $this->addElement(
            'text',
            'host',
            array(
                'label'         => $this->translate('Icinga 2 API Host'),
                'description'   => $this->translate('The host Icinga 2 runs on'),
            )
        );

        $this->addElement(
            'number',
            'port',
            array(
                'label'         => $this->translate('Icinga 2 API Port'),
                'description'   => $this->translate('The port the Icinga 2 API listens on'),
                'value'         => 5665
            )
        );

        $this->addElement(
            'text',
            'username',
            array(
                'label'         => $this->translate('Icinga 2 API User'),
                'description'   => $this->translate(
                    'An Icinga 2 API user (not required if the user is authenticated only via TLS client certificate)'
                ),
            )
        );

        $this->addElement(
            'password',
            'password',
            array(
                'label'         => $this->translate('Icinga 2 API Password'),
                'description'   => $this->translate(
                    'The above user\'s password'
                        . ' (not required if the user is authenticated only via TLS client certificate)'
                ),
            )
        );

        $this->addElement(
            'file',
            'tls_client_cert_file',
            array(
                'label'         => $this->translate('TLS Client Certificate'),
                'description'   => $this->translate(
                    'TLS client certificate (not required if the user is authenticated only via HTTP basic auth)'
                ),
                'validators'    => array(new TlsCertFileValidator())
            )
        );

        $this->addElement(
            'file',
            'tls_client_key_file',
            array(
                'label'         => $this->translate('TLS Client Key'),
                'description'   => $this->translate(
                    'TLS client key (not required if the user is authenticated only via HTTP basic auth)'
                ),
                'validators'    => array(new TlsKeyFileValidator())
            )
        );

        $this->addElement(
            'hidden',
            'tls_client_cert',
            array('validators' => array(new TlsCertValidator()))
        );

        $this->addElement(
            'hidden',
            'tls_client_key',
            array('validators' => array(new TlsKeyValidator()))
        );

        $this->addElement(
            'hidden',
            'tls_server_cert',
            array('validators' => array(new TlsCertValidator()))
        );

        $this->addElement(
            'hidden',
            'tls_server_cert_accepted_fingerprint'
        );
    }

    public function isValid($formData)
    {
        if (! parent::isValid($formData)) {
            return false;
        }

        if (isset($formData['skip']) && $formData['skip']) {
            return true;
        }

        if (! $this->hasEnoughApiInfo()) {
            return false;
        }

        $this->persistTlsClientFiles();

        $serverTlsCert = $this->fetchServerTlsCert();
        if ($serverTlsCert === false) {
            return false;
        }

        return $this->processTlsServerCertAcceptance($formData, $serverTlsCert);
    }

    public function getValues($suppressArrayNotation = false)
    {
        $values = parent::getValues($suppressArrayNotation);
        unset($values['tls_client_cert_file']);
        unset($values['tls_client_key_file']);
        return $values;
    }

    /**
     * Create form element for Icinga 2's TLS certificate's info
     *
     * @param   string  $serverTlsCert  Icinga 2's TLS certificate
     *
     * @return  Note
     */
    protected function createTlsServerCertInfoNote($serverTlsCert)
    {
        $timezoneDetect = new TimezoneDetect();
        $timeZone = new DateTimeZone(
            $timezoneDetect->success() ? $timezoneDetect->getTimezoneName() : date_default_timezone_get()
        );
        $parsedServerTlsCert = openssl_x509_parse($serverTlsCert);
        $view = $this->getView();

        $subject = array();
        foreach ($parsedServerTlsCert['subject'] as $key => $value) {
            $subject[] = $view->escape("$key = " . var_export($value, true));
        }

        $issuer = array();
        foreach ($parsedServerTlsCert['issuer'] as $key => $value) {
            $issuer[] = $view->escape("$key = " . var_export($value, true));
        }

        return $this->createElement(
            'note',
            'tls_server_cert_info',
            array(
                'escape'    => false,
                'label'     => $this->translate('Icinga 2\'s TLS certificate'),
                'value'     => sprintf(
                    '<table class="name-value-list">' . str_repeat('<tr><td>%s</td><td>%s</td></tr>', 6) . '</table>',
                    $view->escape($this->translate('Subject', 'x509.certificate')),
                    $view->escape(implode('<br>', $subject)),
                    $view->escape($this->translate('Issuer', 'x509.certificate')),
                    $view->escape(implode('<br>', $issuer)),
                    $view->escape($this->translate('Valid from', 'x509.certificate')),
                    $view->escape(
                        DateTime::createFromFormat('U', $parsedServerTlsCert['validFrom_time_t'])
                            ->setTimezone($timeZone)
                            ->format(DateTime::ISO8601)
                    ),
                    $view->escape($this->translate('Valid until', 'x509.certificate')),
                    $view->escape(
                        DateTime::createFromFormat('U', $parsedServerTlsCert['validTo_time_t'])
                            ->setTimezone($timeZone)
                            ->format(DateTime::ISO8601)
                    ),
                    $view->escape($this->translate('SHA256 fingerprint', 'x509.certificate')),
                    $view->escape(
                        implode(' ', str_split(strtoupper(openssl_x509_fingerprint($serverTlsCert, 'sha256')), 2))
                    ),
                    $view->escape($this->translate('SHA1 fingerprint', 'x509.certificate')),
                    $view->escape(
                        implode(' ', str_split(strtoupper(openssl_x509_fingerprint($serverTlsCert, 'sha1')), 2))
                    )
                )
            )
        );
    }

    /**
     * Create checkbox for accepting Icinga 2's TLS certificate
     *
     * @return Zend_Form_Element_Checkbox
     */
    protected function createAcceptTlsServerCertCheckbox()
    {
        return $this->createElement('checkbox', 'tls_server_cert_accept', array(
            'label'         => $this->translate('Accept Icinga 2\'s TLS certificate'),
            'description'   => $this->translate('Trust the Icinga 2 API\'s TLS certificate')
        ));
    }

    /**
     * Return whether the user has provided enough information about the Icinga 2 API to connect to it non-anonymously
     *
     * @return bool
     */
    protected function hasEnoughApiInfo()
    {
        $hasEnough = true;

        if ($this->getValue('host') === '') {
            $this->addError($this->translate('Please specify the host'));
            $hasEnough = false;
        }

        if ($this->getValue('port') === '') {
            $this->addError($this->translate('Please specify the port'));
            $hasEnough = false;
        }

        if (($this->getValue('username') === '' || $this->getValue('password') === '') && (
            ($this->getValue('tls_client_cert_file') === null && $this->getValue('tls_client_cert') === null) || (
                $this->getValue('tls_client_key_file') === null && $this->getValue('tls_client_key') === null
            )
        )) {
            $this->addError($this->translate(
                'Please either specify username and password or provide a TLS client certificate and a key'
            ));
            $hasEnough = false;
        }

        return $hasEnough;
    }

    /**
     * Copy our TLS client certificate and key from the files into the hidden fields
     */
    protected function persistTlsClientFiles()
    {
        if ($this->getValue('tls_client_cert_file') !== null) {
            $this->getElement('tls_client_cert')->setValue(
                file_get_contents($this->getElement('tls_client_cert_file')->getFileName())
            );
        }

        if ($this->getValue('tls_client_key_file') !== null) {
            $this->getElement('tls_client_key')->setValue(
                file_get_contents($this->getElement('tls_client_key_file')->getFileName())
            );
        }
    }

    /**
     * Try to fetch Icinga 2's TLS certificate
     *
     * @return string|false
     */
    protected function fetchServerTlsCert()
    {
        $tlsOpts = array(
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'capture_peer_cert' => true
        );

        if (! ($this->getValue('tls_client_cert') === null || $this->getValue('tls_client_key') === null)) {
            $tlsClient = new TlsClient($this->getValue('tls_client_cert'), $this->getValue('tls_client_key'));
            $tlsOpts['local_cert'] = $tlsClient->getCertAndKey();
        }

        $errno = null;
        $errstr = null;
        $context = stream_context_create(array('ssl' => $tlsOpts));

        try {
            fclose(stream_socket_client(
                'tls://' . $this->getValue('host') . ':' . $this->getValue('port'),
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

        $cert = false;
        $params = stream_context_get_params($context);
        openssl_x509_export($params['options']['ssl']['peer_certificate'], $cert);
        return $cert;
    }

    /**
     * If the user accepted Icinga 2's TLS certificate, save this info – if not, ask the user to accept
     *
     * @param   string[]    $formData
     * @param   string      $serverTlsCert
     *
     * @return  bool        Whether the user accepted
     */
    protected function processTlsServerCertAcceptance(array $formData, $serverTlsCert)
    {
        $fingerprint = openssl_x509_fingerprint($serverTlsCert, 'sha256');
        $acceptedTlsCert = isset($formData['tls_server_cert'])
            && openssl_x509_fingerprint($formData['tls_server_cert'], 'sha256') === $fingerprint
            && ((isset($formData['tls_server_cert_accept']) && $formData['tls_server_cert_accept']) || (
                    isset($formData['tls_server_cert_accepted_fingerprint'])
                    && $formData['tls_server_cert_accepted_fingerprint'] === $fingerprint
                ));

        if ($acceptedTlsCert) {
            $this->getElement('tls_server_cert_accepted_fingerprint')->setValue($fingerprint);
        } else {
            $this->addElement($this->createTlsServerCertInfoNote($serverTlsCert));
            $this->addElement($this->createAcceptTlsServerCertCheckbox());
            $this->getElement('tls_server_cert')->setValue($serverTlsCert);
        }

        return $acceptedTlsCert;
    }
}
