<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

use Zend_Controller_Response_Http;
use Icinga\Application\Icinga;

class Response extends Zend_Controller_Response_Http
{
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
     * Get the request
     *
     * @return Request
     */
    public function getRequest()
    {
        if ($this->request === null) {
            $this->request = Icinga::app()->getFrontController()->getRequest();
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
        if ($this->getRequest()->isXmlHttpRequest() && $this->getRerenderLayout()) {
            $this->setHeader('X-Icinga-Rerender-Layout', 'yes');
        }
    }

    /**
     * Redirect to the given URL and exit immediately
     *
     * @param string|Url $url
     */
    public function redirectAndExit($url)
    {
        if (! $url instanceof Url) {
            $url = Url::fromPath((string) $url);
        }
        $url->getParams()->setSeparator('&');

        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->setHeader('X-Icinga-Redirect', rawurlencode($url->getAbsoluteUrl()));
        } else {
            $this->setRedirect($url->getAbsoluteUrl());
        }

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
