<?php

namespace Icinga\Util;

use Icinga\Web\Response;
use Icinga\Web\Session;
use Icinga\Web\Window;
use RuntimeException;

class Csp
{
    /** @var static */
    private static $instance = null;

    /** @var string */
    private $styleNonce = null;


    private function loadNoncesFromSession()
    {
        $windowSession = Window::getInstance()->getSessionNamespace('csp');

        $this->styleNonce = $windowSession->get('csp_style_nonce');
    }

    private static function getInstance(): self
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        static::$instance->loadNoncesFromSession();

        return static::$instance;
    }

    public static function getStyleNonce(): ?string
    {
        $instance = static::getInstance();

        if (empty($instance->styleNonce)) {
            throw new RuntimeException('No style nonce is set');
        }

        return $instance->styleNonce;
    }

    public static function createNonces(): void
    {
        $instance = static::getInstance();
        $windowSession = Window::getInstance()->getSessionNamespace('csp', true);

        $instance->styleNonce = base64_encode(random_bytes(16));

        $windowSession->set('csp_style_nonce', $instance->styleNonce);

        Session::getSession()->write();
    }

    public static function addHeader(Response $response)
    {
        $instance = static::getInstance();
        $styleNonce = $instance->styleNonce;

        if (! $response->getHeader('X-Icinga-WindowId')) {
            $response->setHeader('X-Icinga-WindowId', Window::getInstance()->getId(), true);
        }
        $cspValue = '';

        if (! empty($styleNonce)) {
            $cspValue .= "style-src 'self' 'nonce-$styleNonce';";
        }

        $response->setHeader('Content-Security-Policy', $cspValue, true);
    }
}
