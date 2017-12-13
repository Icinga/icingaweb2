<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Test\Clicommands;

use ErrorException;
use Icinga\Application\Icinga;
use Icinga\Cli\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * PHP unit- & style-tests
 */
class PhpCommand extends Command
{
    /**
     * Run all unit-test suites
     *
     * This command runs the unit- and regression-tests of icingaweb and installed modules.
     *
     * USAGE
     *
     * icingacli test php unit [options]
     *
     * OPTIONS
     *
     *   --verbose  Be more verbose.
     *   --build    Enable reporting.
     *   --include  Pattern to use for including files/test cases.
     *
     * EXAMPLES
     *
     *   icingacli test php unit --verbose
     *   icingacli test php unit --build
     *   icingacli test php unit --include=*SpecialTest
     *
     * NOTES
     *
     *   Default settings are defined via `modules/test/phpunit.xml` under icingaweb'
     *   installation directory.
     */
    public function unitAction()
    {
        $build = $this->params->shift('build');
        $include = $this->params->shift('include');

        $phpUnit = exec('which phpunit');
        if (!file_exists($phpUnit)) {
            $this->fail('PHPUnit not found. Please install PHPUnit to be able to run the unit-test suites.');
        }

        $options = array();
        if ($this->isVerbose) {
            $options[] = '--verbose --testdox';
        }
        if ($build) {
            $reportPath = $this->setupAndReturnReportDirectory();
            $options[] = '--log-junit';
            $options[] = $reportPath . '/phpunit_results.xml';
            $options[] = '--coverage-html';
            $options[] = $reportPath . '/php_html_coverage';
        }
        if ($include !== null) {
            $options[] = '--filter';
            $options[] = $include;
        }

        chdir(Icinga::app()->getBaseDir());
        $command = $this->getEnvironmentVariables()
            . $phpUnit
            . ' -c modules/test/phpunit.xml'
            . ' ' . join(' ', array_merge($options, $this->params->getAllStandalone()));

        if ($this->isVerbose) {
            $res = `$command`;
            foreach (preg_split('/\n/', $res) as $line) {
                if (preg_match('~\s+\[([x\s])\]\s~', $line, $m)) {
                    if ($m[1] === 'x') {
                        echo $this->screen->colorize($line, 'green') . "\n";
                    } else {
                        echo $this->screen->colorize($line, 'red') . "\n";
                    }
                } else {
                    echo $line . "\n";
                }
            }
        } else {
            passthru($command);
        }
    }

    /**
     * Run code-style checks
     *
     * This command checks whether icingaweb and installed modules match the PSR-2 coding standard.
     *
     * USAGE
     *
     * icingacli test php style [options]
     *
     * OPTIONS
     *
     *   --verbose  Be more verbose.
     *   --build    Enable reporting.
     *   --include  Include only specific files. (Can be supplied multiple times.)
     *   --exclude  Pattern to use for excluding files. (Can be supplied multiple times.)
     *
     * EXAMPLES
     *
     *   icingacli test php style --verbose
     *   icingacli test php style --build
     *   icingacli test php style --include=path/to/your/file
     *   icingacli test php style --exclude=*someFile* --exclude=someOtherFile*
     *
     * NOTES
     *
     *   Default rules are defined via `phpcs.xml` and `icingaweb2.ruleset.xml` in icingaweb'
     *   installation directory.
     */
    public function styleAction()
    {
        $build = $this->params->shift('build');
        $include = (array) $this->params->shift('include', array());
        $exclude = (array) $this->params->shift('exclude', array());

        $phpcs = exec('which phpcs');
        if (!file_exists($phpcs)) {
            $this->fail(
                'PHP_CodeSniffer not found. Please install PHP_CodeSniffer to be able to run code style tests.'
            );
        }

        $options = array();
        if ($this->isVerbose) {
            $options[] = '-v';
        }
        if ($build) {
            $options[] = '--report-checkstyle=' . $this->setupAndReturnReportDirectory();
        }
        if (!empty($exclude)) {
            $options[] = '--ignore=' . join(',', $exclude);
        }
        $arguments = array_filter(array_map(function ($p) {
            return realpath($p);
        }, $include));

        chdir(Icinga::app()->getBaseDir());
        passthru(
            $phpcs . ' ' . join(
                ' ',
                array_merge(
                    $options,
                    $arguments,
                    $this->params->getAllStandalone()
                )
            )
        );
    }

    /**
     * Run code-validity checks
     *
     * This command checks whether icingaweb and installed modules match PHP syntax.
     *
     * USAGE
     *
     * icingacli test php validity
     */
    public function validityAction()
    {
        $types = array(
            T_CLASS     => 'class',
            T_INTERFACE => 'interface'
        );

        if (version_compare(PHP_VERSION, '5.4.0') > -1) {
            $types[T_TRAIT] = 'trait';
        }

        $files = array();
        $baseDir = realpath(__DIR__ . '/../../../..');
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir));

        foreach ($iterator as $path => $info) {
            /** @var \SplFileInfo $info */
            if (preg_match(
                '~\A(?:test|vendor|modules/[^/]+/test|library/Icinga/Test)(?:/|\z)~',
                $iterator->getInnerIterator()->getSubPath()
            ) || ! ($info->isFile() && preg_match('/\.php\z/', $path))) {
                continue;
            }

            $content = file_get_contents("file://$path");
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

        define('ICINGA_LIBDIR', "$baseDir/library");

        require_once 'HTMLPurifier/Bootstrap.php';
        require_once 'HTMLPurifier.php';

        $oldErrorReportingLevel = error_reporting();
        error_reporting($oldErrorReportingLevel & ~ E_DEPRECATED);

        require_once 'HTMLPurifier.autoload.php';

        error_reporting($oldErrorReportingLevel);

        foreach ($files as $file) {
            printf('+ require_once %s;%s', var_export($file, true), PHP_EOL);
            require_once $file;
        }
    }

    /**
     * Setup the directory where to put report files and return its path
     *
     * @return  string
     */
    protected function setupAndReturnReportDirectory()
    {
        $path = realpath(__DIR__ . '/../../../..') . '/build/log';
        if (!file_exists($path) && !@mkdir($path, 0755, true)) {
            $this->fail("Could not create directory: $path");
        }

        return $path;
    }

    /**
     * Setup some required environment variables
     */
    protected function getEnvironmentVariables()
    {
        $vars = array();
        $vars[] = sprintf('ICINGAWEB_BASEDIR=%s', $this->app->getBaseDir());
        $vars[] = sprintf('ICINGAWEB_ICINGA_LIB=%s', $this->app->getLibraryDir('Icinga'));

        // Disabled as the bootstrap.php for PHPUnit and class BaseTestCase can't handle multiple paths yet
        /*$vars[] = sprintf(
            'ICINGAWEB_MODULES_DIR=%s',
            implode(PATH_SEPARATOR, $this->app->getModuleManager()->getModuleDirs())
        );*/

        return join(' ', $vars) . ' ';
    }
}
