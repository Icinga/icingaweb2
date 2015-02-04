<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

use Zend_Controller_Response_Http;
use Icinga\Application\Icinga;

class Response extends Zend_Controller_Response_Http
{
    public function redirectAndExit($url)
    {
        if (! $url instanceof Url) {
            $url = Url::fromPath($url);
        }
        $url->getParams()->setSeparator('&');

        if (Icinga::app()->getFrontController()->getRequest()->isXmlHttpRequest()) {
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
