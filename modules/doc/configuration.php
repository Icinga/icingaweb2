<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

/** @var $this \Icinga\Application\Modules\Module */

$section = $this->menuSection($this->translate('Documentation'), array(
    'title'    => 'Documentation',
    'icon'     => 'book',
    'url'      => 'doc',
    'priority' => 700
));

$section->add('Icinga Web 2', array(
    'url' => 'doc/icingaweb/toc',
));
$section->add('Module documentations', array(
    'url' => 'doc/module',
));
$section->add($this->translate('Developer - Style'), array(
    'url' => 'doc/style/guide',
    'priority' => 790
));

$this->provideSearchUrl($this->translate('Doc'), 'doc/search', -10);
