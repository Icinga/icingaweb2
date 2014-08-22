<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

/* @var $this \Icinga\Application\Modules\Module */

$section = $this->menuSection($this->translate('Documentation'), array(
    'title'    => 'Documentation',
    'icon'     => 'img/icons/comment.png',
    'url'      => 'doc',
    'priority' => 80
));
