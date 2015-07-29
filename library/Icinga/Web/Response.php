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

    public function redirectAndExit($url)
    {
        if (! $url instanceof Url) {
            $url = Url::fromPath($url);
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
}
