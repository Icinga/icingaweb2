<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Cli\Documentation;

use Icinga\Cli\Screen;

class CommentParser
{
    protected $raw;
    protected $plain;
    protected $title;
    /** @var array $paragraphs */
    protected $paragraphs = array();

    public function __construct($raw)
    {
        $this->raw = $raw;
        if ($raw) {
            $this->parse();
        }
    }

    public function getTitle()
    {
        return $this->title;
    }

    protected function parse()
    {
        $plain = $this->raw;

        // Strip comment start /**
        $plain = preg_replace('~^/\s*\*\*\n~s', '', $plain);

        // Strip comment end */
        $plain = preg_replace('~\n\s*\*/\s*~s', "\n", $plain);
        $p = null;
        foreach (preg_split('~\n~', $plain) as $line) {
            // Strip * at line start
            /** @var string $line */
            $line = preg_replace('~^\s*\*\s?~', '', $line);
            $line = rtrim($line);
            if ($this->title === null) {
                $this->title = $line;
                continue;
            }
            if ($p === null && empty($this->paragraphs)) {
                $p = & $this->paragraphs[];
            }

            if ($line === '') {
                if ($p !== null) {
                    $p = & $this->paragraphs[];
                }
                continue;
            }
            if ($p === null) {
                $p = $line;
            } else {
                if (substr($line, 0, 2) === '  ') {
                    $p .= "\n" . $line;
                } else {
                    $p .= ' ' . $line;
                }
            }
        }
        if ($p === null) {
            array_pop($this->paragraphs);
        }
    }

    public function dump()
    {
        if ($this->title) {
            $res = $this->title . "\n" . str_repeat('=', strlen($this->title)) . "\n\n";
        } else {
            $res = '';
        }

        foreach ($this->paragraphs as $p) {
            $res .= wordwrap($p, Screen::instance()->getColumns()) . "\n\n";
        }

        return $res;
    }
}
