<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

/** @var $this \Icinga\Application\Modules\Module */

$section = $this->menuSection(N_('Documentation'), [
    'title'    => 'Documentation',
    'icon'     => 'book',
    'url'      => 'doc',
    'priority' => 700
]);

$section->add('Icinga Web 2', [
    'url' => 'doc/icingaweb/toc',
]);
$section->add('Module documentations', [
    'url' => 'doc/module',
]);
$section->add(N_('Developer - Style'), [
    'url' => 'doc/style/guide',
    'priority' => 790
]);

$this->provideSearchUrl($this->translate('Doc'), 'doc/search', -10);
