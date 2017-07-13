<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

use Zend_Controller_Response_Http;
use Icinga\Application\Icinga;
use Icinga\Web\Response\JsonResponse;

/**
 * A HTTP response
 */
class Response extends Zend_Controller_Response_Http
{
    /**
     * The default content type being used for responses
     *
     * @var string
     */
    const DEFAULT_CONTENT_TYPE = 'text/html; charset=UTF-8';

    /**
     * Auto-refresh interval
     *
     * @var int
     */
    protected $autoRefreshInterval;

    /**
     * Set of cookies which are to be sent to the client
     *
     * @var CookieSet
     */
    protected $cookies;

    /**
     * Redirect URL
     *
     * @var Url|null
     */
    protected $redirectUrl;

    /**
     * Request
     *
     * @var Request
     */
    protected $request;

    /**
     * Whether to instruct client side script code to reload CSS
     *
     * @var bool
     */
    protected $reloadCss;

    /**
     * Whether to send the rerender layout header on XHR
     *
     * @var bool
     */
    protected $rerenderLayout = false;

    /**
     * Get the auto-refresh interval
     *
     * @return int
     */
    public function getAutoRefreshInterval()
    {
        return $this->autoRefreshInterval;
    }

    /**
     * Set the auto-refresh interval
     *
     * @param   int $autoRefreshInterval
     *
     * @return  $this
     */
    public function setAutoRefreshInterval($autoRefreshInterval)
    {
        $this->autoRefreshInterval = $autoRefreshInterval;
        return $this;
    }

    /**
     * Get the set of cookies which are to be sent to the client
     *
     * @return  CookieSet
     */
    public function getCookies()
    {
        if ($this->cookies === null) {
            $this->cookies = new CookieSet();
        }
        return $this->cookies;
    }

    /**
     * Get the cookie with the given name from the set of cookies which are to be sent to the client
     *
     * @param   string  $name       The name of the cookie
     *
     * @return  Cookie|null         The cookie with the given name or null if the cookie does not exist
     */
    public function getCookie($name)
    {
        return $this->getCookies()->get($name);
    }

    /**
     * Set the given cookie for sending it to the client
     *
     * @param   Cookie  $cookie The cookie to send to the client
     *
     * @return  $this
     */
    public function setCookie(Cookie $cookie)
    {
        $this->getCookies()->add($cookie);
        return $this;
    }

    /**
     * Get the redirect URL
     *
     * @return Url|null
     */
    protected function getRedirectUrl()
    {
        return $this->redirectUrl;
    }

    /**
     * Set the redirect URL
     *
     * Unlike {@link setRedirect()} this method only sets a redirect URL on the response for later usage.
     * {@link prepare()} will take care of the correct redirect handling and HTTP headers on XHR and "normal" browser
     * requests.
     *
     * @param   string|Url $redirectUrl
     *
     * @return  $this
     */
    protected function setRedirectUrl($redirectUrl)
    {
        if (! $redirectUrl instanceof Url) {
            $redirectUrl = Url::fromPath((string) $redirectUrl);
        }
        $redirectUrl->getParams()->setSeparator('&');
        $this->redirectUrl = $redirectUrl;
        return $this;
    }

    /**
     * Get an array of all header values for the given name
     *
     * @param   string  $name       The name of the header
     * @param   bool    $lastOnly   If this is true, the last value will be returned as a string
     *
     * @return  null|array|string
     */
    public function getHeader($name, $lastOnly = false)
    {
        $result = ($lastOnly ? null : array());
        $headers = $this->getHeaders();
        foreach ($headers as $header) {
            if ($header['name'] === $name) {
                if ($lastOnly) {
                    $result = $header['value'];
                } else {
                    $result[] = $header['value'];
                }
            }
        }

        return $result;
    }

    /**
     * Get the request
     *
     * @return Request
     */
    public function getRequest()
    {
        if ($this->request === null) {
            $this->request = Icinga::app()->getRequest();
        }
        return $this->request;
    }

