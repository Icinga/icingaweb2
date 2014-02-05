<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}

namespace Icinga\Module\Doc;

require_once 'vendor/Parsedown/Parsedown.php';

use \Exception;
use \SplStack;
use \RecursiveIteratorIterator;
use \RecursiveDirectoryIterator;
use \Parsedown;
use Icinga\Web\Menu;

/**
 * Parser for documentation written in Markdown
 */
class DocParser
{
    /**
     * Retrieve table of contents and HTML converted from all Markdown files in the given directory sorted by filename
     *
     * @param   $dir
     *
     * @return  array
     * @throws  Exception
     */
    public function parseDirectory($dir)
    {
        $iter = new RecursiveIteratorIterator(
            new MarkdownFileIterator(
                new RecursiveDirectoryIterator($dir)
            )
        );
        $fileInfos = iterator_to_array($iter);
        natcasesort($fileInfos);
        $cat    = array();
        $toc    = new Menu('doc');
        $stack  = new SplStack();
        foreach ($fileInfos as $fileInfo) {
            try {
                $fileObject = $fileInfo->openFile();
            } catch (RuntimeException $e) {
                throw new Exception($e->getMessage());
            }
            if ($fileObject->flock(LOCK_SH) === false) {
                throw new Exception('Couldn\'t get the lock');
            }
            while (!$fileObject->eof()) {
                $line = $fileObject->fgets();
                if ($line &&
                    $line[0] === '#' &&
                    preg_match('/^#+/', $line, $match) === 1
                ) {
                    // Atx-style
                    $level      = strlen($match[0]);
                    $heading    = str_replace('.', '&#46;', trim(strip_tags(substr($line, $level))));
                    $fragment   = urlencode($heading);
                    $line       = '<span id="' . $fragment . '">' . "\n" . $line;
                    $stack->rewind();
                    while ($stack->valid() && $stack->current()->level >= $level) {
                        $stack->pop();
                        $stack->next();
                    }
                    $parent = $stack->current();
                    if ($parent === null) {
                        $item = $toc->addChild($heading, array('url' => '#' . $fragment));
                    } else {
                        $item = $parent->item->addChild($heading, array('url' => '#' . $fragment));
                    }
                    $stack->push((object) array(
                        'level' => $level,
                        'item'  => $item
                    ));
                } elseif (
                    $line &&
                    ($line[0] === '=' || $line[0] === '-') &&
                    preg_match('/^[=-]+\s*$/', $line, $match) === 1
                ) {
                    // Setext
                    if ($match[0][0] === '=') {
                        // H1
                        $level = 1;
                    } else {
                        // H
                        $level = 2;
                    }
                    $heading    = trim(strip_tags(end($cat)));
                    $fragment   = urlencode($heading);
                    $line       = '<span id="' . $fragment . '">' . "\n" . $line;
                    $stack->rewind();
                    while ($stack->valid() && $stack->current()->level >= $level) {
                        $stack->pop();
                        $stack->next();
                    }
                    $parent = $stack->current();
                    if ($parent === null) {
                        $item = $toc->addChild($heading, array('url' => '#' . $fragment));
                    } else {
                        $item = $parent->item->addChild($heading,  array('url' => '#' . $fragment));
                    }
                    $stack->push((object) array(
                        'level' => $level,
                        'item'  => $item
                    ));
                }
                $cat[] = $line;
            }
            $fileObject->flock(LOCK_UN);
        }
        $html = Parsedown::instance()->parse(implode('', $cat));
        return array($html, $toc);
    }
}
