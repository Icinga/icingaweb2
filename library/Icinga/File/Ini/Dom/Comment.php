<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\File\Ini\Dom;

class Comment
{
    /**
     * @var string
     */
    public $content;

    /**
     * @return string
     */
    public function render()
    {
        return ';' . $this->content;
    }
}
