<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Authentication;

use Closure;
use ipl\Html\Attributes;
use ipl\Html\ValidHtml;

readonly class LoginButton
{
    /**
     * Create an additional button to be displayed on the login page
     *
     * @param Closure $onClick What to do if the button is pressed
     * @param ValidHtml $content What to show in the button, see also {@link Html::wantHtml()}
     * @param ?Attributes $attributes Additional <button> attributes, e.g. title
     */
    public function __construct(
        public Closure $onClick,
        public ValidHtml $content,
        public ?Attributes $attributes = null
    ) {
    }
}
