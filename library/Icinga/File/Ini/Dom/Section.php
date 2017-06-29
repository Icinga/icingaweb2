<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\File\Ini\Dom;

use Icinga\Exception\ConfigurationError;

/**
 * A section in an INI file
 */
class Section
{
    /**
     * The immutable name of this section
     *
     * @var string
     */
    protected $name;

    /**
     * All configuration directives of this section
     *
     * @var Directive[]
     */
    protected $directives = array();

    /**
     * Comments added one line before this section
     *
     * @var Comment[]
     */
    protected $commentsPre;

    /**
     * Comment added at the end of the same line
     *
     * @var string
     */
    protected $commentPost;

    /**
     * @param   string  $name       The immutable name of this section
     *
     * @throws  ConfigurationError  When the section name is empty
     */
    public function __construct($name)
    {
        $this->name = trim($name);
        if (strlen($this->name) < 1) {
            throw new ConfigurationError(sprintf('Ini file error: empty section identifier'));
        }
    }

    /**
     * Append a directive to the end of this section
     *
     * @param   Directive   $directive  The directive to append
     */
    public function addDirective(Directive $directive)
    {
        $this->directives[$directive->getKey()] = $directive;
    }

    /**
     * Remove the directive with the given name
     *
     * @param   string      $key        They name of the directive to remove
     */
    public function removeDirective($key)
    {
        unset($this->directives[$key]);
    }

    /**
     * Return whether this section has a directive with the given key
     *
     * @param   string  $key            The name of the directive
     *
     * @return  bool
     */
    public function hasDirective($key)
    {
        return isset($this->directives[$key]);
    }

    /**
     * Get the directive with the given key
     *
     * @param $key  string
     *
     * @return Directive
     */
    public function getDirective($key)
    {
        return $this->directives[$key];
    }

    /**
     * Return the name of this section
     *
     * @return string   The name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the comments to be rendered on the line before this section
     *
     * @param   Comment[]   $comments
     */
    public function setCommentsPre(array $comments)
    {
        $this->commentsPre = $comments;
    }

    /**
     * Set the comment rendered on the same line of this section
     *
     * @param   Comment     $comment
     */
    public function setCommentPost(Comment $comment)
    {
        $this->commentPost = $comment;
    }

    /**
     * Render this section into INI markup
     *
     * @return string
     */
    public function render()
    {
        $dirs = '';
        $i = 0;
        foreach ($this->directives as $directive) {
            $comments = $directive->getCommentsPre();
            $dirs .= (($i++ > 0 && ! empty($comments)) ? PHP_EOL : '')
                    . $directive->render() . PHP_EOL;
        }
        $cms = '';
        if (! empty($this->commentsPre)) {
            foreach ($this->commentsPre as $comment) {
                $comments[] = $comment->render();
            }
            $cms = implode(PHP_EOL, $comments) . PHP_EOL;
        }
        $post = '';
        if (isset($this->commentPost)) {
            $post = ' ' . $this->commentPost->render();
        }
        return $cms . sprintf('[%s]', $this->sanitize($this->name)) . $post . PHP_EOL . $dirs;
    }

    /**
     * Escape the significant characters in sections and normalize line breaks
     *
     * @param   $str    The string to sanitize
     *
     * @return  mixed
     */
    protected function sanitize($str)
    {
        $str = trim($str);
        $str = str_replace('\\', '\\\\', $str);
        $str = str_replace('"', '\\"', $str);
        $str = str_replace(']', '\\]', $str);
        $str = str_replace(';', '\\;', $str);
        return str_replace(PHP_EOL, ' ', $str);
    }

    /**
     * Convert $this to an array
     *
     * @return  array
     */
    public function toArray()
    {
        $a = array();
        foreach ($this->directives as $directive) {
            $a[$directive->getKey()] = $directive->getValue();
        }
        return $a;
    }
}
