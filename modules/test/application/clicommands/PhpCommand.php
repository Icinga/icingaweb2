<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Test\Clicommands;

use Icinga\Cli\Command;

/**
 * PHP unit- & style-tests
 */
class PhpCommand extends Command
{
    /**
     * Default arguments and options for PHP_CodeSniffer
     *
     * @var array
     */
    protected $phpcsDefaultParams = array(
        '-p',
        '--standard=PSR2',
        '--extensions=php',
        '--encoding=utf-8'
    );

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

        chdir(realpath(__DIR__ . '/../..'));
        $command = $this->getEnvironmentVariables() . $phpUnit . ' ' . join(
            ' ',
            array_merge($options, $this->params->getAllStandalone())
        );
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
        if (empty($arguments)) {
            $arguments = array(
                realpath(__DIR__ . '/../../../../application'),
                realpath(__DIR__ . '/../../../../library/Icinga')
            );
        }

        chdir(realpath(__DIR__ . '/../..'));
        passthru(
            $phpcs . ' ' . join(
                ' ',
                array_merge(
                    $options,
                    $this->phpcsDefaultParams,
                    $arguments,
                    $this->params->getAllStandalone()
                )
            )
        );
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
