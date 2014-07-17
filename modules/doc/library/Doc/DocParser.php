<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Doc;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Parsedown;
use Icinga\Application\Icinga;
use Icinga\Web\Menu;
use Icinga\Web\Url;

require_once 'IcingaVendor/Parsedown/Parsedown.php';

/**
 * Parser for documentation written in Markdown
 */
class DocParser
{
    protected $dir;

    protected $module;

    /**
     * Create a new documentation parser for the given module or the application
     *
     * @param   string $module
     *
     * @throws  DocException
     */
    public function __construct($module = null)
    {
        if ($module === null) {
            $dir = Icinga::app()->getApplicationDir('/../doc');
        } else {
            $mm = Icinga::app()->getModuleManager();
            if (!$mm->hasInstalled($module)) {
                throw new DocException('Module is not installed');
            }
            if (!$mm->hasEnabled($module)) {
                throw new DocException('Module is not enabled');
            }
            $dir = $mm->getModuleDir($module, '/doc');
        }
        if (!is_dir($dir)) {
            throw new DocException('Doc directory does not exist');
        }
        $this->dir      = $dir;
        $this->module   = $module;
    }

    /**
     * Retrieve table of contents and HTML converted from markdown files sorted by filename
     *
     * @return  array
     * @throws  DocException
     */
    public function getDocumentation()
    {
        $iter = new RecursiveIteratorIterator(
            new MarkdownFileIterator(
                new RecursiveDirectoryIterator($this->dir)
            )
        );
        $fileInfos = iterator_to_array($iter);
        natcasesort($fileInfos);
        $cat    = array();
        $toc    = array((object) array(
            'level' => 0,
            'item'  => new Menu('doc')
        ));
        $itemPriority = 1;
        foreach ($fileInfos as $fileInfo) {
            try {
                $fileObject = $fileInfo->openFile();
            } catch (RuntimeException $e) {
                throw new DocException($e->getMessage());
            }
            if ($fileObject->flock(LOCK_SH) === false) {
                throw new DocException('Couldn\'t get the lock');
            }
            $line = null;
            while (!$fileObject->eof()) {
                // Save last line for setext-style headers
                $lastLine   = $line;
                $line       = $fileObject->fgets();
                $header     = $this->extractHeader($line, $lastLine);
                if ($header !== null) {
                    list($header, $level)   = $header;
                    $id                     = $this->extractHeaderId($header);
                    $attribs                = array();
                    $this->reduceToc($toc, $level);
                    if ($id === null) {
                        $path = array();
                        foreach (array_slice($toc, 1) as $entry) {
                            $path[] = $entry->item->getTitle();
                        }
                        $path[]         = $header;
                        $id             = implode('-', $path);
                        $attribs['rel'] = 'nofollow';
                    }
                    $id     = urlencode(str_replace('.', '&#46;', strip_tags($id)));
                    $item   = end($toc)->item->addChild(
                        $id,
                        array(
                            'url' => Url::fromPath(
                                'doc/module/view',
                                array(
                                    'name' => $this->module
                                )
                            )->setAnchor($id)->getRelativeUrl(),
                            'title'     => htmlspecialchars($header),
                            'priority'  => $itemPriority++,
                            'attribs'   => $attribs
                        )
                    );
                    $toc[]  = ((object) array(
                        'level' => $level,
                        'item'  => $item
                    ));
                    $line = '<a name="' . $id . '"></a>' . PHP_EOL . $line;
                }
                $cat[] = $line;
            }
            $fileObject->flock(LOCK_UN);
        }
        $html = Parsedown::instance()->parse(implode('', $cat));
        $html = preg_replace_callback(
            '#<pre><code class="language-php">(.*?)\</code></pre>#s',
            array($this, 'highlight'),
            $html
        );
        return array($html, $toc[0]->item);
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
        if (!$line) {
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
            if (!$header) {
                return null;
            }
        } elseif (
            $line &&
            ($line[0] === '=' || $line[0] === '-') &&
            preg_match('/^[=-]+\s*$/', $line, $match) === 1
        ) {
            // Setext
            $header = trim($lastLine);
            if (!$header) {
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
     * Reduce the toc to the given level
     *
     * @param array &$toc
     * @param int   $level
     */
    protected function reduceToc(array &$toc, $level) {
        while (end($toc)->level >= $level) {
            array_pop($toc);
        }
    }
}
