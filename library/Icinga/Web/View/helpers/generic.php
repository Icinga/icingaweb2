<?php

namespace Icinga\Web\View;

use Icinga\Authentication\Manager;

$this->addHelperFunction('auth', function () {
    return Manager::getInstance();
});

