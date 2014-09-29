<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Form\Validator;

use Zend_Validate_Abstract;

/**
 * Validator that checks if a token matches with the contents of a corresponding token-file
 */
class TokenValidator extends Zend_Validate_Abstract
{
    /**
     * The path to the token file
     *
     * @var string
     */
    protected $tokenPath;

    /**
     * Create a new TokenValidator
     *
     * @param   string      $tokenPath      The path to the token-file
     */
    public function __construct($tokenPath)
    {
        $this->tokenPath = $tokenPath;
        $this->_messageTemplates = array(
            'TOKEN_FILE_NOT_FOUND'  => t('Cannot validate token, file could not be opened or does not exist.'),
            'TOKEN_FILE_EMPTY'      => t('Cannot validate token, file is empty. Please define a token.'),
            'TOKEN_FILE_PUBLIC'     => t('Cannot validate token, file is publicly readable.'),
            'TOKEN_INVALID'         => t('Invalid token supplied.')
        );
    }

    /**
     * Validate the given token with the one in the token-file
     *
     * @param   string  $value      The token to validate
     * @param   null    $context    The form context (ignored)
     *
     * @return  bool
     */
    public function isValid($value, $context = null)
    {
        $tokenStats = @stat($this->tokenPath);
        if (($tokenStats['mode'] & 4) === 4) {
            $this->_error('TOKEN_FILE_PUBLIC');
            return false;
        }

        $expectedToken = @file_get_contents($this->tokenPath);
        if ($expectedToken === false) {
            $this->_error('TOKEN_FILE_NOT_FOUND');
            return false;
        }

        $expectedToken = trim($expectedToken);
        if (empty($expectedToken)) {
            $this->_error('TOKEN_FILE_EMPTY');
            return false;
        } elseif ($value !== $expectedToken) {
            $this->_error('TOKEN_INVALID');
            return false;
        }

        return true;
    }
}

