<?php

namespace Icinga\Application;

use Icinga\Application\Hook\LoginButton\LoginButton;
use Icinga\Application\Hook\LoginButtonHook;

class LBH implements LoginButtonHook
{
    public function getButtons(): array
    {
        return [new LoginButton(function () {
        }, 'LolCat')];
    }
}
