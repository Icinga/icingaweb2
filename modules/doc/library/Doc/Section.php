<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

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
    protected $noFollow;

    /**
     * The ID of the chapter the section is part of
     *
     * @var string
     */
    protected $chapterId;

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
     * @param bool      $noFollow       Whether to instruct search engines to not index the link to the section
     * @param string    $chapterId      The ID of the chapter the section is part of
     */
    public function __construct($id, $title, $level, $noFollow, $chapterId)
    {
        $this->id = $id;
        $this->title = $title;
        $this->level = $level;
        $this->noFollow = $noFollow;
        $this->chapterId= $chapterId;
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
    public function isNoFollow()
    {
        return $this->noFollow;
    }

    /**
     * The ID of the chapter the section is part of
     *
     * @return string
     */
    public function getChapterId()
    {
        return $this->chapterId;
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
