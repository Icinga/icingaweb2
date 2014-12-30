<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

/** @type $this \Icinga\Application\Modules\Module */

$section = $this->menuSection($this->translate('Documentation'), array(
    'title'    => 'Documentation',
    'icon'     => 'book',
    'url'      => 'doc',
    'priority' => 190
));

$section->add('Icinga Web 2', array(
    'url' => 'doc/icingaweb/toc',
));
$section->add('Module documentations', array(
    'url' => 'doc/module',
));
$section->add($this->translate('Developer - Style'), array(
    'url' => 'doc/style/guide',
    'priority' => 200,
));
