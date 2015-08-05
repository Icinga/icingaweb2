<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\File\Ini\Dom;

class Directive
{
    /**
     * @var string
     */
    protected $key;

    /**
     * @var string
     */
    protected $value;

    /**
     * @var array
     */
    public $commentsPre;

    /**
     * @var string
     */
    public $commentPost;

    /**
     * @param   string    $key
     *
     * @throws  Exception
     */
    public function __construct($key)
    {
        $this->key = trim(str_replace("\n", ' ', $key));
        if (strlen($this->key) < 1) {
            throw new Exception(sprintf('Ini parser error: empty key.'));
        }
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param string    $value
     */
    public function setValue($value)
    {
        $this->value = trim(str_replace("\n", ' ', $value));
    }

    /**
     * @return string
     */
    public function render()
    {
        $str = '';
        if (! empty ($this->commentsPre)) {
            $comments = array();
            foreach ($this->commentsPre as $comment) {
                $comments[] = $comment->render();
            }
            $str = implode(PHP_EOL, $comments) . PHP_EOL;
        }
        $str .= sprintf('%s = "%s"', $this->key, $this->value);
        if (isset ($this->commentPost)) {
            $str .= ' ' . $this->commentPost->render();
        }
        return $str;
    }
}
