<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Web\Form\Validator;

use Exception;
use Zend_Validate_Abstract;
use Icinga\Util\File;

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
            'TOKEN_FILE_ERROR'  => sprintf(
                mt('setup', 'Cannot validate token: %s (%s)'),
                $tokenPath,
                '%value%'
            ),
            'TOKEN_FILE_EMPTY'  => sprintf(
                mt('setup', 'Cannot validate token, file "%s" is empty. Please define a token.'),
                $tokenPath
            ),
            'TOKEN_INVALID'     => mt('setup', 'Invalid token supplied.')
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
        try {
            $file = new File($this->tokenPath);
            $expectedToken = trim($file->fgets());
        } catch (Exception $e) {
            $msg = $e->getMessage();
            $this->_error('TOKEN_FILE_ERROR', substr($msg, strpos($msg, ']: ') + 3));
            return false;
        }

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
