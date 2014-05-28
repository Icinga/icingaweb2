<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}

namespace Icinga\Module\Doc;

use RecursiveIteratorIterator;

class DocTocHtmlRenderer extends RecursiveIteratorIterator
{
    protected $html = array();

    public function __construct(DocToc $toc)
    {
        parent::__construct($toc, RecursiveIteratorIterator::SELF_FIRST);
    }

    public function beginIteration()
    {
        $this->html[] = '<ul>';
    }

    public function endIteration()
    {
        $this->html[] = '</ul>';
    }

    public function beginChildren()
    {
        $this->html[] = '<ul>';
    }

    public function endChildren()
    {
        $this->html[] = '</ul>';
    }

    public function render($callback)
    {
        foreach ($this as $node) {
            $this->html[] = $callback($node->getValue());
        }
        return implode("\n", $this->html);
    }
}
