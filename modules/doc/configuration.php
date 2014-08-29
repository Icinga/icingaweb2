<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

/* @var $this \Icinga\Application\Modules\Module */

$section = $this->menuSection('documentation', $this->translate('Documentation'), array(
    'icon'     => 'img/icons/comment.png',
    'url'      => 'doc',
    'priority' => 80
));
