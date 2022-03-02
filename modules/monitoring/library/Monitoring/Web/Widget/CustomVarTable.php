<?php
/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Monitoring\Web\Widget;

use Icinga\Module\Monitoring\Hook\CustomVarRendererHook;
use Icinga\Module\Monitoring\Object\MonitoredObject;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Widget\Icon;

class CustomVarTable extends BaseHtmlElement
{
    /** @var iterable The variables */
    protected $data;

    /** @var ?MonitoredObject The object the variables are bound to */
    protected $object;

    /** @var Closure Callback to apply hooks */
    protected $hookApplier;

    /** @var array The groups as identified by hooks */
    protected $groups = [];

    /** @var string Header title */
    protected $headerTitle;

    /** @var int The nesting level */
    protected $level = 0;

    protected $tag = 'table';

    /** @var HtmlElement The table body */
    protected $body;

    protected $defaultAttributes = [
        'class' => ['custom-var-table', 'name-value-table']
    ];

    /**
     * Create a new CustomVarTable
     *
     * @param iterable $data
     * @param ?MonitoredObject $object
     */
    public function __construct($data, MonitoredObject $object = null)
    {
        $this->data = $data;
        $this->object = $object;
        $this->body = new HtmlElement('tbody');
    }

    /**
     * Set the header to show
     *
     * @param string $title
     *
     * @return $this
     */
    protected function setHeader($title)
    {
        $this->headerTitle = (string) $title;

        return $this;
    }

    /**
     * Add a new row to the body
     *
     * @param mixed $name
     * @param mixed $value
     *
     * @return void
     */
    protected function addRow($name, $value)
    {
        $this->body->addHtml(new HtmlElement(
            'tr',
            Attributes::create(['class' => "level-{$this->level}"]),
            new HtmlElement('th', null, Html::wantHtml($name)),
            new HtmlElement('td', null, Html::wantHtml($value))
        ));
    }

    /**
     * Render a variable
     *
     * @param mixed $name
     * @param mixed $value
     *
     * @return void
     */
    protected function renderVar($name, $value)
    {
        if ($this->object !== null && $this->level === 0) {
            list($name, $value, $group) = call_user_func($this->hookApplier, $name, $value);
            if ($group !== null) {
                $this->groups[$group][] = [$name, $value];
                return;
            }
        }

        $isArray = is_array($value);
        if (! $isArray && $value instanceof \stdClass) {
            $value = (array) $value;
            $isArray = true;
        }

        switch (true) {
            case $isArray && is_int(key($value)):
                $this->renderArray($name, $value);
                break;
            case $isArray:
                $this->renderObject($name, $value);
                break;
            default:
                $this->renderScalar($name, $value);
        }
    }

    /**
     * Render an array
     *
     * @param mixed $name
     * @param array $array
     *
     * @return void
     */
    protected function renderArray($name, array $array)
    {
        $numItems = count($array);
        $name = (new HtmlDocument())->addHtml(
            Html::wantHtml($name),
            Text::create(' (Array)')
        );

        $this->addRow($name, sprintf(tp('%d item', '%d items', $numItems), $numItems));

        ++$this->level;

        ksort($array);
        foreach ($array as $key => $value) {
            $this->renderVar("[$key]", $value);
        }

        --$this->level;
    }

    /**
     * Render an object (associative array)
     *
     * @param mixed $name
     * @param array $object
     *
     * @return void
     */
    protected function renderObject($name, array $object)
    {
        $numItems = count($object);
        $this->addRow($name, sprintf(tp('%d item', '%d items', $numItems), $numItems));

        ++$this->level;

        ksort($object);
        foreach ($object as $key => $value) {
            $this->renderVar($key, $value);
        }

        --$this->level;
    }

    /**
     * Render a scalar
     *
     * @param mixed $name
     * @param mixed $value
     *
     * @return void
     */
    protected function renderScalar($name, $value)
    {
        if ($value === '') {
            $value = new HtmlElement('span', Attributes::create(['class' => 'empty']), Text::create(t('empty string')));
        }

        $this->addRow($name, $value);
    }

    /**
     * Render a group
     *
     * @param string $name
     * @param iterable $entries
     *
     * @return void
     */
    protected function renderGroup($name, $entries)
    {
        $table = new self($entries);

        $wrapper = $this->getWrapper();
        if ($wrapper === null) {
            $wrapper = new HtmlDocument();
            $wrapper->addHtml($this);
            $this->prependWrapper($wrapper);
        }

        $wrapper->addHtml($table->setHeader($name));
    }

    protected function assemble()
    {
        if ($this->object !== null) {
            $this->hookApplier = CustomVarRendererHook::prepareForObject($this->object);
        }

        if ($this->headerTitle !== null) {
            $this->getAttributes()
                ->add('class', 'collapsible')
                ->add('data-visible-height', 100)
                ->add('data-toggle-element', 'thead')
                ->add(
                    'id',
                    preg_replace('/\s+/', '-', strtolower($this->headerTitle)) . '-customvars'
                );

            $this->addHtml(new HtmlElement('thead', null, new HtmlElement(
                'tr',
                null,
                new HtmlElement(
                    'th',
                    Attributes::create(['colspan' => 2]),
                    new HtmlElement(
                        'span',
                        null,
                        new Icon('angle-right'),
                        new Icon('angle-down')
                    ),
                    Text::create($this->headerTitle)
                )
            )));
        }

        if (is_array($this->data)) {
            ksort($this->data);
        }

        foreach ($this->data as $name => $value) {
            $this->renderVar($name, $value);
        }

        $this->addHtml($this->body);

        // Hooks can return objects as replacement for keys, hence a generator is needed for group entries
        $genGenerator = function ($entries) {
            foreach ($entries as list($key, $value)) {
                yield $key => $value;
            }
        };

        foreach ($this->groups as $group => $entries) {
            $this->renderGroup($group, $genGenerator($entries));
        }
    }
}
