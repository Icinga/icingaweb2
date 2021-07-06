<?php
/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\View;

use Icinga\Application\Hook\HealthHook;
use ipl\Html\FormattedString;
use ipl\Html\HtmlElement;
use ipl\Html\Table;
use ipl\Web\Common\BaseTarget;
use ipl\Web\Widget\Link;
use Traversable;

class AppHealth extends Table
{
    use BaseTarget;

    protected $defaultAttributes = ['class' => ['app-health', 'common-table', 'table-row-selectable']];

    /** @var Traversable */
    protected $data;

    public function __construct(Traversable $data)
    {
        $this->data = $data;

        $this->setBaseTarget('_next');
    }

    protected function assemble()
    {
        foreach ($this->data as $row) {
            $this->add(Table::tr([
                Table::th(HtmlElement::create('span', ['class' => [
                    'ball',
                    'ball-size-xl',
                    $this->getStateClass($row->state)
                ]])),
                Table::td([
                    new HtmlElement('header', null, FormattedString::create(
                        t('%s by %s is %s', '<check> by <module> is <state-text>'),
                        $row->url
                            ? new Link(HtmlElement::create('span', null, $row->name), $row->url)
                            : HtmlElement::create('span', null, $row->name),
                        HtmlElement::create('span', null, $row->module),
                        HtmlElement::create('span', null, $this->getStateText($row->state))
                    )),
                    HtmlElement::create('section', null, $row->message)
                ])
            ]));
        }
    }

    protected function getStateClass($state)
    {
        if ($state === null) {
            $state = HealthHook::STATE_UNKNOWN;
        }

        switch ($state) {
            case HealthHook::STATE_OK:
                return 'state-ok';
            case HealthHook::STATE_WARNING:
                return 'state-warning';
            case HealthHook::STATE_CRITICAL:
                return 'state-critical';
            case HealthHook::STATE_UNKNOWN:
                return 'state-unknown';
        }
    }

    protected function getStateText($state)
    {
        if ($state === null) {
            $state = t('UNKNOWN');
        }

        switch ($state) {
            case HealthHook::STATE_OK:
                return t('OK');
            case HealthHook::STATE_WARNING:
                return t('WARNING');
            case HealthHook::STATE_CRITICAL:
                return t('CRITICAL');
            case HealthHook::STATE_UNKNOWN:
                return t('UNKNOWN');
        }
    }
}
