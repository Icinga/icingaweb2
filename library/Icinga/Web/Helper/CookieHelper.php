<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Helper;

use Icinga\Web\Request;

/**
 * Helper Class Cookie
 */
class CookieHelper
{
    /**
     * The name of the control cookie
     */
    const CHECK_COOKIE = '_chc';

    /**
     * The request
     *
     * @var Request
     */
    protected $request;

    /**
     * Create a new cookie
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Check whether cookies are supported or not
     *
     * @return bool
     */
    public function isSupported()
    {
        if (! empty($_COOKIE)) {
            $this->cleanupCheck();
            return true;
        }

        $url = $this->request->getUrl();

        if ($url->hasParam('_checkCookie') && empty($_COOKIE)) {
            return false;
        }

        if (! $url->hasParam('_checkCookie')) {
            $this->provideCheck();
        }

        return false;
    }

    /**
     * Prepare check to detect cookie support
     */
    public function provideCheck()
    {
        setcookie(self::CHECK_COOKIE, '1');

        $requestUri = $this->request->getUrl()->addParams(array('_checkCookie' => 1));
        $this->request->getResponse()->redirectAndExit($requestUri);
    }

    /**
     * Cleanup the cookie support check
     */
    public function cleanupCheck()
    {
        if ($this->request->getUrl()->hasParam('_checkCookie') && isset($_COOKIE[self::CHECK_COOKIE])) {
            $requestUri =$this->request->getUrl()->without('_checkCookie');
            $this->request->getResponse()->redirectAndExit($requestUri);
        }
    }
}
