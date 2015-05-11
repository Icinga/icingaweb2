<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Doc;

use LogicException;
use SplStack;
use Icinga\Data\Tree\SimpleTree;
use Icinga\Exception\NotReadableError;
use Icinga\Module\Doc\Exception\DocEmptyException;
use Icinga\Module\Doc\Exception\DocException;

/**
 * Parser for documentation written in Markdown
 */
class DocParser
{
    /**
     * Path to the documentation
     *
     * @var string
     */
    protected $path;

    /**
     * Iterator over documentation files
     *
     * @var DocIterator
     */
    protected $docIterator;

    /**
     * Create a new documentation parser for the given path
     *
     * @param   string $path        Path to the documentation
     *
     * @throws  DocException        If the documentation directory does not exist
     * @throws  NotReadableError    If the documentation directory is not readable
     * @throws  DocEmptyException   If the documentation directory is empty
     */
    public function __construct($path)
    {
        if (! is_dir($path)) {
            throw new DocException(
                sprintf(mt('doc', 'Documentation directory \'%s\' does not exist'), $path)
            );
        }
        if (! is_readable($path)) {
            throw new DocException(
                sprintf(mt('doc', 'Documentation directory \'%s\' is not readable'), $path)
            );
        }
        $docIterator = new DocIterator($path);
        if ($docIterator->count() === 0) {
            throw new DocEmptyException(
                sprintf(
                    mt(
                        'doc',
                        'Documentation directory \'%s\' does not contain any non-empty Markdown file (\'.md\' suffix)'
                    ),
                    $path
                )
            );
        }
        $this->path = $path;
        $this->docIterator = $docIterator;
    }

    /**
     * Extract atx- or setext-style headers from the given lines
     *
     * @param   string $line
     * @param   string $lastLine
     *
     * @return  array|null An array containing the header and the header level or null if there's nothing to extract
     */
    protected function extractHeader($line, $lastLine)
    {
        if (! $line) {
            return null;
        }
        $header = null;
        if ($line
            && $line[0] === '#'
            && preg_match('/^#+/', $line, $match) === 1
        ) {
            // Atx
            $level  = strlen($match[0]);
            $header = trim(substr($line, $level));
            if (! $header) {
                return null;
            }
        } elseif (
            $line
            && ($line[0] === '=' || $line[0] === '-')
            && preg_match('/^[=-]+\s*$/', $line, $match) === 1
        ) {
            // Setext
            $header = trim($lastLine);
            if (! $header) {
                return null;
            }
            if ($match[0][0] === '=') {
                $level = 1;
            } else {
                $level = 2;
            }
        }
        if ($header === null) {
            return null;
        }
        if ($header[0] === '<'
            && preg_match('#(?:<(?P<tag>a|span) (?:id|name)="(?P<id>.+)"></(?P=tag)>)\s*#u', $header, $match)
        ) {
            $header = str_replace($match[0], '', $header);
            $id = $match['id'];
        } else {
            $id = null;
        }
        return array($header, $id, $level);
    }

    /**
     * Get the documentation tree
     *
     * @return SimpleTree
     */
    public function getDocTree()
    {
        $tree = new SimpleTree();
        $stack = new SplStack();
        foreach ($this->docIterator as $fileInfo) {
            /** @var $fileInfo \SplFileInfo */
            $file = $fileInfo->openFile();
            $lastLine = null;
            foreach ($file as $line) {
                $header = $this->extractHeader($line, $lastLine);
                if ($header !== null) {
                    list($title, $id, $level) = $header;
                    while (! $stack->isEmpty() && $stack->top()->getLevel() >= $level) {
                        $stack->pop();
                    }
                    if ($id === null) {
                        $path = array();
                        foreach ($stack as $section) {
                            /** @var $section DocSection */
                            $path[] = $section->getTitle();
                        }
                        $path[] = $title;
                        $id = implode('-', $path);
                        $noFollow = true;
                    } else {
                        $noFollow = false;
                    }
                    if ($tree->getNode($id) !== null) {
                        $id = uniqid($id);
                    }
                    $section = new DocSection();
                    $section
                        ->setId($id)
                        ->setTitle($title)
                        ->setLevel($level)
                        ->setNoFollow($noFollow);
                    if ($stack->isEmpty()) {
                        $section->setChapter($section);
                        $tree->addChild($section);
                    } else {
                        $section->setChapter($stack->bottom());
                        $tree->addChild($section, $stack->top());
                    }
                    $stack->push($section);
                } else {
                    if ($stack->isEmpty()) {
                        throw new LogicException('Heading required');
                    }
                    $stack->top()->appendContent($line);
                }
                // Save last line for setext-style headers
                $lastLine = $line;
            }
        }
        return $tree;
    }
}
