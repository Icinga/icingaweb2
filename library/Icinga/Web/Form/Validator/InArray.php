<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Form\Validator;

use Zend_Validate_InArray;
use Icinga\Util\StringHelper;

class InArray extends Zend_Validate_InArray
{
    protected function _error($messageKey, $value = null)
    {
        if ($messageKey === static::NOT_IN_ARRAY) {
            $matches = StringHelper::findSimilar($this->_value, $this->_haystack);
            if (empty($matches)) {
                $this->_messages[$messageKey] = sprintf(t('"%s" is not in the list of allowed values.'), $this->_value);
            } else {
                $this->_messages[$messageKey] = sprintf(
                    t('"%s" is not in the list of allowed values. Did you mean one of the following?: %s'),
                    $this->_value,
                    implode(', ', $matches)
                );
            }
        } else {
            parent::_error($messageKey, $value);
        }
    }
}
