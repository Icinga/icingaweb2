<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Form\Validator;

use Zend_Validate_Abstract;

/**
 * Validates TLS X509 certificate files
 */
class TlsCertFileValidator extends Zend_Validate_Abstract
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_messageTemplates = array('INVALID_TLS_CERT' => t('Invalid TLS certificate'));
    }

    public function isValid($value)
    {
        if (openssl_x509_parse("file://$value") === false) {
            $this->_error('INVALID_TLS_CERT');
            return false;
        }

        return true;
    }
}
