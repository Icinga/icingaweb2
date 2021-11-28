<?php
/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard;

use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Web\Widget\Link;

class ProvidedDashlets extends BaseHtmlElement
{
    /**
     * Dashlets provided by all loaded modules
     *
     * @var Dashlet[]
     */
    protected $dashlets = [];

    protected $tag = 'table';

    protected $defaultAttributes = [
        'class'            => 'common-table table-row-selectable',
        'data-base-target' => '_next',
    ];

    public function __construct(array $dashlets)
    {
        $this->dashlets = $dashlets;
    }

    protected function assembleHeader()
    {
        $thead = HtmlElement::create('thead');
        $theadRow = HtmlElement::create('tr');

        $theadRow->addHtml(HtmlElement::create('th', null, t('Module')));
        $theadRow->addHtml(HtmlElement::create('th', null, t('Name')));
        $thead->addHtml($theadRow);

        return $thead;
    }

    protected function assembleBody()
    {
        $tbody = new HtmlElement('tbody');
        foreach ($this->dashlets as $module => $dashlets) {
            /** @var Dashlet $dashlet */
            foreach ($dashlets as $dashlet) {
                $row = HtmlElement::create('tr', ['data-dashlet-name' => $module . ':' . $dashlet->getName()]);

                $dashletLink = new Link($module, $dashlet->getUrl()->getRelativeUrl());
                $row->addHtml(HtmlElement::create('td', null, $dashletLink));
                $row->addHtml(HtmlElement::create('td', null, $dashlet->getName()));

                $row->addHtml(HtmlElement::create('td', [
                    'style' => 'text-align: center; width: 10px;'
                ], HtmlElement::create('a', null, HtmlElement::create('i', [
                    'class' => 'icon-pin pin-dashlets',
                    'title' => t('Pin a dashlet you want to create a new dashboard from. Double click on the icon to release it.')
                ]))));

                $tbody->add($row);
            }
        }

        if (empty($this->dashlets)) {
            $tbody->addHtml(HtmlElement::create('td', null, t('No provided dashlet found')));
        }

        return $tbody;
    }

    protected function assemble()
    {
        $this->addHtml($this->assembleHeader());
        $this->addHtml($this->assembleBody());
    }
}
