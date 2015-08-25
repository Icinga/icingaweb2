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

        return $img;
    }
}
