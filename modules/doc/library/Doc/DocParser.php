<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Doc;

use CachingIterator;
use SplFileObject;
use SplStack;
use Icinga\Data\Tree\SimpleTree;
use Icinga\Exception\NotReadableError;
use Icinga\Util\DirectoryIterator;
use Icinga\Module\Doc\Exception\DocException;

/**
 * Parser for documentation written in Markdown
 */
class DocParser
{
    /**
     * Internal identifier for Atx-style headers
     *
     * @var int
     */
    const HEADER_ATX = 1;

    /**
     * Internal identifier for Setext-style headers
     *
     * @var int
     */
    const HEADER_SETEXT = 2;

    /**
     * Path to the documentation
     *
     * @var string
     */
    protected $path;

    /**
     * Iterator over documentation files
     *
     * @var DirectoryIterator
     */
    protected $docIterator;

    /**
     * Create a new documentation parser for the given path
     *
     * @param   string $path        Path to the documentation
     *
     * @throws  DocException        If the documentation directory does not exist
     * @throws  NotReadableError    If the documentation directory is not readable
     */
    public function __construct($path)
    {
        if (! DirectoryIterator::isReadable($path)) {
            throw new DocException(
                mt('doc', 'Documentation directory \'%s\' is not readable'),
                $path
            );
        }
        $this->path = $path;
        $this->docIterator = new DirectoryIterator($path, 'md');
    }

    /**
     * Extract atx- or setext-style headers from the given lines
     *
     * @param   string $line
     * @param   string $nextLine
     *
     * @return  array|null An array containing the header and the header level or null if there's nothing to extract
     */
    protected function extractHeader($line, $nextLine)
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
            $headerStyle = static::HEADER_ATX;
        } elseif (
            $nextLine
            && ($nextLine[0] === '=' || $nextLine[0] === '-')
            && preg_match('/^[=-]+\s*$/', $nextLine, $match) === 1
        ) {
            // Setext
            $header = trim($line);
            if (! $header) {
                return null;
            }
            if ($match[0][0] === '=') {
                $level = 1;
            } else {
                $level = 2;
            }
            $headerStyle = static::HEADER_SETEXT;
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
        return array($header, $id, $level, $headerStyle);
    }

    /**
     * Get the documentation tree
     *
     * @return SimpleTree
     */
    public function getDocTree()
    {
        $tree = new SimpleTree();
        foreach ($this->docIterator as $filename) {
            $file = new SplFileObject($filename);
            $lastLine = null;
            $stack = new SplStack();
            $cachingIterator = new CachingIterator($file, CachingIterator::TOSTRING_USE_CURRENT);
            for ($cachingIterator->rewind(); $line = $cachingIterator->valid(); $cachingIterator->next()) {
                $fileIterator = $cachingIterator->getInnerIterator();
                $line = $cachingIterator->current();
                $header = $this->extractHeader($line, $fileIterator->valid() ? $fileIterator->current() : null);
                if ($header !== null) {
                    list($title, $id, $level, $headerStyle) = $header;
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
                    if ($headerStyle === static::HEADER_SETEXT) {
                        $cachingIterator->next();
                        continue;
                    }
                } else {
                    if ($stack->isEmpty()) {
                        $title = ucfirst($file->getBasename('.' . pathinfo($file->getFilename(), PATHINFO_EXTENSION)));
                        $id = $title;
                        if ($tree->getNode($id) !== null) {
                            $id = uniqid($id);
                        }
                        $section = new DocSection();
                        $section
                            ->setId($id)
                            ->setTitle($title)
                            ->setLevel(1)
                            ->setNoFollow(true);
                        $section->setChapter($section);
                        $tree->addChild($section);
                        $stack->push($section);
                    }
                    $stack->top()->appendContent($line);
                }
            }
        }
        return $tree;
    }
}
