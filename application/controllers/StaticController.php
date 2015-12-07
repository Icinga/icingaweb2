<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Web\Controller;
use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Web\FileCache;

/**
 * Delivery static content to clients
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

    public function gravatarAction()
    {
        $cache = FileCache::instance();
        $filename = md5(strtolower(trim($this->_request->getParam('email'))));
        $cacheFile = 'gravatar-' . $filename;
        header('Cache-Control: public');
        header('Pragma: cache');
        if ($etag = $cache->etagMatchesCachedFile($cacheFile)) {
            header("HTTP/1.1 304 Not Modified");
            return;
        }

        header('Content-Type: image/jpg');
        if ($cache->has($cacheFile)) {
            header('ETag: "' . $cache->etagForCachedFile($cacheFile) . '"');
            $cache->send($cacheFile);
            return;
        }
        $img = file_get_contents('http://www.gravatar.com/avatar/' . $filename . '?s=120&d=mm');
        $cache->store($cacheFile, $img);
        header('ETag: "' . $cache->etagForCachedFile($cacheFile) . '"');
        echo $img;
    }

    /**
     * Return an image from the application's or the module's public folder
     */
    public function imgAction()
    {
        // TODO(el): I think this action only retrieves images from modules
        $module = $this->_getParam('module_name');
        $file   = $this->_getParam('file');
        $basedir = Icinga::app()->getModuleManager()->getModule($module)->getBaseDir();

        $filePath = realpath($basedir . '/public/img/' . $file);

        if ($filePath === false) {
            $this->httpNotFound('%s does not exist', $filePath);
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
        header('Content-Type: image/' . $extension);
        header(sprintf('ETag: "%x-%x-%x"', $s['ino'], $s['size'], (float) str_pad($s['mtime'], 16, '0')));
        header('Cache-Control: public, max-age=3600');
        header('Pragma: cache');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $s['mtime']) . ' GMT');

        readfile($filePath);
    }
}
