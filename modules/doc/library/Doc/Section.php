<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Doc;

use Icinga\Data\Identifiable;

/**
 * A section of a documentation
 */
class Section implements Identifiable
{
    /**
     * The ID of the section
     *
     * @var string
     */
    protected $id;

    /**
     * The title of the section
     *
     * @var string
     */
    protected $title;

    /**
     * The header level
     *
     * @var int
     */
    protected $level;

    /**
     * Whether to instruct search engines to not index the link to the section
     *
     * @var bool
     */
    protected $nofollow;

    /**
     * The title of the chapter the section is part of
     *
     * @var string
     */
    protected $chapterTitle;

    /**
     * The content of the section
     *
     * @var array
     */
    protected $content = array();

    /**
     * Create a new section
     *
     * @param string    $id             The ID of the section
     * @param string    $title          The title of the section
     * @param int       $level          The header level
     * @param bool      $nofollow       Whether to instruct search engines to not index the link to the section
     * @param string    $chapterTitle   The title of the chapter the section is part of
     */
    public function __construct($id, $title, $level, $nofollow, $chapterTitle)
    {
        $this->id = $id;
        $this->title = $title;
        $this->level = $level;
        $this->nofollow = $nofollow;
        $this->chapterTitle= $chapterTitle;
    }

    /**
     * Get the ID of the section
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the title of the section
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Get the header level
     *
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Whether to instruct search engines to not index the link to the section
     *
     * @return bool
     */
    public function isNofollow()
    {
        return $this->nofollow;
    }

    /**
     * The title of the chapter the section is part of
     *
     * @return string
     */
    public function getChapterTitle()
    {
        return $this->chapterTitle;
    }

    /**
     * Append content
     *
     * @param string $content
     */
    public function appendContent($content)
    {
        $this->content[] = $content;
    }

    /**
     * Get the content of the section
     *
     * @return array
     */
    public function getContent()
    {
        return $this->content;
    }
}
