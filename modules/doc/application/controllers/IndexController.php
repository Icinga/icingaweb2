<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Module\Doc\Controller as DocController;

use Icinga\Module\Doc\DocParser;

class Doc_IndexController extends DocController
{
    protected $parser;


    public function init()
    {
        $module = null;
        $this->parser = new DocParser($module);
    }


    public function tocAction()
    {
        // Temporary workaround
        list($html, $toc)   = $this->parser->getDocumentation();
        $this->view->toc = $toc;
    }

    /**
     * Display the application's documentation
     */
    public function indexAction()
    {
        $this->populateView();
    }
}
