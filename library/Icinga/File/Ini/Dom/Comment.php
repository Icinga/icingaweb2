<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\File\Ini\Dom;

/**
 * A single comment-line in an INI file
 */
class Comment
{
    /**
     * The comment text
     *
     * @var string
     */
    protected $content;

    /**
     * Set the text content of this comment
     *
     * @param $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Render this comment into INI markup
     *
     * @return string
     */
    public function render()
    {
        return ';' . $this->content;
    }
}
