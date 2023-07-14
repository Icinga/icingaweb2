<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Web\Widget\SearchDashboard;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Compat\CompatController;

/**
 * Search controller
 */
class SearchController extends CompatController
{
    public function indexAction()
    {
        $searchDashboard = new SearchDashboard();
        $searchDashboard->setUser($this->Auth()->getUser());

        $this->controls->setTabs($searchDashboard->getTabs());
        $this->addContent($searchDashboard->search($this->getParam('q')));
    }

    public function hintAction()
    {
        $this->getTabs()->disableLegacyExtensions();

        $this->addContent(new HtmlElement('h1', null, Text::create(t('I\'m ready to search, waiting for your input'))));

        $p = new HtmlElement('p');
        $p->addHtml(new HtmlElement('strong', null, Text::create(t('Hint') . ': ')));
        $p->addHtml(Text::create(t(
            'Please use the asterisk (*) as a placeholder for wildcard searches. For convenience I\'ll always add' .
            ' a wildcard in front and after your search string.'
        )));

        $this->addContent($p);
    }
}
