<?php
/* Icinga Web 2 | (c) 2018 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Helper;

use Closure;
use InvalidArgumentException;

class HtmlPurifier
{
    /**
     * The actual purifier instance
     *
     * @var \HTMLPurifier
     */
    protected $purifier;

    /**
     * Create a new HtmlPurifier
     *
     * @param   array|Closure   $config     Additional configuration
     */
    public function __construct($config = null)
    {
        require_once 'HTMLPurifier/Bootstrap.php';
        require_once 'HTMLPurifier.php';
        require_once 'HTMLPurifier.autoload.php';

        $purifierConfig = \HTMLPurifier_Config::createDefault();
        $purifierConfig->set('Core.EscapeNonASCIICharacters', true);
        $purifierConfig->set('Attr.AllowedFrameTargets', array('_blank'));
        // This avoids permission problems:
        // $purifierConfig->set('Core.DefinitionCache', null);
        $purifierConfig->set('Cache.DefinitionImpl', null);
        // TODO: Use a cache directory:
        // $purifierConfig->set('Cache.SerializerPath', '/var/spool/whatever');
        // $purifierConfig->set('URI.Base', 'http://www.example.com');
        // $purifierConfig->set('URI.MakeAbsolute', true);

        $this->configure($purifierConfig);

        if ($config instanceof Closure) {
            call_user_func($config, $purifierConfig);
        } elseif (is_array($config)) {
            $purifierConfig->loadArray($config);
        } elseif ($config !== null) {
            throw new InvalidArgumentException('$config must be either a Closure or array');
        }

        $this->purifier = new \HTMLPurifier($purifierConfig);
    }

    /**
     * Apply additional default configuration
     *
     * May be overwritten by more concrete purifier implementations.
     *
     * @param   \HTMLPurifier_Config    $config
     */
    protected function configure($config)
    {
    }

    /**
     * Purify and return the given HTML string
     *
     * @param   string          $html
     * @param   array|Closure   $config     Configuration to use instead of the default
     *
     * @return  string
     */
    public function purify($html, $config = null)
    {
        return $this->purifier->purify($html, $config);
    }

    /**
     * Purify and return the given HTML string
     *
     * Convenience method to bypass object creation.
     *
     * @param   string          $html
     * @param   array|Closure   $config     Additional configuration
     *
     * @return  string
     */
    public static function process($html, $config = null)
    {
        $purifier = new static($config);

        return $purifier->purify($html);
    }
}
