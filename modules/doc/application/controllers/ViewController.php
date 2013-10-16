<?php
// @codingStandardsIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Application\Icinga;
use Icinga\Module\Doc\Parser as DocParser;
use Icinga\Web\Controller\ActionController;

class Doc_ViewController extends ActionController
{
    public function init()
    {
        $this->_helper->viewRenderer->setRender('view');
    }

    /**
     * Populate view
     *
     * @param string $dir
     */
    private function populateView($dir)
    {
        $parser = new DocParser();
        list($html, $toc)   = $parser->parseDirectory($dir);
        $this->view->html   = $html;
        $this->view->toc    = $toc;
    }

    public function indexAction()
    {
        $this->populateView(Icinga::app()->getApplicationDir('/../doc'));
    }

    /**
     * Provide run-time dispatching of module documentation
     *
     * @param string    $methodName
     * @param array     $args
     */
    public function __call($methodName, $args)
    {
        $moduleManager = Icinga::app()->getModuleManager();
        $moduleName = substr($methodName, 0, -6);  // Strip 'Action' suffix
        if ($moduleManager->hasEnabled($moduleName)) {
            $this->populateView($moduleManager->getModuleDir($moduleName, '/doc'));
        } else {
            parent::__call($methodName, $args);
        }
    }
}
// @codingStandardsIgnoreEnd
