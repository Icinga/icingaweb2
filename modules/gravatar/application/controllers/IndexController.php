<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

use Icinga\Web\Controller;
use Icinga\Web\FileCache;

class Gravatar_IndexController extends Controller
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

    public function indexAction()
    {
        $cache = FileCache::instance();
        $filename = md5(strtolower(trim($this->_request->getParam('email'))));
        $cacheFile = 'gravatar-' . $filename;

        header('Cache-Control: public');
        header('Pragma: cache');
        if ($cache->etagMatchesCachedFile($cacheFile)) {
            $this
                ->getResponse()
                ->setHttpResponseCode(304)
                ->sendHeaders();
            return;
        }

        if ($cache->has($cacheFile)) {
            $img = $cache->get($cacheFile);
        } elseif (false === $img = @file_get_contents('http://www.gravatar.com/avatar/' . $filename . '?s=120&d=mm')) {
            $this
                ->getResponse()
                ->setHttpResponseCode(500)
                ->sendHeaders();
            return;
        } else {
            $cache->store($cacheFile, $img);
        }

        $this
            ->getResponse()
            ->setHeader('ETag', $cache->etagForCachedFile($cacheFile))
            ->setHeader('Content-Type', 'image/jpg')
            ->setBody($img)
            ->sendResponse();
    }
}
