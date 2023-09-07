<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Application\Icinga;
use Icinga\Web\Controller;
use Icinga\Web\FileCache;

/**
 * Deliver static content to clients
 */
class StaticController extends Controller
{
    /**
     * Static routes don't require authentication
     *
     * @var bool
     */
    protected $requiresAuthentication = false;

    /**
     * Disable layout rendering as this controller doesn't provide any html layouts
     */
    public function init()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout()->disableLayout();
    }

    /**
     * Return an image from a module's public folder
     */
    public function imgAction()
    {
        $imgRoot = Icinga::app()
            ->getModuleManager()
            ->getModule($this->getParam('module_name'))
            ->getBaseDir() . '/public/img/';

        $file = $this->getParam('file');
        $filePath = realpath($imgRoot . $file);

        if ($filePath === false || substr($filePath, 0, strlen($imgRoot)) !== $imgRoot) {
            $this->httpNotFound('%s does not exist', $file);
        }

        if (preg_match('/\.([a-z]+)$/i', $file, $m)) {
            $extension = $m[1];
            if ($extension === 'svg') {
                $extension = 'svg+xml';
            }
        } else {
            $extension = 'fixme';
        }

        $s = stat($filePath);
        $eTag = sprintf('%x-%x-%x', $s['ino'], $s['size'], (float) str_pad((string) $s['mtime'], 16, '0'));

        $this->getResponse()->setHeader(
            'Cache-Control',
            'public, max-age=1814400, stale-while-revalidate=604800',
            true
        );

        if ($this->getRequest()->getServer('HTTP_IF_NONE_MATCH') === $eTag) {
            $this->getResponse()
                ->setHttpResponseCode(304);
        } else {
            $this->getResponse()
                ->setHeader('ETag', $eTag)
                ->setHeader('Content-Type', 'image/' . $extension, true)
                ->setHeader('Last-Modified', gmdate('D, d M Y H:i:s', $s['mtime']) . ' GMT');

            readfile($filePath);
        }
    }
}
