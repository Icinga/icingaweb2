<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Test\Clicommands;

use Icinga\Cli\Command;
use Icinga\Util\Process;

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
     *   icingacli test php unit --include *SpecialTest
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
            $options[] = '--verbose';
        }
        if ($build) {
            $reportPath = $this->setupAndReturnReportDirectory();
            echo $reportPath;
            $options[] = '--log-junit';
            $options[] = $reportPath . '/phpunit_results.xml';
            $options[] = '--coverage-html';
            $options[] = $reportPath . '/php_html_coverage';
        }
        if ($include !== null) {
            $options[] = '--filter';
            $options[] = $include;
        }

        Process::start(
            $phpUnit . ' ' . join(' ', array_merge($options, $this->params->getAllStandalone())),
            realpath(__DIR__ . '/../..')
        )->wait();
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
     *   icingacli test php style --include path/to/your/file
     *   icingacli test php style --exclude *someFile* --exclude someOtherFile*
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
        $arguments = array_filter(array_map(function ($p) { return realpath($p); }, $include));
        if (empty($arguments)) {
            $arguments = array(
                realpath(__DIR__ . '/../../../../application'),
                realpath(__DIR__ . '/../../../../library/Icinga')
            );
        }

        Process::start(
            $phpcs . ' ' . join(
                ' ',
                array_merge(
                    $options,
                    $this->phpcsDefaultParams,
                    $arguments,
                    $this->params->getAllStandalone()
                )
            ),
            realpath(__DIR__ . '/../..')
        )->wait();
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
}
