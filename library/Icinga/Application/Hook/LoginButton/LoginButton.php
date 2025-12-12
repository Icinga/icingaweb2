<?php
/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Application\Hook\LoginButton;

use Closure;
use ipl\Html\Attributes;
use ipl\Html\ValidHtml;

readonly class LoginButton
{
    /**
     * Create an additional button to be displayed below the login form
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
