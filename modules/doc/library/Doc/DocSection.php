<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Doc;

use Icinga\Data\Tree\TreeNode;

/**
 * A section of a documentation
 */
class DocSection extends TreeNode
{
    /**
     * The title of the section
     *
     * @type string
     */
    protected $title;

    /**
     * The header level
     *
     * @type int
     */
    protected $level;

    /**
     * Whether to instruct search engines to not index the link to the section
     *
     * @type bool
     */
    protected $noFollow;

    /**
     * The content of the section
     *
     * @type array
     */
    protected $content = array();

    /**
     * Set the title of the section
     *
     * @param   string  $title  Title of the section
     *
     * @return  $this
     */
    public function setTitle($title)
    {
        $this->title = (string) $title;
        return $this;
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
     * Set the header level
     *
     * @param   int     $level  Header level
     *
     * @return  $this
     */
    public function setLevel($level)
    {
        $this->level = (int) $level;
        return $this;
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
     * Set whether to instruct search engines to not index the link to the section
     *
     * @param   bool    $noFollow   Whether to instruct search engines to not index the link to the section
     *
     * @return  $this
     */
    public function setNoFollow($noFollow = true)
    {
        $this->noFollow = (bool) $noFollow;
        return $this;
    }

    /**
     * Get whether to instruct search engines to not index the link to the section
     *
     * @return bool
     */
    public function getNoFollow()
    {
        return $this->noFollow;
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
