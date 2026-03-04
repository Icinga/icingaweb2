<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Form\Validator;

use Zend_Validate_Abstract;

/**
 * Validates TLS private key files
 */
class TlsKeyFileValidator extends Zend_Validate_Abstract
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_messageTemplates = array('INVALID_TLS_KEY' => t('Invalid TLS private key'));
    }

    public function isValid($value)
    {
        if (openssl_pkey_get_private("file://$value") === false) {
            $this->_error('INVALID_TLS_KEY');
            return false;
        }

        return true;
    }
}
