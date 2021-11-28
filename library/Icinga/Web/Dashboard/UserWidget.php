<?php

/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard;

use Icinga\Web\Widget\Dashboard;

trait UserWidget
{
    /**
     * Flag if widget is created by an user
     *
     * @var bool
     */
    protected $userWidget = false;

    /**
     * Whether this widget overrides a system widget
     *
     * @var bool
     */
    protected $override = false;

    /**
     * A user this widget belongs to
     *
     * @var string
     */
    protected $owner;

    /**
     * A type of this widget
     *
     * @var string
     */
    protected $type = Dashboard::SYSTEM;

    /**
     * Disabled flag of a pane
     *
     * @var bool
     */
    protected $disabled = false;

    /**
     * Set the owner of this widget
     *
     * @param $owner
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get the owner of this widget
     *
     * @return string
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Set whether this pane overrides a system pane
     *
     * @param  boolean $value
     *
     * @return $this
     */
    public function setOverride($value)
    {
        $this->override = (bool)$value;

        return $this;
    }

    /**
     * Get whether this widget overrides a system widget
     *
     * @return bool
     */
    public function isOverridingWidget()
    {
        return $this->override;
    }

    /**
     * Set the user widget flag
     *
     * @param boolean $userWidget
     */
    public function setUserWidget($userWidget = true)
    {
        $this->userWidget = (bool) $userWidget;

        return $this;
    }

    /**
     * Getter for user widget flag
     *
     * @return boolean
     */
    public function isUserWidget()
    {
        return $this->userWidget;
    }

    /**
     * Set type of this widget
     *
     * @param $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type of this widget
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Setter for disabled
     *
     * @param bool $disabled
     */
    public function setDisabled(bool $disabled)
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * Getter for disabled
     *
     * @return bool
     */
    public function isDisabled()
    {
        return $this->disabled;
    }
}
