<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Widget\Dashboard;

interface UserWidget
{
    /**
     * Set the user widget flag
     *
     * @param boolean $userWidget
     */
    public function setUserWidget($userWidget = true);

    /**
     * Getter for user widget flag
     *
     * @return boolean
     */
    public function isUserWidget();
}
