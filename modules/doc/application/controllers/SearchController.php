<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

use \Zend_Controller_Action_Exception;
use Icinga\Application\Icinga;
use Icinga\Module\Doc\DocController;
use Icinga\Module\Doc\DocParser;
use Icinga\Module\Doc\Exception\DocException;
use Icinga\Module\Doc\Search\DocSearch;
use Icinga\Module\Doc\Search\DocSearchIterator;
use Icinga\Module\Doc\Renderer\DocSearchRenderer;

class Doc_SearchController extends DocController
{
    /**
     * Render search
     */
    public function indexAction()
    {
        $parser = new DocParser($this->getWebPath());
        $search = new DocSearchRenderer(
            new DocSearchIterator(
                $parser->getDocTree()->getIterator(),
                new DocSearch($this->params->get('q'))
            )
        );
        $search->setUrl('doc/icingaweb/chapter');
        if (strlen($this->params->get('q')) < 3) {
            $this->view->searches = array();
            return;
        }
        $searches = array(
            'Icinga Web 2' => $search
        );
        foreach (Icinga::app()->getModuleManager()->listEnabledModules() as $module) {
            if (($path = $this->getModulePath($module)) !== null) {
                try {
                    $parser = new DocParser($path);
                    $search = new DocSearchRenderer(
                        new DocSearchIterator(
                            $parser->getDocTree()->getIterator(),
                            new DocSearch($this->params->get('q'))
                        )
                    );
                } catch (DocException $e) {
                    continue;
                }
                $search
                    ->setUrl('doc/module/chapter')
                    ->setUrlParams(array('moduleName' => $module));
                $searches[$module] = $search;
            }
        }
        $this->view->searches = $searches;
    }

    /**
     * Get the path to a module's documentation
     *
     * @param   string  $module
     *
     * @return  string|null
     */
    protected function getModulePath($module)
    {
        if (is_dir(($path = Icinga::app()->getModuleManager()->getModuleDir($module, '/doc')))) {
            return $path;
        }
        if (($path = $this->Config()->get('documentation', 'modules')) !== null) {
            $path = str_replace('{module}', $module, $path);
            if (is_dir($path)) {
                return $path;
            }
        }
        return null;
    }

    /**
     * Get the path to Icinga Web 2's documentation
     *
     * @return  string
     *
     * @throws  Zend_Controller_Action_Exception    If Icinga Web 2's documentation is not available
     */
    protected function getWebPath()
    {
        $path = Icinga::app()->getBaseDir('doc');
        if (is_dir($path)) {
            return $path;
        }
        if (($path = $this->Config()->get('documentation', 'icingaweb2')) !== null) {
            if (is_dir($path)) {
                return $path;
            }
        }
        throw new Zend_Controller_Action_Exception(
            $this->translate('Documentation for Icinga Web 2 is not available'),
            404
        );
    }
}
