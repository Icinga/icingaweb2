<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\View;

use Icinga\Authentication\Auth;
use Icinga\Web\Widget;

$this->addHelperFunction('auth', function () {
    return Auth::getInstance();
});

$this->addHelperFunction('widget', function ($name, $options = null) {
    return Widget::create($name, $options);
});
