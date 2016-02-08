<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Widget;

use Icinga\Forms\Control\LimiterControlForm;

/**
 * Limiter control widget
 */
class Limiter extends AbstractWidget
{
    /**
     * Default limit for this instance
     *
     * @var int|null
     */
    protected $defaultLimit;

    /**
     * Get the default limit
     *
     * @return int|null
     */
    public function getDefaultLimit()
    {
        return $this->defaultLimit;
    }

    /**
     * Set the default limit
     *
     * @param   int $defaultLimit
     *
     * @return  $this
     */
    public function setDefaultLimit($defaultLimit)
    {
        $this->defaultLimit = (int) $defaultLimit;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        $control = new LimiterControlForm();
        $control
            ->setDefaultLimit($this->defaultLimit)
            ->handleRequest();
        return (string)$control;
    }
}
