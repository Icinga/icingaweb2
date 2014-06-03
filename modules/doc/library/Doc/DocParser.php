<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}

namespace Icinga\Module\Doc;

require_once 'vendor/Parsedown/Parsedown.php';

use ArrayIterator;
use RunetimeException;

class FileLockingIterator extends ArrayIterator
{
    public function next()
    {
        $this->current()->flock(LOCK_UN);
        parent::next();
    }

    public function valid()
    {
        if (!parent::valid()) {
            return false;
        }
        $fileInfo = $this->current();
        try {
            $fileObject = $fileInfo->openFile();
        } catch (RuntimeException $e) {
            throw new DocException($e->getMessage());
        }
        if ($fileObject->flock(LOCK_SH) === false) {
            throw new DocException('Couldn\'t get the lock');
        }
        $this[$this->key()] = $fileObject;
        return true;
    }
}

use IteratorAggregate;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class DocIterator implements IteratorAggregate
{
    protected $fileInfos;

    public function __construct($path)
    {
        $iter = new RecursiveIteratorIterator(
            new MarkdownFileIterator(
                new RecursiveDirectoryIterator($path)
            )
        );
        $fileInfos = iterator_to_array($iter);
        natcasesort($fileInfos);
        $this->fileInfos = $fileInfos;
    }

    public function getIterator()
    {
        return new FileLockingIterator($this->fileInfos);
    }
}

use Parsedown;
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
            throw new DocException('Doc directory `' . $path .'\' does not exist');
        }
        if (! is_readable($path)) {
            throw new NotReadableError('Doc directory `' . $path .'\' is not readable');
        }
        $this->path = $path;
    }

    /**
     * Retrieve the table of contents
     *
     * @return  DocTocHtmlRenderer
     */
    public function getToc()
    {
        $tocStack = array((object) array(
            'level' => 0,
            'node'  => new DocToc()
        ));
        foreach (new DocIterator($this->path) as $fileObject) {
            $line = null;
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
                    $node = end($tocStack)->node->appendChild(
                        (object) array(
                            'id'        => $id,
                            'title'     => $header,
                            'nofollow'  => $nofollow
                        )
                    );
                    $tocStack[] = (object) array(
                        'level' => $level,
                        'node'  => $node
                    );
                }
            }
        }
        return new DocTocHtmlRenderer($tocStack[0]->node);
    }

    /**
     * Retrieve doc as HTML converted from markdown files sorted by filename and the table of contents
     *
     * @return  array
     * @throws  DocException
     */
    public function getDocAndToc()
    {
        $cat = array();
        $tocStack = array((object) array(
            'level' => 0,
            'node'  => new DocToc()
        ));
        foreach (new DocIterator($this->path) as $fileObject) {
            $line = null;
            $sectionTitle = null;
            while (! $fileObject->eof()) {
                // Save last line for setext-style headers
                $lastLine   = $line;
                $line       = $fileObject->fgets();
                $header     = $this->extractHeader($line, $lastLine);
                if ($header !== null) {
                    list($header, $level) = $header;
                    if ($sectionTitle === null) {
                        // The first header is the section's title
                        $sectionTitle = $header;
                    }
                    $id         = $this->extractHeaderId($header);
                    $nofollow   = false;
                    $this->reduceToc($tocStack, $level);
                    if ($id === null) {
                        $path = array();
                        foreach (array_slice($tocStack, 1) as $entity) {
                            $path[] = $entity->node->getValue()->title;
                        }
                        $path[]         = $header;
                        $id             = implode('-', $path);
                        $nofollow       = true;
                    }
                    $id     = urlencode(str_replace('.', '&#46;', strip_tags($id)));
                    $node   = end($tocStack)->node->appendChild(
                        (object) array(
                            'id'        => $id,
                            'title'     => $header,
                            'nofollow'  => $nofollow
                        )
                    );
                    $tocStack[]  = (object) array(
                        'level' => $level,
                        'node'  => $node
                    );
                    $line = '<a name="' . $id . '"></a>' . PHP_EOL . $line;
                }
                $cat[] = $line;
            }
        }
        $html = Parsedown::instance()->text(implode('', $cat));
        $html = preg_replace_callback(
            '#<pre><code class="language-php">(.*?)\</code></pre>#s',
            array($this, 'highlight'),
            $html
        );
        return array($html, new DocTocHtmlRenderer($tocStack[0]->node));
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
