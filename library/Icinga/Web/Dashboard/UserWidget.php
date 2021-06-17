<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Dashboard;

use Icinga\Web\Navigation\DashboardHome;

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
    private $owner = DashboardHome::DEFAULT_IW2_USER;

    /**
     * Set the owner of this widget
     *
     * @param $owner
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
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
}
