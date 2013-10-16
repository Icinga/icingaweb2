<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}

namespace Icinga\Module\Doc;

require_once 'vendor/Michelf/Markdown.php';
require_once 'vendor/Michelf/MarkdownExtra.php';

use \RecursiveIteratorIterator;
use \RecursiveDirectoryIterator;
use \Exception;
use Michelf\MarkdownExtra;

/**
 * Markdown parser
 */
class Parser
{
    /**
     * Retrieve table of contents and HTML converted from all markdown files in the given directory sorted by filename
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
        $cat = array();
        $toc = array();
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
                    $heading    = trim(strip_tags(substr($line, $level)));
                    $fragment   = urlencode($heading);
                    $toc[]      = array(
                        'heading'   => $heading,
                        'level'     => $level,
                        'fragment'  => $fragment
                    );
                    $line = '<span id="' . $fragment . '">' . "\n" . $line;
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
                        // H2
                        $level = 2;
                    }
                    $heading    = trim(strip_tags(end($cat)));
                    $fragment   = urlencode($heading);
                    $toc[]      = array(
                        'heading'   => $heading,
                        'level'     => $level,
                        'fragment'  => $fragment
                    );
                    $line = '<span id="' . $fragment . '">' . "\n" . $line;
                }
                $cat[] = $line;
            }
            $fileObject->flock(LOCK_UN);
        }
        $html   = MarkdownExtra::defaultTransform(implode('', $cat));
        return array($html, $toc);
    }
}
