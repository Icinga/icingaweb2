<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

/**
 * Helper for escaping comments, but preserving links
 */
class Zend_View_Helper_EscapeComment extends Zend_View_Helper_Abstract
{
    /**
     * The purifier to use for escaping
     *
     * @var HTMLPurifier
     */
    protected static $purifier;

    /**
     * Escape any comment for being placed inside HTML, but preserve simple links (<a href="...">).
     *
     * @param   string  $comment
     *
     * @return  string
     */
    public function escapeComment($comment)
    {
        if (self::$purifier === null) {
            require_once 'HTMLPurifier/Bootstrap.php';
            require_once 'HTMLPurifier.php';
            require_once 'HTMLPurifier.autoload.php';

            $config = HTMLPurifier_Config::createDefault();
            $config->set('Core.EscapeNonASCIICharacters', true);
            $config->set('HTML.Allowed', 'a[href]');
            $config->set('Cache.DefinitionImpl', null);
            self::$purifier = new HTMLPurifier($config);
        }
        return self::$purifier->purify($comment);
    }
}
