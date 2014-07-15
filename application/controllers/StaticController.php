<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use \Zend_Controller_Action_Exception as ActionException;
use Icinga\Web\Controller\ActionController;
use Icinga\Application\Icinga;
use Icinga\Logger\Logger;

/**
 * Delivery static content to clients
 */
class StaticController extends ActionController
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
        $img = file_get_contents('http://www.gravatar.com/avatar/' . md5(strtolower(trim($this->_request->getParam('email')))) . '?s=200&d=mm');
        header('image/jpeg');
        echo $img;
    }

    /**
     * Return an image from the application's or the module's public folder
     */
    public function imgAction()
    {
        $module = $this->_getParam('module_name');
        // TODO: This is more than dangerous, must be fixed!!
        $file   = $this->_getParam('file');

        $basedir = Icinga::app()->getModuleManager()->getModule($module)->getBaseDir();

        $filePath = $basedir . '/public/img/' . $file;
        if (! file_exists($filePath)) {
            throw new ActionException(sprintf(
                '%s does not exist',
                $filePath
            ), 404);
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
        header('Last-Modified: ' . gmdate(
            'D, d M Y H:i:s',
            $s['mtime']
        ) . ' GMT');

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
            if (!Icinga::app()->getModuleManager()->hasEnabled($module)) {
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

        if (!file_exists($filePath)) {
            Logger::error(
                'Non-existing frontend component "' . $module . '/' . $file
                . '" was requested, which would resolve to the the path: ' . $filePath
            );
            echo '/** Module has no js files **/';
            return;
        }
        $response = $this->getResponse();
        $response->setHeader('Content-Type', 'text/javascript');
        $this->setCacheHeader(3600);

        $response->setHeader(
            'Last-Modified',
            gmdate(
                'D, d M Y H:i:s',
                filemtime($filePath)
            ) . ' GMT'
        );

        readfile($filePath);
    }

    /**
     * Set cache header for this response
     *
     * @param integer $maxAge The maximum age to set
     */
    private function setCacheHeader($maxAge)
    {
        $this->_response->setHeader('Cache-Control', 'max-age=3600', true);
        $this->_response->setHeader('Pragma', 'cache', true);
        $this->_response->setHeader(
            'Expires',
            gmdate(
                'D, d M Y H:i:s',
                time()+3600
            ) . ' GMT',
            true
        );
    }

    public function stylesheetAction()
    {
        $lessCompiler = new \Icinga\Web\LessCompiler();
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

        $this->_response->setHeader('Content-Type', 'text/css');
        $this->setCacheHeader(3600);

        $lessCompiler->printStack();
    }
}
