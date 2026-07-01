<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Web\View;

use Icinga\Util\StringHelper;
use Icinga\Web\Helper\Markdown;

$this->addHelperFunction('ellipsis', function ($string, $maxLength, $ellipsis = '...') {
    return StringHelper::ellipsis($string, $maxLength, $ellipsis);
});

$this->addHelperFunction('nl2br', function ($string) {
    return nl2br(str_replace(['\r\n', '\r', '\n'], '<br>', $string), false);
});

$this->addHelperFunction('markdown', function ($content, $containerAttribs = null) {
    if (! isset($containerAttribs['class'])) {
        $containerAttribs['class'] = 'markdown';
    } else {
        $containerAttribs['class'] .= ' markdown';
    }

    return '<section' . $this->propertiesToString($containerAttribs) . '>' . Markdown::text($content) . '</section>';
});

$this->addHelperFunction('markdownLine', function ($content, $containerAttribs = null) {
    if (! isset($containerAttribs['class'])) {
        $containerAttribs['class'] = 'markdown inline';
    } else {
        $containerAttribs['class'] .= ' markdown inline';
    }

    return '<section' . $this->propertiesToString($containerAttribs) . '>' .
        Markdown::line($content) . '</section>';
});
