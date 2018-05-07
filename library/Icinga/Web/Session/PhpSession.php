<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Session;

use Icinga\Application\Logger;
use Icinga\Exception\ConfigurationError;
use Icinga\Web\Cookie;

/**
 * Session implementation in PHP
 */
class PhpSession extends Session
{
    /**
     * The namespace prefix
     *
     * Used to differentiate between standard session keys and namespace identifiers
     */
    const NAMESPACE_PREFIX = 'ns.';

    /**
     * Whether the session has already been closed
     *
     * @var bool
     */
    protected $hasBeenTouched = false;

    /**
     * Name of the session
     *
     * @var string
     */
    protected $sessionName = 'Icingaweb2';

    /**
     * Create a new PHPSession object using the provided options (if any)
     *
     * @param   array   $options    An optional array of ini options to set
     *
     * @return  static
     *
     * @throws  ConfigurationError
     * @see     http://php.net/manual/en/session.configuration.php
     */
    public static function create(array $options = null)
    {
        return version_compare(PHP_VERSION, '7.2.0') < 0 ? new self($options) : new Php72Session($options);
    }

    /**
     * Create a new PHPSession object using the provided options (if any)
     *
     * @param   array   $options    An optional array of ini options to set
     *
     * @throws  ConfigurationError
     * @see     http://php.net/manual/en/session.configuration.php
     */
    public function __construct(array $options = null)
    {
        $defaultCookieOptions = array(
            'use_trans_sid'     => false,
            'use_cookies'       => true,
            'cookie_httponly'   => true,
            'use_only_cookies'  => true
        );

        if (version_compare(PHP_VERSION, '7.1.0') < 0) {
            $defaultCookieOptions['hash_function'] = true;
            $defaultCookieOptions['hash_bits_per_character'] = 5;
        } else {
            $defaultCookieOptions['sid_bits_per_character'] = 5;
        }

        if ($options !== null) {
            $options = array_merge($defaultCookieOptions, $options);
        } else {
            $options = $defaultCookieOptions;
        }

        if (array_key_exists('test_session_name', $options)) {
            $this->sessionName = $options['test_session_name'];
            unset($options['test_session_name']);
        }

        foreach ($options as $sessionVar => $value) {
            if (ini_set("session." . $sessionVar, $value) === false) {
                Logger::warning(
                    'Could not set php.ini setting %s = %s. This might affect your sessions behaviour.',
                    $sessionVar,
                    $value
                );
            }
        }

        $sessionSavePath = session_save_path() ?: sys_get_temp_dir();
        if (session_module_name() === 'files' && !is_writable($sessionSavePath)) {
            throw new ConfigurationError("Can't save session, path '$sessionSavePath' is not writable.");
        }

        if ($this->exists()) {
            // We do not want to start a new session here if there is not any
            $this->read();
        }
    }

    /**
     * Open a PHP session
     */
    protected function open()
    {
        session_name($this->sessionName);

        if ($this->hasBeenTouched) {
            $cacheLimiter = ini_get('session.cache_limiter');
            ini_set('session.use_cookies', false);
            ini_set('session.use_only_cookies', false);
            ini_set('session.cache_limiter', null);
        }

        $cookie = new Cookie('bogus');
        session_set_cookie_params(
            0,
            $cookie->getPath(),
            $cookie->getDomain(),
            $cookie->isSecure(),
            true
        );

        session_start();

        if ($this->hasBeenTouched) {
            ini_set('session.use_cookies', true);
            ini_set('session.use_only_cookies', true);
            /** @noinspection PhpUndefinedVariableInspection */
            ini_set('session.cache_limiter', $cacheLimiter);
        }
    }

    /**
     * Read all values written to the underling session and make them accessible.
     */
    public function read()
    {
        $this->clear();
        $this->open();

        foreach ($_SESSION as $key => $value) {
            if (strpos($key, self::NAMESPACE_PREFIX) === 0) {
                $namespace = new SessionNamespace();
                $namespace->setAll($value);
                $this->namespaces[substr($key, strlen(self::NAMESPACE_PREFIX))] = $namespace;
            } else {
                $this->set($key, $value);
            }
        }

        session_write_close();
        $this->hasBeenTouched = true;
    }

    /**
     * Write all values of this session object to the underlying session implementation
     */
    public function write()
    {
        $this->open();

        foreach ($this->removed as $key) {
            unset($_SESSION[$key]);
        }
        foreach ($this->values as $key => $value) {
            $_SESSION[$key] = $value;
        }
        foreach ($this->removedNamespaces as $identifier) {
            unset($_SESSION[self::NAMESPACE_PREFIX . $identifier]);
        }
        foreach ($this->namespaces as $identifier => $namespace) {
            $_SESSION[self::NAMESPACE_PREFIX . $identifier] = $namespace->getAll();
        }

        session_write_close();
        $this->hasBeenTouched = true;
    }

    /**
     * Delete the current session, causing all session information to be lost
     */
    public function purge()
    {
        $this->open();
        $_SESSION = array();
        $this->clear();
        session_destroy();
        $this->clearCookies();
        session_write_close();
        $this->hasBeenTouched = true;
    }

    /**
     * Remove session cookies
     */
    protected function clearCookies()
    {
        if (ini_get('session.use_cookies')) {
            Logger::debug('Clear session cookie');
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
    }

    /**
     * @see Session::getId()
     */
    public function getId()
    {
        if (($id = session_id()) === '') {
            // Make sure we actually get a id
            $this->open();
            session_write_close();
            $this->hasBeenTouched = true;
            $id = session_id();
        }

        return $id;
    }

    /**
     * Assign a new sessionId to the currently active session
     */
    public function refreshId()
    {
        $this->open();
        if ($this->exists()) {
            session_regenerate_id();
        }
        session_write_close();
        $this->hasBeenTouched = true;
    }

    /**
     * @see Session::exists()
     */
    public function exists()
    {
        return isset($_COOKIE[$this->sessionName]);
    }
}