    /**
     * Get whether to instruct client side script code to reload CSS
     *
     * @return bool
     */
    public function isReloadCss()
    {
        return $this->reloadCss;
    }

    /**
     * Set whether to instruct client side script code to reload CSS
     *
     * @param   bool    $reloadCss
     *
     * @return  $this
     */
    public function setReloadCss($reloadCss)
    {
        $this->reloadCss = $reloadCss;
        return $this;
    }

    /**
     * Get whether to send the rerender layout header on XHR
     *
     * @return bool
     */
    public function getRerenderLayout()
    {
        return $this->rerenderLayout;
    }

    /**
     * Get whether to send the rerender layout header on XHR
     *
     * @param   bool $rerenderLayout
     *
     * @return  $this
     */
    public function setRerenderLayout($rerenderLayout = true)
    {
        $this->rerenderLayout = (bool) $rerenderLayout;
        return $this;
    }

    /**
     * Entry point for HTTP responses in JSON format
     *
     * @return JsonResponse
     */
    public function json()
    {
        $response = new JsonResponse();
        $response->copyMetaDataFrom($this);
        return $response;
    }

    /**
     * Prepare the request before sending
     */
    protected function prepare()
    {
        $redirectUrl = $this->getRedirectUrl();
        if ($this->getRequest()->isXmlHttpRequest()) {
            if ($redirectUrl !== null) {
                $this->setHeader('X-Icinga-Redirect', rawurlencode($redirectUrl->getAbsoluteUrl()), true);
                if ($this->getRerenderLayout()) {
                    $this->setHeader('X-Icinga-Rerender-Layout', 'yes', true);
                }
            }
            if ($this->getRerenderLayout()) {
                $this->setHeader('X-Icinga-Container', 'layout', true);
            }
            if ($this->isReloadCss()) {
                $this->setHeader('X-Icinga-Reload-Css', 'now', true);
            }
            if (($autoRefreshInterval = $this->getAutoRefreshInterval()) !== null) {
                $this->setHeader('X-Icinga-Refresh', $autoRefreshInterval, true);
            }

            $notifications = Notification::getInstance();
            if ($notifications->hasMessages()) {
                $notificationList = array();
                foreach ($notifications->popMessages() as $m) {
                    $notificationList[] = rawurlencode($m->type . ' ' . $m->message);
                }
                $this->setHeader('X-Icinga-Notification', implode('&', $notificationList), true);
            }
        } else {
            if ($redirectUrl !== null) {
                $this->setRedirect($redirectUrl->getAbsoluteUrl());
            }
        }

        if (! $this->getHeader('Content-Type', true)) {
            $this->setHeader('Content-Type', static::DEFAULT_CONTENT_TYPE);
        }
    }

    /**
     * Redirect to the given URL and exit immediately
     *
     * @param string|Url $url
     */
    public function redirectAndExit($url)
    {
        $this->setRedirectUrl($url);

        $session = Session::getSession();
        if ($session->hasChanged()) {
            $session->write();
        }

        $this->sendHeaders();
        exit;
    }

    /**
     * Send the cookies to the client
     */
    public function sendCookies()
    {
        foreach ($this->getCookies() as $cookie) {
            /** @var Cookie $cookie */
            setcookie(
                $cookie->getName(),
                $cookie->getValue(),
                $cookie->getExpire(),
                $cookie->getPath(),
                $cookie->getDomain(),
                $cookie->isSecure(),
                $cookie->isHttpOnly()
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function sendHeaders()
    {
        $this->prepare();
        if (! $this->getRequest()->isApiRequest()) {
            $this->sendCookies();
        }
        return parent::sendHeaders();
    }

    /**
     * Copies non-body-related response data from $response
     *
     * @param   Response    $response
     *
     * @return  $this
     */
    protected function copyMetaDataFrom(self $response)
    {
        $this->_headers = $response->_headers;
        $this->_headersRaw = $response->_headersRaw;
        $this->_httpResponseCode = $response->_httpResponseCode;
        $this->headersSentThrowsException = $response->headersSentThrowsException;
        return $this;
    }
}
