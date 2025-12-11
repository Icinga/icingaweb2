<?php

/* Icinga Notifications Web | (c) 2025 Icinga GmbH | GPLv2 */

namespace Icinga\Web\Widget;

use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Widget\Icon;

/**
 * A warning box that indicates the schedule timezone. It should be used to warn
 * the user that the display timezone differs from the schedule timezone.
 */
class TwoFactorWarning extends BaseHtmlElement
{
    use Translation;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'two-factor-warning'];

    /**
     * @param string $timezone The schedule timezone
     */
    public function __construct()
    {
    }

    public function assemble(): void
    {
        $this->addHtml(new Icon('warning'));
        $this->addHtml(new HtmlElement(
            'p',
            null,
            new Text('Make sure to save the QR code or the secret for recovery purposes!')
        ));
    }
}
