<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\File\Ini\Dom;

use Icinga\Exception\ConfigurationError;

class Section
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $directives = array();

    /**
     * @var array
     */
    public $commentsPre;

    /**
     * @var string
     */
    public $commentPost;

    /**
     * @param   string      $name
     */
    public function __construct($name)
    {
        $this->name = trim($name);
        if (strlen($this->name) < 1) {
            throw new ConfigurationError(sprintf('Ini file error: empty section identifier'));
        }
    }

    /**
     * @param Directive $directive
     */
    public function addDirective(Directive $directive)
    {
        $this->directives[$directive->getKey()] = $directive;
    }

    /**
     * @param string    $key
     */
    public function removeDirective($key)
    {
        unset ($this->directives[$key]);
    }

    /**
     * @param   string  $key
     *
     * @return  bool
     */
    public function hasDirective($key)
    {
        return isset($this->directives[$key]);
    }

    /**
     * @param $key  string
     *
     * @return Directive
     */
    public function getDirective($key)
    {
        return $this->directives[$key];
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function render()
    {
        $dirs = '';
        $i = 0;
        foreach ($this->directives as $directive) {
            $dirs .= (($i++ > 0 && ! empty($directive->commentsPre)) ? PHP_EOL : '') . $directive->render() . PHP_EOL;
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

    protected function sanitize($str)
    {
        $str = trim($str);
        $str = str_replace('\\', '\\\\', $str);
        $str = str_replace('"', '\\"', $str);
        $str = str_replace(']', '\\]', $str);
        $str = str_replace(';', '\\;', $str);
        return str_replace(PHP_EOL, ' ', $str);
    }
}
