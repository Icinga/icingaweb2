<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\File\Ini\Dom;

use Icinga\Exception\ConfigurationError;

/**
 * A key value pair in a Section
 */
class Directive
{
    /**
     * The value of this configuration directive
     *
     * @var string
     */
    protected $key;

    /**
     * The immutable name of this configuration directive
     *
     * @var string
     */
    protected $value;

    /**
     * Comments added one line before this directive
     *
     * @var Comment[]   The comment lines
     */
    protected $commentsPre = null;

    /**
     * Comment added at the end of the same line
     *
     * @var Comment
     */
    protected $commentPost = null;

    /**
     * @param   string    $key  The name of this configuration directive
     *
     * @throws  ConfigurationError
     */
    public function __construct($key)
    {
        $this->key = trim($key);
        if (strlen($this->key) < 1) {
            throw new ConfigurationError(sprintf('Ini error: empty directive key.'));
        }
    }

    /**
     * Return the name of this directive
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Return the value of this configuration directive
     *
     * @return  string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set the value of this configuration directive
     *
     * @param   string      $value
     */
    public function setValue($value)
    {
        $this->value = trim($value);
    }

    /**
     * Set the comments to be rendered on the line before this directive
     *
     * @param   Comment[]   $comments
     */
    public function setCommentsPre(array $comments)
    {
        $this->commentsPre = $comments;
    }

    /**
     * Return the comments to be rendered on the line before this directive
     *
     * @return Comment[]
     */
    public function getCommentsPre()
    {
        return $this->commentsPre;
    }

    /**
     * Set the comment rendered on the same line of this directive
     *
     * @param   Comment     $comment
     */
    public function setCommentPost(Comment $comment)
    {
        $this->commentPost = $comment;
    }

    /**
     * Render this configuration directive into INI markup
     *
     * @return  string
     */
    public function render()
    {
        $str = '';
        if (! empty($this->commentsPre)) {
            $comments = array();
            foreach ($this->commentsPre as $comment) {
                $comments[] = $comment->render();
            }
            $str = implode(PHP_EOL, $comments) . PHP_EOL;
        }
        $str .= sprintf('%s = "%s"', $this->sanitizeKey($this->key), $this->sanitizeValue($this->value));
        if (isset($this->commentPost)) {
            $str .= ' ' . $this->commentPost->render();
        }
        return $str;
    }

    /**
     * Assure that the given identifier contains no newlines and pending or trailing whitespaces
     *
     * @param   $str    The string to sanitize
     *
     * @return string
     */
    protected function sanitizeKey($str)
    {
        return trim(str_replace(PHP_EOL, ' ', $str));
    }

    /**
     * Escape the significant characters in directive values, normalize line breaks and assure that
     * the character contains no linebreaks
     *
     * @param   $str    The string to sanitize
     *
     * @return mixed|string
     */
    protected function sanitizeValue($str)
    {
        $str = trim($str);
        $str = str_replace('\\', '\\\\', $str);
        $str = str_replace('"', '\\"', $str);

        // line breaks in the value should always match the current system EOL sequence
        // to assure editable configuration files
        $str = preg_replace("/(\r\n)|(\n)/", PHP_EOL, $str);
        return $str;
    }
}
