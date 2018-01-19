<?php
/* Icinga Web 2 | (c) 2018 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Form\Validator;

use ErrorException;
use Zend_Validate_Abstract;

/**
 * Validates a list of PCREs
 */
class PcreListValidator extends Zend_Validate_Abstract
{
    public function isValid($value)
    {
        try {
            foreach (explode("\n", $value) as $line) {
                $line = trim($line);

                if ($line === '') {
                    continue;
                }

                preg_match($line, '');
            }
        } catch (ErrorException $e) {
            $this->_messages['BAD_PCRE'] = sprintf(
                t('Bad PCRE %s: %s'),
                var_export($line, true),
                $e->getMessage()
            );

            return false;
        }

        return true;
    }
}
