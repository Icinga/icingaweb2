<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

use Zend_Controller_Response_Http;
use Icinga\Application\Icinga;

class Response extends Zend_Controller_Response_Http
{
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
     * Whether to send the rerender layout header on XHR
     *
     * @var bool
     */
    protected $rerenderLayout = false;

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
        } else {
            if ($redirectUrl !== null) {
                $this->setRedirect($redirectUrl->getAbsoluteUrl());
            }
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
     * {@inheritdoc}
     */
    public function sendHeaders()
    {
        $this->prepare();
        return parent::sendHeaders();
    }
}
