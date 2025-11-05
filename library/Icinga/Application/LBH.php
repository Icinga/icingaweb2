<?php

namespace Icinga\Application;

use Icinga\Application\Hook\LoginButton\LoginButton;
use Icinga\Application\Hook\LoginButtonHook;
use ipl\Html\Attributes;
use ipl\Html\Html;

class LBH extends LoginButtonHook
{
    public function getButtons(): array
    {
        return [
            new LoginButton(
                function () {
                    var_dump(42);
                    die;
                },
                Html::wantHtml('LolCat'),
                new Attributes(['title' => 'https://al2klimov.de/teapot.jpg'])
            ),
            new LoginButton(
                function () {
                    var_dump(43);
                    die;
                },
                Html::wantHtml('LolDog'),
                new Attributes(['title' => 'https://al2klimov.de/teapot.jpg'])
            )
        ];
    }
}
