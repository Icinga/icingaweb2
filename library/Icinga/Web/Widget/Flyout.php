<?php
/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Widget;

use Icinga\Web\Window;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;

class Flyout extends BaseHtmlElement
{

    protected $tag = 'div';

    protected $button;

    protected $mobileOnly;

    protected $defaultAttributes = [
        'class' => 'flyout-content'
    ];

    public function __construct($button = null, $mobileOnly = false)
    {
        $this->button = $button;
        if ($button === null) {
            $this->button = new HtmlElement('button', null, ['press me']);
        }

        $this->mobileOnly = $mobileOnly;
    }

    public function setButton($button)
    {
        $this->button = $button;
    }

    protected function assemble()
    {
        $wrapper = new HtmlElement('div', Attributes::create(['class' => 'flyout']));

        // $uniqueID = $this->Window()->getContainerId();
        $uniqueID = Window::generateID();
        $wrapper->addAttributes([
            'id' => 'icingaweb2-flyout-' . $uniqueID,
            'class' => $this->mobileOnly ? 'mobile-only' : ''
        ]);

        $wrapper->add($this);

        $html = new HtmlDocument();

        $this->prependWrapper($html->addHtml($wrapper, $this->button));
    }
}
