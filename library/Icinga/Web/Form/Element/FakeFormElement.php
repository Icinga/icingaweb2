<?php

/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Form\Element;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\Web\Widget\Icon;

/**
 * This is an element that integrates {@see ValidHtml} into an IPL Web {@see CompatForm}.
 *
 * The html will be rendered as follows:
 * <pre>
 * <div class='control-group'>
 *     <div class='control-label-group'>
 *         <!-- if label is set -->
 *         <label>$this->label</label>
 *     </div>
 *     <div class='fake-form-element'>
 *         $this->content
 *     </div>
 *     <!-- if description is set -->
 *     <i class="icon fa-info-circle control-info fa" role="image"
 *        title="Please enter the token from your authenticator app to verify your setup."></i>
 * </div>
 * </pre>
 */
class FakeFormElement extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'control-group'];

    /**
     * @param ValidHtml $content     The element to add to the form
     * @param ?string   $label       The label to render in the control-label-group element
     * @param ?string   $description The description to show for the element
     */
    public function __construct(
        protected ValidHtml $content,
        protected ?string $label = null,
        protected ?string $description = null
    ) {
    }

    protected function assemble(): void
    {
        $this->addHtml(
            HtmlElement::create(
                'div',
                Attributes::create(['class' => 'control-label-group']),
                $this->label ? HtmlElement::create('label', null, new Text($this->label)) : null
            )
        );
        $this->addHtml(
            HtmlElement::create('div', Attributes::create(['class' => 'fake-form-element']), $this->content)
        );
        if ($this->description) {
            $this->addHtml(
                new Icon('info-circle', Attributes::create(['class' => 'control-info', 'title' => $this->description]))
            );
        }
    }
}
