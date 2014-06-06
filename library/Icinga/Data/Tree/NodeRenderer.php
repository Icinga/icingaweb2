<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}

namespace Icinga\Data\Tree;

use Exception;
use RecursiveIteratorIterator;
use RuntimeException;

/**
 * A not yet customizable node renderer
 */
class NodeRenderer extends RecursiveIteratorIterator
{
    protected $content = array();

    public function __construct(NodeInterface $node)
    {
        parent::__construct($node, RecursiveIteratorIterator::SELF_FIRST);
    }

    public function beginIteration()
    {
        $this->content[] = '<ul>';
    }

    public function endIteration()
    {
        $this->content[] = '</ul>';
    }

    public function beginChildren()
    {
        $this->content[] = '<ul>';
    }

    public function endChildren()
    {
        $this->content[] = '</ul>';
    }

    public function render($callback)
    {
        if (! is_callable($callback)) {
            throw new RuntimeException('Callable expected');
        }
        foreach ($this as $node) {
            try {
                $content = call_user_func($callback, $node);
            } catch (Exception $e) {
                throw new RuntimeException($e);
            }
            $this->content[] = $content;
        }
        return implode("\n", $this->content);
    }
}
