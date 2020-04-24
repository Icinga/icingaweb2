<?php

namespace Icinga\Module\Dashboards\Web\Widget;

use InvalidArgumentException;
use ipl\Html\BaseHtmlElement;

use function ipl\Stdlib\get_php_type;

class DashboardWidget extends BaseHtmlElement
{
    /** @var iterable|null $dashlets of the dashboard */
    protected $dashlets;

    /** @var iterable|null $userDashlets of the private dashboard */
    protected $userDashlets;

    protected $defaultAttributes = ['class' => 'dashboard content'];

    protected $tag = 'div';

    /**
     * Create a new dashboard widget
     *
     * @param iterable|null $dashlets The dashlets of the dashboard
     *
     * @param iterable|null $userDashlets The private dashlet of the private dashboard
     *
     * @throws InvalidArgumentException If $dashlets is not iterable
     */
    public function __construct($dashlets = null, $userDashlets = null)
    {
        if (! is_iterable($dashlets)) {
            throw new InvalidArgumentException(sprintf(
                '%s expects parameter 1 to be iterable, got %s instead',
                __METHOD__,
                get_php_type($dashlets)
            ));
        }

        if (! is_iterable($userDashlets)) {
            throw new InvalidArgumentException(sprintf(
                '%s expects parameter 1 to be iterable, got %s instead',
                __METHOD__,
                get_php_type($userDashlets)
            ));
        }

        $this->dashlets = $dashlets;
        $this->userDashlets = $userDashlets;
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
        $dashlets = [];
        $userDashlets = [];

        if (! empty($this->dashlets)) {
            foreach ($this->dashlets as $dashlet) {
                if (! in_array($dashlet->name, $dashlets)) {
                    $this->add(new DashletWidget($dashlet));

                    $dashlets[] = $dashlet->name;
                }
            }
        }

        if (! empty($this->userDashlets)) {
            foreach ($this->userDashlets as $userDashlet) {
                if (! in_array($userDashlet->name, $userDashlets)) {
                    $this->add(new DashletWidget($userDashlet));

                    $userDashlets[] = $userDashlet->name;
                }
            }
        }
    }
}
