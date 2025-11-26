<?php

/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Form\Validator;

use ipl\I18n\Translation;
use ipl\Validator\BaseValidator;

class TotpTokenValidator extends BaseValidator
{
    use Translation;

    public function __construct(
        /** @var int Token length */
        protected int $digits = 6
    ) {
    }

    public function isValid($value): bool
    {
        // Multiple isValid() calls must not stack validation messages
        $this->clearMessages();

        if (! is_numeric($value)) {
            $this->addMessage($this->translate('The token must only contain numbers'));

            return false;
        }

        if (strlen($value) !== $this->digits) {
            $this->addMessage($this->translate('The token must be exactly 6 digits long.'));

            return false;
        }

        return true;
    }
}
