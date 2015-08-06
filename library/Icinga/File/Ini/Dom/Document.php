<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\File\Ini\Dom;

class Document
{
    /**
     * @var array
     */
    protected $sections = array();

    /**
     * @var array
     */
    public $commentsDangling;

    /**
     * @param Section $section
     */
    public function addSection(Section $section)
    {
        $this->sections[$section->getName()] = $section;
    }

    /**
     * @param   string  $name
     *
     * @return  bool
     */
    public function hasSection($name)
    {
        return isset($this->sections[$name]);
    }

    /**
     * @param   string  $name
     *
     * @return Section
     */
    public function getSection($name)
    {
        return $this->sections[$name];
    }

    /**
     * @param string  $name
     * @param Section $section
     *
     * @return Section
     */
    public function setSection($name, Section $section)
    {
        return $this->sections[$name] = $section;
    }

    /**
     * @param string $name
     */
    public function removeSection($name)
    {
        unset ($this->sections[$name]);
    }

    /**
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
}
