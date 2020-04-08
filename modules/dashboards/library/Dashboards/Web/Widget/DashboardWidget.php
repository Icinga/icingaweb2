<?php

namespace Icinga\Module\Dashboards\Web\Widget;

use InvalidArgumentException;
use ipl\Html\BaseHtmlElement;

use function ipl\Stdlib\get_php_type;

class DashboardWidget extends BaseHtmlElement
{
    /** @var iterable Dashlets of the dashboard */
    protected $dashlets;

    protected $defaultAttributes = ['class' => 'dashboard content'];

    protected $tag = 'div';

    /**
     * Create a new dashboard widget
     *
     * @param iterable $dashlets The dashlets of the dashboard
     *
     * @throws InvalidArgumentException If $dashlets is not iterable
     */
    public function __construct($dashlets)
    {
        if (! is_iterable($dashlets)) {
            throw new InvalidArgumentException(sprintf(
                '%s expects parameter 1 to be iterable, got %s instead',
                __METHOD__,
                get_php_type($dashlets)
            ));
        }

        $this->dashlets = $dashlets;
    }

    /**
     * @inheritDoc
     *
     * ipl/Html lacks a call to {@link BaseHtmlElement::ensureAssembled()} here. This override is subject to remove once
     * ipl/Html incorporates this fix.
     */
    public function isEmpty()
    {
        $this->ensureAssembled();

        return parent::isEmpty();
    }

    protected function assemble()
    {
        foreach ($this->dashlets as $dashlet) {
            $this->add(new DashletWidget($dashlet));
        }
    }
}
