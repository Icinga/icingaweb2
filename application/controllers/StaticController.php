<?php
// @codingStandardsIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

# namespace Icinga\Web\Form;

use Icinga\Web\ActionController;
use Icinga\Application\Icinga;
use Zend_Controller_Action_Exception as ActionException;

/**
 * Class StaticController
 * @package Icinga\Web\Form
 */
class StaticController extends ActionController
{

    /**
     * @var bool
     */
    protected $handlesAuthentication = true;

    /**
     *
     */
    public function init()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout()->disableLayout();
    }

    /**
     * @return array
     */
    private function getModuleList()
    {
        $modules = Icinga::app()->moduleManager()->getLoadedModules();

        // preliminary static definition
        $result = array();
        foreach ($modules as $name => $module) {
            $hasJs = file_exists($module->getBasedir() . "/public/js/$name.js");
            $result[] = array(
                'name' => $name,
                'active' => true,
                'type' => 'generic',
                'behaviour' => $hasJs
            );
        }
        return $result;
    }

    /**
     *
     */
    public function modulelistAction()
    {

        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout()->disableLayout();
        $this->getResponse()->setHeader("Content-Type", "application/json");
        echo "define(function() { return " . json_encode($this->getModuleList(), true) . "; })";
        exit;
    }

    /**
     * @throws \Zend_Controller_Action_Exception
     */
    public function javascriptAction()
    {
        $module = $this->_getParam('moduleName');
        $file = $this->_getParam('file');
        $basedir = Icinga::app()->getModule($module)->getBaseDir();

        $filePath = $basedir . '/public/js/' . $file;
        if (!file_exists($filePath)) {
            throw new ActionException(
                sprintf(
                    '%s does not exist',
                    $filePath
                ),
                404
            );
        }
        $hash = md5_file($filePath);
        $response = $this->getResponse();
        $response->setHeader('ETag', $hash);
        $response->setHeader('Content-Type', 'application/javascript');
        $response->setHeader('Cache-Control', 'max-age=3600', true);
        $response->setHeader(
            'Last-Modified',
            gmdate(
                'D, d M Y H:i:s',
                filemtime($filePath)
            ) . ' GMT'
        );

        if ($hash === $this->getRequest()->getHeader('If-None-Match')) {
            $response->setHttpResponseCode(304);
            return;
        } else {
            readfile($filePath);
        }
        // TODO: get rid of exit:
        exit;
    }
}

// @codingStandardsIgnoreEnd