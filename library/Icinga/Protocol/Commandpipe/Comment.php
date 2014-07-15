<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Protocol\Commandpipe;

/**
 * Container for comment information that can be send to Icinga's external command pipe
 */
class Comment
{
    /**
     * Whether this comment is persistent or not
     *
     * @var bool
     */
    public $persistent;

    /**
     * The author of this comment
     *
     * @var string
     */
    public $author;

    /**
     * The text of this comment
     *
     * @var string
     */
    public $content;

    /**
     * Create a new comment object
     *
     * @param   string  $author         The name of the comment's author
     * @param   string  $content        The text for this comment
     * @param   bool    $persistent     Whether this comment should be persistent or not
     */
    public function __construct($author, $content, $persistent = false)
    {
        $this->author = $author;
        $this->content = $content;
        $this->persistent = $persistent;
    }

    /**
     * Return this comment's properties as list of command parameters
     *
     * @param   bool    $ignorePersistentFlag   Whether the persistent flag should be included or not
     * @return  array
     */
    public function getArguments($ignorePersistentFlag = false)
    {
        if ($ignorePersistentFlag) {
            return array($this->author, $this->content);
        } else {
            return array($this->persistent ? '1' : '0', $this->author, $this->content);
        }
    }
}
