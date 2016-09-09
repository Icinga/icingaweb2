<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Form\Validator;

use Zend_Validate_Abstract;
use Icinga\Web\Url;

/**
 * Validator that checks whether a textfield doesn't contain an external URL
 */
class InternalUrlValidator extends Zend_Validate_Abstract
{
    /**
     * {@inheritdoc}
     */
    public function isValid($value)
    {
        if (Url::fromPath($value)->isExternal()) {
            $this->_error('IS_EXTERNAL');

            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function _error($messageKey, $value = null)
    {
        if ($messageKey === 'IS_EXTERNAL') {
            $this->_messages[$messageKey] = t('The url must not be external.');
        } else {
            parent::_error($messageKey, $value);
        }
    }
}
