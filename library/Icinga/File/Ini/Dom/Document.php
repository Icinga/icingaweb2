<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\File\Ini\Dom;

class Document
{
    /**
     * The sections of this INI file
     *
     * @var Section[]
     */
    protected $sections = array();

    /**
     * The comemnts at file end that belong to no particular section
     *
     * @var Comment[]
     */
    protected $commentsDangling;

    /**
     * Append a section to the end of this INI file
     *
     * @param Section $section
     */
    public function addSection(Section $section)
    {
        $this->sections[$section->getName()] = $section;
    }

    /**
     * Return whether this INI file has the section with the given key
     *
     * @param   string  $name
     *
     * @return  bool
     */
    public function hasSection($name)
    {
        return isset($this->sections[trim($name)]);
    }

    /**
     * Return the section with the given name
     *
     * @param   string  $name
     *
     * @return Section
     */
    public function getSection($name)
    {
        return $this->sections[trim($name)];
    }

    /**
     * Set the section with the given name
     *
     * @param string  $name
     * @param Section $section
     *
     * @return Section
     */
    public function setSection($name, Section $section)
    {
        return $this->sections[trim($name)] = $section;
    }

    /**
     * Remove the section with the given name
     *
     * @param string $name
     */
    public function removeSection($name)
    {
        unset($this->sections[trim($name)]);
    }

    /**
     * Set the dangling comments at file end that belong to no particular directive
     *
     * @param Comment[] $comments
     */
    public function setCommentsDangling(array $comments)
    {
        $this->commentsDangling = $comments;
    }

    /**
     * Get the dangling comments at file end that belong to no particular directive
     *
     * @return array
     */
    public function getCommentsDangling()
    {
        return $this->commentsDangling;
    }

    /**
     * Render this document into the corresponding INI markup
     *
     * @return string
     */
    public function render()
    {
        $sections = array();
        foreach ($this->sections as $section) {
            $sections []=  $section->render();
        }
        $str = implode(PHP_EOL, $sections);
        if (! empty($this->commentsDangling)) {
            foreach ($this->commentsDangling as $comment) {
                $str .= PHP_EOL . $comment->render();
            }
        }
        return $str;
    }

    /**
     * Convert $this to an array
     *
     * @return  array
     */
    public function toArray()
    {
        $a = array();
        foreach ($this->sections as $section) {
            $a[$section->getName()] = $section->toArray();
        }
        return $a;
    }
}
