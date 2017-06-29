<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\File\Ini;

use Icinga\File\Ini\Dom\Section;
use Icinga\File\Ini\Dom\Comment;
use Icinga\File\Ini\Dom\Document;
use Icinga\File\Ini\Dom\Directive;
use Icinga\Application\Logger;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\NotReadableError;
use Icinga\Application\Config;

class IniParser
{
    const LINE_START = 0;
    const SECTION = 1;
    const ESCAPE = 2;
    const DIRECTIVE_KEY = 4;
    const DIRECTIVE_VALUE_START = 5;
    const DIRECTIVE_VALUE = 6;
    const DIRECTIVE_VALUE_QUOTED = 7;
    const COMMENT = 8;
    const COMMENT_END = 9;
    const LINE_END = 10;

    /**
     * Cancel the parsing with an error
     *
     * @param $message  The error description
     * @param $line     The line in which the error occured
     *
     * @throws ConfigurationError
     */
    private static function throwParseError($message, $line)
    {
        throw new ConfigurationError(sprintf('Ini parser error: %s. (l. %d)', $message, $line));
    }

    /**
     * Read the ini file contained in a string and return a mutable DOM that can be used
     * to change the content of an INI file.
     *
     * @param $str                  A string containing the whole ini file
     *
     * @return Document             The mutable DOM object.
     * @throws ConfigurationError   In case the file is not parseable
     */
    public static function parseIni($str)
    {
        $doc = new Document();
        $sec = null;
        $dir = null;
        $coms = array();
        $state = self::LINE_START;
        $escaping = null;
        $token = '';
        $line = 0;

        for ($i = 0; $i < strlen($str); $i++) {
            $s = $str[$i];
            switch ($state) {
                case self::LINE_START:
                    if (ctype_space($s)) {
                        continue;
                    }
                    switch ($s) {
                        case '[':
                            $state = self::SECTION;
                            break;
                        case ';':
                            $state = self::COMMENT;
                            break;
                        default:
                            $state = self::DIRECTIVE_KEY;
                            $token = $s;
                            break;
                    }
                    break;

                case self::ESCAPE:
                    $token .= $s;
                    $state = $escaping;
                    $escaping = null;
                    break;

                case self::SECTION:
                    if ($s === "\n") {
                        self::throwParseError('Unterminated SECTION', $line);
                    } elseif ($s === '\\') {
                        $state = self::ESCAPE;
                        $escaping = self::SECTION;
                    } elseif ($s !== ']') {
                        $token .= $s;
                    } else {
                        $sec = new Section($token);
                        $sec->setCommentsPre($coms);
                        $doc->addSection($sec);
                        $dir = null;
                        $coms = array();

                        $state = self::LINE_END;
                        $token = '';
                    }
                    break;

                case self::DIRECTIVE_KEY:
                    if ($s !== '=') {
                        $token .= $s;
                    } else {
                        $dir = new Directive($token);
                        $dir->setCommentsPre($coms);
                        if (isset($sec)) {
                            $sec->addDirective($dir);
                        } else {
                            Logger::warning(sprintf(
                                'Ini parser warning: section-less directive "%s" ignored. (l. %d)',
                                $token,
                                $line
                            ));
                        }

                        $coms = array();
                        $state = self::DIRECTIVE_VALUE_START;
                        $token = '';
                    }
                    break;

                case self::DIRECTIVE_VALUE_START:
                    if (ctype_space($s)) {
                        continue;
                    } elseif ($s === '"') {
                        $state = self::DIRECTIVE_VALUE_QUOTED;
                    } else {
                        $state = self::DIRECTIVE_VALUE;
                        $token = $s;
                    }
                    break;

                case self::DIRECTIVE_VALUE:
                    /*
                        Escaping non-quoted values is not supported by php_parse_ini, it might
                        be reasonable to include in case we are switching completely our own
                        parser implementation
                    */
                    if ($s === "\n" || $s === ";") {
                        $dir->setValue($token);
                        $token = '';

                        if ($s === "\n") {
                            $state = self::LINE_START;
                            $line ++;
                        } elseif ($s === ';') {
                            $state = self::COMMENT;
                        }
                    } else {
                        $token .= $s;
                    }
                    break;

                case self::DIRECTIVE_VALUE_QUOTED:
                    if ($s === '\\') {
                        $state = self::ESCAPE;
                        $escaping = self::DIRECTIVE_VALUE_QUOTED;
                    } elseif ($s !== '"') {
                        $token .= $s;
                    } else {
                        $dir->setValue($token);
                        $token = '';
                        $state = self::LINE_END;
                    }
                    break;

                case self::COMMENT:
                case self::COMMENT_END:
                    if ($s !== "\n") {
                        $token .= $s;
                    } else {
                        $com = new Comment();
                        $com->setContent($token);
                        $token = '';

                        // Comments at the line end belong to the current line's directive or section. Comments
                        // on empty lines belong to the next directive that shows up.
                        if ($state === self::COMMENT_END) {
                            if (isset($dir)) {
                                $dir->setCommentPost($com);
                            } else {
                                $sec->setCommentPost($com);
                            }
                        } else {
                            $coms[] = $com;
                        }
                        $state = self::LINE_START;
                        $line ++;
                    }
                    break;

                case self::LINE_END:
                    if ($s === "\n") {
                        $state = self::LINE_START;
                        $line ++;
                    } elseif ($s === ';') {
                        $state = self::COMMENT_END;
                    }
                    break;
            }
        }

        // process the last token
        switch ($state) {
            case self::COMMENT:
            case self::COMMENT_END:
                $com = new Comment();
                $com->setContent($token);
                if ($state === self::COMMENT_END) {
                    if (isset($dir)) {
                        $dir->setCommentPost($com);
                    } else {
                        $sec->setCommentPost($com);
                    }
                } else {
                    $coms[] = $com;
                }
                break;

            case self::DIRECTIVE_VALUE:
                $dir->setValue($token);
                $sec->addDirective($dir);
                break;

            case self::ESCAPE:
            case self::DIRECTIVE_VALUE_QUOTED:
            case self::DIRECTIVE_KEY:
            case self::SECTION:
                self::throwParseError('File ended in unterminated state ' . $state, $line);
        }
        if (! empty($coms)) {
            $doc->setCommentsDangling($coms);
        }
        return $doc;
    }

    /**
     * Read the ini file and parse it with ::parseIni()
     *
     * @param   string  $file       The ini file to read
     *
     * @return  Config
     * @throws  NotReadableError    When the file cannot be read
     */
    public static function parseIniFile($file)
    {
        if (($path = realpath($file)) === false) {
            throw new NotReadableError('Couldn\'t compute the absolute path of `%s\'', $file);
        }

        if (($content = file_get_contents($path)) === false) {
            throw new NotReadableError('Couldn\'t read the file `%s\'', $path);
        }

        return Config::fromArray(parse_ini_string($content, true))->setConfigFile($file);
    }
}
