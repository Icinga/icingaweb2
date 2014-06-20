<?php

namespace Icinga\Web\View;

use Icinga\Authentication\Manager;
use Icinga\Web\Widget;

$this->addHelperFunction('auth', function () {
    return Manager::getInstance();
});

$this->addHelperFunction('widget', function ($name, $options = null) {
    return Widget::create($name, $options);
});

