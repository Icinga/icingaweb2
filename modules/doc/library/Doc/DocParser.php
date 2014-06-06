<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Doc;

require_once 'IcingaVendor/Parsedown/Parsedown.php';

use Parsedown;
use Icinga\Data\Tree\Node;
use Icinga\Exception\NotReadableError;

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
     * Create a new documentation parser for the given path
     *
     * @param   string $path Path to the documentation
     *
     * @throws  DocException
     * @throws  NotReadableError
     */
    public function __construct($path)
    {
        if (! is_dir($path)) {
            throw new DocException('Doc directory `' . $path . '\' does not exist');
        }
        if (! is_readable($path)) {
            throw new NotReadableError('Doc directory `' . $path . '\' is not readable');
        }
        $this->path = $path;
    }

    /**
     * Retrieve the table of contents
     *
     * @return Node
     */
    public function getToc()
    {
        $tocStack = array((object) array(
            'level' => 0,
            'node'  => new Node()
        ));
        foreach (new DocIterator($this->path) as $fileObject) {
            $line = null;
            $currentChapterName = null;
            while (! $fileObject->eof()) {
                // Save last line for setext-style headers
                $lastLine = $line;
                $line = $fileObject->fgets();
                $header = $this->extractHeader($line, $lastLine);
                if ($header !== null) {
                    list($header, $level) = $header;
                    $id = $this->extractHeaderId($header);
                    $nofollow = false;
                    $this->reduceToc($tocStack, $level);
                    if ($id === null) {
                        $path = array();
                        foreach (array_slice($tocStack, 1) as $entity) {
                            $path[] = $entity->node->getValue()->title;
                        }
                        $path[] = $header;
                        $id = implode('-', $path);
                        $nofollow = true;
                    }
                    $id = urlencode(str_replace('.', '&#46;', strip_tags($id)));
                    if ($currentChapterName === null) {
                        // The first header is the chapter's name
                        $currentChapterName = $id;
                        $id = null;
                    }
                    $node = end($tocStack)->node->appendChild(
                        (object) array(
                            'id'            => $id,
                            'title'         => $header,
                            'nofollow'      => $nofollow,
                            'chapterName'   => $currentChapterName
                        )
                    );
                    $tocStack[] = (object) array(
                        'level' => $level,
                        'node'  => $node
                    );
                }
            }
        }
        return $tocStack[0]->node;
    }

    /**
     * Retrieve a chapter
     *
     * @param   string $chapterName
     *
     * @return  string
     */
    public function getChapter($chapterName)
    {
        $cat = array();
        $tocStack = array((object) array(
            'level' => 0,
            'node'  => new Node()
        ));
        $chapterFound = false;
        foreach (new DocIterator($this->path) as $fileObject) {
            $line = null;
            $currentChapterName = null;
            $chapter = array();
            while (! $fileObject->eof()) {
                // Save last line for setext-style headers
                $lastLine = $line;
                $line = $fileObject->fgets();
                $header = $this->extractHeader($line, $lastLine);
                if ($header !== null) {
                    list($header, $level) = $header;
                    $id = $this->extractHeaderId($header);
                    $this->reduceToc($tocStack, $level);
                    if ($id === null) {
                        $path = array();
                        foreach (array_slice($tocStack, 1) as $entity) {
                            $path[] = $entity->node->getValue()->title;
                        }
                        $path[] = $header;
                        $id = implode('-', $path);
                    }
                    $id = urlencode(str_replace('.', '&#46;', strip_tags($id)));
                    if ($currentChapterName === null) {
                        $currentChapterName = $id;
                        $id = null;
                    }
                    $node = end($tocStack)->node->appendChild(
                        (object) array(
                            'title' => $header
                        )
                    );
                    $tocStack[] = (object) array(
                        'level' => $level,
                        'node'  => $node
                    );
                    $line = '<a name="' . $id . '"></a>' . PHP_EOL . $line;
                }
                $chapter[] = $line;
            }
            if ($currentChapterName === $chapterName) {
                $chapterFound = true;
                $cat = $chapter;
            }
            if (! $chapterFound) {
                $cat = array_merge($cat, $chapter);
            }
        }
        $html = preg_replace_callback(
            '#<pre><code class="language-php">(.*?)\</code></pre>#s',
            array($this, 'highlight'),
            Parsedown::instance()->text(implode('', $cat))
        );
        return $html;
    }

    /**
     * Syntax highlighting for PHP code
     *
     * @param   $match
     *
     * @return  string
     */
    protected function highlight($match)
    {
        return highlight_string(htmlspecialchars_decode($match[1]), true);
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
        if ($line &&
            $line[0] === '#' &&
            preg_match('/^#+/', $line, $match) === 1
        ) {
            // Atx-style
            $level  = strlen($match[0]);
            $header = trim(substr($line, $level));
            if (! $header) {
                return null;
            }
        } elseif (
            $line &&
            ($line[0] === '=' || $line[0] === '-') &&
            preg_match('/^[=-]+\s*$/', $line, $match) === 1
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
        return array($header, $level);
    }

    /**
     * Extract header id in an a or a span tag
     *
     * @param   string  &$header
     *
     * @return  id|null
     */
    protected function extractHeaderId(&$header)
    {
        if ($header[0] === '<' &&
            preg_match('#(?:<(?P<tag>a|span) id="(?P<id>.+)"></(?P=tag)>)#u', $header, $match)
        ) {
            $header = str_replace($match[0], '', $header);
            return $match['id'];
        }
        return null;
    }

    /**
     * Reduce the toc stack to the given level
     *
     * @param array &$tocStack
     * @param int   $level
     */
    protected function reduceToc(array &$tocStack, $level) {
        while (end($tocStack)->level >= $level) {
            array_pop($tocStack);
        }
    }
}
