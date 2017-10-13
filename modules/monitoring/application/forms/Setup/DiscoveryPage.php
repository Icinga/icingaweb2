<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Setup;

use DateTime;
use DateTimeZone;
use ErrorException;
use Icinga\Module\Monitoring\Util\TemporaryDirectory;
use Icinga\Module\Monitoring\Web\Form\Validator\TlsCertFileValidator;
use Icinga\Module\Monitoring\Web\Form\Validator\TlsCertValidator;
use Icinga\Module\Monitoring\Web\Form\Validator\TlsKeyFileValidator;
use Icinga\Module\Monitoring\Web\Form\Validator\TlsKeyValidator;
use Icinga\Util\TimezoneDetect;
use Icinga\Web\Form;
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

        $this->addElement(
            'hidden',
            'tls_server_cacert',
            array('validators' => array(new TlsCertValidator()))
        );

        $this->addElement(
            'hidden',
            'tls_server_cacert_accepted_fingerprint'
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

        $serverTlsCertChain = $this->fetchServerTlsCertChain();
        if ($serverTlsCertChain === false) {
            return false;
        }

        return $this->processTlsServerCertAcceptance($formData, $serverTlsCertChain);
    }

    public function getValues($suppressArrayNotation = false)
    {
        $values = parent::getValues($suppressArrayNotation);
        unset($values['tls_client_cert_file']);
        unset($values['tls_client_key_file']);
        return $values;
    }

    /**
     * Add form elements for Icinga 2's TLS certificate chain's info
     *
     * @param   string  $serverTlsCert  Icinga 2's TLS certificate
     * @param   string  $caTlsCert      Icinga 2's TLS CA certificate
     */
    protected function addTlsServerCertInfoNotes($serverTlsCert, $caTlsCert)
    {
        $timezoneDetect = new TimezoneDetect();
        $timeZone = new DateTimeZone(
            $timezoneDetect->success() ? $timezoneDetect->getTimezoneName() : date_default_timezone_get()
        );

        $this->addTlsServerCertInfoNote(
            $caTlsCert,
            'tls_server_cacert_info',
            $this->translate('Icinga 2\'s TLS CA certificate'),
            $timeZone
        );

        $this->addTlsServerCertInfoNote(
            $serverTlsCert,
            'tls_server_cert_info',
            $this->translate('Icinga 2\'s TLS certificate'),
            $timeZone
        );
    }

    /**
     * Add form element for the given TLS certificate's info
     *
     * @param   string          $tlsCert
     * @param   string          $name
     * @param   string          $label
     * @param   DateTimeZone    $timeZone
     */
    protected function addTlsServerCertInfoNote($tlsCert, $name, $label, DateTimeZone $timeZone)
    {
        $view = $this->getView();
        $parsedServerTlsCert = openssl_x509_parse($tlsCert);

        $subject = array();
        foreach ($parsedServerTlsCert['subject'] as $key => $value) {
            $subject[] = $view->escape("$key = " . var_export($value, true));
        }

        $issuer = array();
        foreach ($parsedServerTlsCert['issuer'] as $key => $value) {
            $issuer[] = $view->escape("$key = " . var_export($value, true));
        }

        $this->addElement(
            'note',
            $name,
            array(
                'escape'    => false,
                'label'     => $label,
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
                        implode(' ', str_split(strtoupper(openssl_x509_fingerprint($tlsCert, 'sha256')), 2))
                    ),
                    $view->escape($this->translate('SHA1 fingerprint', 'x509.certificate')),
                    $view->escape(
                        implode(' ', str_split(strtoupper(openssl_x509_fingerprint($tlsCert, 'sha1')), 2))
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
     * Try to fetch Icinga 2's TLS certificate chain
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

        if (! ($this->getValue('tls_client_cert') === null || $this->getValue('tls_client_key') === null)) {
            $tempDir = new TemporaryDirectory();
            $certAndKey = $tempDir . DIRECTORY_SEPARATOR . 'cert-and-key.pem';
            file_put_contents(
                $certAndKey,
                $this->getValue('tls_client_cert') . PHP_EOL . $this->getValue('tls_client_key')
            );

            $tlsOpts['local_cert'] = $certAndKey;
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

        $certs = array();
        $params = stream_context_get_params($context);
        foreach ($params['options']['ssl']['peer_certificate_chain'] as $index => $cert) {
            $certs[$index] = null;
            openssl_x509_export($cert, $certs[$index]);
        }

        return $certs;
    }

    /**
     * If the user accepted Icinga 2's TLS certificate, save this info – if not, ask the user to accept
     *
     * @param   string[]    $formData
     * @param   string[]    $serverTlsCertChain
     *
     * @return  bool        Whether the user accepted
     */
    protected function processTlsServerCertAcceptance(array $formData, array $serverTlsCertChain)
    {
        $certs = array();
        $issuers = array();
        foreach ($serverTlsCertChain as $cert) {
            $parsed = openssl_x509_parse($cert);
            $certs[$parsed['subject']['CN']] = $cert;

            if ($parsed['issuer']['CN'] === $parsed['subject']['CN']) {
                $serverCn = $rootCn = $parsed['issuer']['CN'];
            } else {
                $issuers[$parsed['issuer']['CN']] = $parsed['subject']['CN'];
            }
        }

        if (! isset($rootCn)) {
            $this->addError($this->translate('Icinga 2 didn\'t provide any TLS root CA certificate'));
            return false;
        }

        while (isset($issuers[$serverCn])) {
            $serverCn = $issuers[$serverCn];
        }

        if ($serverCn === $rootCn) {
            $this->addError($this->translate('Icinga 2 didn\'t provide any non-self-signed TLS certificate'));
            return false;
        }

        $fingerprint = openssl_x509_fingerprint($certs[$serverCn], 'sha256');
        $fingerprintCA = openssl_x509_fingerprint($certs[$rootCn], 'sha256');

        $serverOk = isset($formData['tls_server_cert'])
            && $formData['tls_server_cert'] !== ''
            && openssl_x509_fingerprint($formData['tls_server_cert'], 'sha256') === $fingerprint;

        $caOk = isset($formData['tls_server_cacert'])
            && $formData['tls_server_cacert'] !== ''
            && openssl_x509_fingerprint($formData['tls_server_cacert'], 'sha256') === $fingerprintCA;

        $acceptedNow = isset($formData['tls_server_cert_accept']) && $formData['tls_server_cert_accept'];

        $alreadyAcceptedServer = isset($formData['tls_server_cert_accepted_fingerprint'])
            && $formData['tls_server_cert_accepted_fingerprint'] === $fingerprint;

        $alreadyAcceptedCA = isset($formData['tls_server_cacert_accepted_fingerprint'])
            && $formData['tls_server_cacert_accepted_fingerprint'] === $fingerprintCA;

        $acceptedTlsCert = $serverOk && $caOk && ($acceptedNow || ($alreadyAcceptedServer && $alreadyAcceptedCA));

        if ($acceptedTlsCert) {
            $this->getElement('tls_server_cert_accepted_fingerprint')->setValue($fingerprint);
            $this->getElement('tls_server_cacert_accepted_fingerprint')->setValue($fingerprintCA);
        } else {
            $this->addTlsServerCertInfoNotes($certs[$serverCn], $certs[$rootCn]);
            $this->addElement($this->createAcceptTlsServerCertCheckbox());
            $this->getElement('tls_server_cert')->setValue($certs[$serverCn]);
            $this->getElement('tls_server_cacert')->setValue($certs[$rootCn]);
        }

        return $acceptedTlsCert;
    }
}
