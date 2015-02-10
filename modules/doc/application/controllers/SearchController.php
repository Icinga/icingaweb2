<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

use \Zend_Controller_Action_Exception;
use Icinga\Application\Icinga;
use Icinga\Module\Doc\DocController;
use Icinga\Module\Doc\DocParser;
use Icinga\Module\Doc\Search\DocSearch;
use Icinga\Module\Doc\Search\DocSearchIterator;
use Icinga\Module\Doc\Search\DocSearchRenderer;

class Doc_SearchController extends DocController
{
    public function indexAction()
    {
        $parser = new DocParser($this->getPath());
        $search = new DocSearchRenderer(
            new DocSearchIterator(
                $parser->getDocTree()->getIterator(),
                new DocSearch($this->params->get('q'))
            )
        );
        $this->view->search = $search->setUrl('doc/icingaweb/chapter');
    }

    /**
     * Get the path to Icinga Web 2's documentation
     *
     * @return  string
     *
     * @throws  Zend_Controller_Action_Exception    If Icinga Web 2's documentation is not available
     */
    protected function getPath()
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
