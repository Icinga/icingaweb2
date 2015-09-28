<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Web\Controller;
use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Web\FileCache;
use Icinga\Web\LessCompiler;

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

    /**
     * Return a javascript file from the application's or the module's public folder
     */
    public function javascriptAction()
    {
        $module = $this->_getParam('module_name');
        $file   = $this->_getParam('file');

        if ($module == 'app') {
            $basedir = Icinga::app()->getApplicationDir('../public/js/icinga/components/');
            $filePath = $basedir . $file;
        } else {
            if (! Icinga::app()->getModuleManager()->hasEnabled($module)) {
                Logger::error(
                    'Non-existing frontend component "' . $module . '/' . $file
                    . '" was requested. The module "' . $module . '" does not exist or is not active.'
                );
                echo "/** Module not enabled **/";
                return;
            }
            $basedir = Icinga::app()->getModuleManager()->getModule($module)->getBaseDir();
            $filePath = $basedir . '/public/js/' . $file;
        }

        if (! file_exists($filePath)) {
            Logger::error(
                'Non-existing frontend component "' . $module . '/' . $file
                . '" was requested, which would resolve to the the path: ' . $filePath
            );
            echo '/** Module has no js files **/';
            return;
        }
        $response = $this->getResponse();
        $response->setHeader('Content-Type', 'text/javascript');
        $this->setCacheHeader();

        $response->setHeader(
            'Last-Modified',
            gmdate('D, d M Y H:i:s', filemtime($filePath)) . ' GMT'
        );

        readfile($filePath);
    }

    /**
     * Set cache header for the response
     *
     * @param int $maxAge The maximum age to set
     */
    private function setCacheHeader($maxAge = 3600)
    {
        $maxAge = (int) $maxAge;
        $this
            ->getResponse()
            ->setHeader('Cache-Control', sprintf('max-age=%d', $maxAge), true)
            ->setHeader('Pragma', 'cache', true)
            ->setHeader(
                'Expires',
                gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT',
                true
            );
    }

    /**
     * Send application's and modules' CSS
     */
    public function stylesheetAction()
    {
        $lessCompiler = new LessCompiler();
        $moduleManager = Icinga::app()->getModuleManager();

        $publicDir = realpath(dirname($_SERVER['SCRIPT_FILENAME']));

        $lessCompiler->addItem($publicDir . '/css/vendor');
        $lessCompiler->addItem($publicDir . '/css/icinga');

        foreach ($moduleManager->getLoadedModules() as $moduleName) {
            $cssDir = $moduleName->getCssDir();

            if (is_dir($cssDir)) {
                $lessCompiler->addItem($cssDir);
            }
        }

        $this->getResponse()->setHeader('Content-Type', 'text/css');
        $this->setCacheHeader(3600);

        $lessCompiler->printStack();
    }
}
