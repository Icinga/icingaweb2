<?php
// @codingStandardsIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Application\Icinga;
use Icinga\Module\Doc\Parser as DocParser;
use Icinga\Web\Controller\ActionController;

class Doc_IndexController extends ActionController
{
    /**
     * Display the application's documentation
     */
    public function indexAction()
    {
        $parser             = new DocParser();
        list($html, $toc)   = $parser->parseDirectory(Icinga::app()->getApplicationDir('/../doc'));
        $this->view->html   = $html;
        $this->view->toc    = $toc;
    }
}
// @codingStandardsIgnoreEnd
