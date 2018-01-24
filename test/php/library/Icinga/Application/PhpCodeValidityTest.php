<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Application;

use Icinga\Test\BaseTestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class PhpCodeValidityTest extends BaseTestCase
{
    /**
     * Collect all classes, interfaces and traits and let PHP validate them as if included
     *
     * @runInSeparateProcess
     */
    public function testAllClassesInterfacesAndTraits()
    {
        $types = array(
            T_CLASS     => 'class',
            T_INTERFACE => 'interface',
            T_TRAIT     => 'trait'
        );

        $baseDir = getenv('ICINGAWEB_BASEDIR') ?: realpath(__DIR__ . '/../../../../..');
        $dirs = array($baseDir => '~\A(?:test|vendor|modules|library/(?:vendor|Icinga/Test))(?:/|\z)~');

        foreach (preg_split('/:/', getenv('ICINGAWEB_MODULE_DIRS'), -1, PREG_SPLIT_NO_EMPTY) as $modDir) {
            $dirs[$modDir] = '~\A(?:test|vendor|library/(?:vendor|[^/]+/ProvidedHook))(?:/|\z)~';
        }

        $files = array();

        foreach ($dirs as $dir => $excludes) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

            foreach ($iterator as $path => $info) {
                /** @var \SplFileInfo $info */
                if (preg_match($excludes, $iterator->getInnerIterator()->getSubPath()) || ! (
                        $info->isFile() && preg_match('/\.php\z/', $path)
                    )) {
                    continue;
                }

                $content = file_get_contents($path);
                $lines = explode("\n", $content);
                $tokens = token_get_all($content);
                $lastDocComment = '';

                foreach ($tokens as $token) {
                    if (! is_array($token)) {
                        continue;
                    }

                    list($tokenNr, $raw, $lineNr) = $token;

                    if ($tokenNr === T_DOC_COMMENT) {
                        $lastDocComment = $raw;
                        continue;
                    }

                    if (array_key_exists($tokenNr, $types)) {
                        $matches = array();
                        if (preg_match('/\A\s*(\w+)\s+\w+/', $lines[$lineNr - 1], $matches)) {
                            list($_, $type) = $matches;

                            if ($type === $types[$tokenNr]) {
                                // Valid definition header

                                if (! preg_match('/@deprecated\b/', $lastDocComment)) {
                                    $files[] = $path;
                                }
                            }
                        }

                        // Bad definition header
                        break;
                    }
                }

                // No definition header
            }
        }

        require_once 'HTMLPurifier/Bootstrap.php';
        require_once 'HTMLPurifier.php';

        error_reporting(error_reporting() & ~ E_DEPRECATED);

        require_once 'HTMLPurifier.autoload.php';

        error_reporting(E_ALL | E_STRICT);

        foreach ($files as $file) {
            require_once $file;
        }
    }
}
