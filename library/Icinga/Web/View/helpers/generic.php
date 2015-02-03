<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

namespace Icinga\Web\View;

use Icinga\Authentication\Manager;
use Icinga\Web\Widget;

$this->addHelperFunction('auth', function () {
    return Manager::getInstance();
});

$this->addHelperFunction('widget', function ($name, $options = null) {
    return Widget::create($name, $options);
});

