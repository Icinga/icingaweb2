<?php

/**
 * Session handling
 */
namespace Icinga\Web;

use Icinga\Authentication\Auth\User;
use Zend_Session;
use Zend_Session_Namespace;
use Icinga\Exception\ProgrammingError;

/**
 * Session handling happens here
 *
 * This is mainly a facade for Zend_Session_Namespace but provides some farther
 * functionality for authentication
 *
 * @copyright  Copyright (c) 2013 Icinga-Web Team <info@icinga.org>
 * @author     Icinga-Web Team <info@icinga.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class Session
{
    /**
     * Session is a Singleton stored in $instance
     *
     * @var Session
     */
    protected static $instance;

    protected $defaultOptions = array(
        'use_trans_sid'           => false,
        'use_cookies'             => true,
        'use_only_cooies'         => true,
        'cookie_httponly'         => true,
        'use_only_cookies'        => true,
        'hash_function'           => true,
        'hash_bits_per_character' => 5,
    );

    /**
     * The ZF session namespace
     *
     * @var \Zend_Session_Namespace
     */
    protected $session;

    protected $started = false;
    protected $closed  = true;

    /**
     * Constructor is protected to enforce singleton usage
     */
    protected function __construct()
    {
        Zend_Session::start();
        $this->session = new Zend_Session_Namespace('Icinga');
    }
/*
// Not yet
    public function start()
    {
        if ($this->started) {
            return $this;
        }
        if ($this->closed) {
            ini_set('session.cache_limiter',    null);
            ini_set('session.use_only_cookies', false);
            ini_set('session.use_cookies',      false);
            ini_set('session.use_trans_sid',    false);
        }
        $this->applyOptions();
        session_start();
        return $this;
    }

    protected function applyOptions()
    {
        foreach ($this->defaultOptions as $key => $val) {
            ini_set('session.' . $key => $val);
        }
        return $this;
    }
*/
    public static function setOptions($options)
    {
        return Zend_Session::setOptions($options);
    }

    /**
     * Once authenticated we store the given user(name) to our session
     *
     * @param  Auth\User $user The user object
     * // TODO: Useless
     * @return self
     */
    public function setAuthenticatedUser(User $user)
    {
        $this->session->userInfo = (string) $user;
        $this->session->realname = (string) $user; // TODO: getRealName()
        return $this;
    }

    /**
     * Get the user object for the authenticated user
     *
     * // TODO: This has not been done yet. Useless?
     *
     * @return User $user The user object
     */
    public function getUser()
    {
        throw new ProgrammingError('Not implemented yet');
    }

    /**
     * Whether this session has an authenticated user
     *
     * // TODO: remove
     * @return bool
     */
    public function isAuthenticated()
    {
        return isset($this->session->username);
    }

    /**
     * Forget everything we know about the authenticated user
     *
     * // TODO: Remove
     * @return self
     */
    public function discardAuthentication()
    {
        unset($this->session->username);
        unset($this->session->realname);
        return $this;
    }

    /**
     * Get a Singleton instance
     *
     * TODO: This doesn't work so yet, it gives you a Zend_Session_Namespace
     *       instance. Facade has to be completed before we can fix this.
     *
     * @return Icinga\Web\Session -> not yet
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Session();
        }
        return self::$instance->session;
    }
}
