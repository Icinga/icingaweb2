<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Translation\Util;

use Exception;
use Icinga\Application\ApplicationBootstrap;
use Icinga\Application\Config;
use Icinga\Application\Modules\Manager;
use Icinga\Exception\IcingaException;
use Icinga\Util\File;

/**
 * This class provides some useful utility functions to handle gettext translations
 */
class GettextTranslationHelper
{
    /**
     * All project files are supposed to have the same/this encoding
     */
    const FILE_ENCODING = 'UTF-8';

    /**
     * Config
     *
     * @var Config
     */
    protected $config;

    /**
     * The source files to parse
     *
     * @var array
     */
    private $sourceExtensions = array(
        'php',
        'phtml'
    );

    /**
     * The module manager of the application's bootstrap
     *
     * @var Manager
     */
    private $moduleMgr;

    /**
     * The current version of Icingaweb 2 or of the module the catalog is being created for
     *
     * @var string
     */
    private $version;

    /**
     * The name of the module if any
     *
     * @var string
     */
    private $moduleName;

    /**
     * The locale used by this helper
     *
     * @var string
     */
    private $locale;

    /**
     * The path to the Zend application root
     *
     * @var string
     */
    private $appDir;

    /**
     * The path to the module, if any
     *
     * @var string
     */
    private $moduleDir;

    /**
     * Path to the Icinga library
     *
     * @var string
     */
    protected $libDir;

    /**
     * The path to the file catalog
     *
     * @var string
     */
    private $catalogPath;

    /**
     * The path to the *.pot file
     *
     * @var string
     */
    private $templatePath;

    /**
     * The path to the *.po file
     *
     * @var string
     */
    private $tablePath;

    /**
     * Create a new TranslationHelper object
     *
     * @param   ApplicationBootstrap    $bootstrap  The application's bootstrap object
     * @param   string                  $locale     The locale to be used by this helper
     */
    public function __construct(ApplicationBootstrap $bootstrap, $locale)
    {
        $this->moduleMgr = $bootstrap->getModuleManager()->loadEnabledModules();
        $this->appDir = $bootstrap->getApplicationDir();
        $this->libDir = $bootstrap->getLibraryDir('Icinga');
        $this->locale = $locale;
    }

    /**
     * Cleanup temporary files
     */
    public function __destruct()
    {
        if ($this->catalogPath !== null && file_exists($this->catalogPath)) {
            unlink($this->catalogPath);
        }

        if ($this->templatePath !== null && file_exists($this->templatePath)) {
            unlink($this->templatePath);
        }
    }

    /**
     * Get the config
     *
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Set the config
     *
     * @param   Config $config
     *
     * @return  $this
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * Update the translation table for the main application
     */
    public function updateIcingaTranslations()
    {
        $this->catalogPath = tempnam(sys_get_temp_dir(), 'IcingaTranslation_');
        $this->templatePath = tempnam(sys_get_temp_dir(), 'IcingaPot_');
        $this->version = 'None'; // TODO: Access icinga version from a file or property

        $this->moduleDir = null;
        $this->tablePath = implode(
            DIRECTORY_SEPARATOR,
            array(
                $this->appDir,
                'locale',
                $this->locale,
                'LC_MESSAGES',
                'icinga.po'
            )
        );

        $this->createFileCatalog();
        $this->createTemplateFile();
        $this->updateTranslationTable();
    }

    /**
     * Update the translation table for a particular module
     *
     * @param   string      $module     The name of the module for which to update the translation table
     */
    public function updateModuleTranslations($module)
    {
        $this->catalogPath = tempnam(sys_get_temp_dir(), 'IcingaTranslation_');
        $this->templatePath = tempnam(sys_get_temp_dir(), 'IcingaPot_');
        $this->version = $this->moduleMgr->getModule($module)->getVersion();
        $this->moduleName = $this->moduleMgr->getModule($module)->getName();

        $this->moduleDir = $this->moduleMgr->getModuleDir($module);
        $this->tablePath = implode(
            DIRECTORY_SEPARATOR,
            array(
                $this->moduleDir,
                'application',
                'locale',
                $this->locale,
                'LC_MESSAGES',
                $module . '.po'
            )
        );

        $this->createFileCatalog();
        $this->createTemplateFile();
        $this->updateTranslationTable();
    }

    /**
     * Compile the translation table for the main application
     */
    public function compileIcingaTranslation()
    {
        $this->tablePath = implode(
            DIRECTORY_SEPARATOR,
            array(
                $this->appDir,
                'locale',
                $this->locale,
                'LC_MESSAGES',
                'icinga.po'
            )
        );

        $this->compileTranslationTable();
    }

    /**
     * Compile the translation table for a particular module
     *
     * @param   string      $module     The name of the module for which to compile the translation table
     */
    public function compileModuleTranslation($module)
    {
        $this->moduleDir = $this->moduleMgr->getModuleDir($module);
        $this->tablePath = implode(
            DIRECTORY_SEPARATOR,
            array(
                $this->moduleDir,
                'application',
                'locale',
                $this->locale,
                'LC_MESSAGES',
                $module . '.po'
            )
        );

        $this->compileTranslationTable();
    }

    /**
     * Update any existing or create a new translation table using the gettext tools
     *
     * @throws  Exception   In case the translation table does not yet exist and cannot be created
     */
    private function updateTranslationTable()
    {
        if (is_file($this->tablePath)) {
            shell_exec(sprintf(
                '%s --update --backup=none %s %s 2>&1',
                $this->getConfig()->get('translation', 'msgmerge', '/usr/bin/env msgmerge'),
                $this->tablePath,
                $this->templatePath
            ));
        } else {
            if ((!is_dir(dirname($this->tablePath)) && !@mkdir(dirname($this->tablePath), 0755, true)) ||
                !rename($this->templatePath, $this->tablePath)) {
                throw new IcingaException(
                    'Unable to create %s',
                    $this->tablePath
                );
            }
        }
        $this->updateHeader($this->tablePath);
        $this->fixSourceLocations($this->tablePath);
    }

    /**
     * Create the template file using the gettext tools
     */
    private function createTemplateFile()
    {
        shell_exec(
            implode(
                ' ',
                array(
                    $this->getConfig()->get('translation', 'xgettext', '/usr/bin/env xgettext'),
                    '--language=PHP',
                    '--keyword=translate',
                    '--keyword=translate:1,2c',
                    '--keyword=translatePlural:1,2',
                    '--keyword=translatePlural:1,2,4c',
                    '--keyword=mt:2',
                    '--keyword=mt:2,3c',
                    '--keyword=mtp:2,3',
                    '--keyword=mtp:2,3,5c',
                    '--keyword=t',
                    '--keyword=t:1,2c',
                    '--keyword=tp:1,2',
                    '--keyword=tp:1,2,4c',
                    '--keyword=N_',
                    '--sort-output',
                    '--force-po',
                    '--omit-header',
                    '--from-code=' . self::FILE_ENCODING,
                    '--files-from="' . $this->catalogPath . '"',
                    '--output="' . $this->templatePath . '"'
                )
            )
        );
    }

    /**
     * Create or update a gettext conformant header in the given file
     *
     * @param   string  $path   The path to the file
     */
    private function updateHeader($path)
    {
        $headerInfo = array(
            'title' => 'Icinga Web 2 - Head for multiple monitoring backends',
            'copyright_holder' => 'Icinga Development Team',
            'copyright_year' => date('Y'),
            'author_name' => 'FIRST AUTHOR',
            'author_mail' => 'EMAIL@ADDRESS',
            'author_year' => 'YEAR',
            'project_name' => $this->moduleName ? ucfirst($this->moduleName) . ' Module' : 'Icinga Web 2',
            'project_version' => $this->version,
            'project_bug_mail' => 'dev@icinga.com',
            'pot_creation_date' => date('Y-m-d H:iO'),
            'po_revision_date' => 'YEAR-MO-DA HO:MI+ZONE',
            'translator_name' => 'FULL NAME',
            'translator_mail' => 'EMAIL@ADDRESS',
            'language'  => $this->locale,
            'language_team_name' => 'LANGUAGE',
            'language_team_url' => 'LL@li.org',
            'charset' => self::FILE_ENCODING
        );

        $content = file_get_contents($path);
        if (strpos($content, '# ') === 0) {
            $authorInfo = array();
            if (preg_match('@# (.+) <(.+)>, (\d+|YEAR)\.@', $content, $authorInfo)) {
                $headerInfo['author_name'] = $authorInfo[1];
                $headerInfo['author_mail'] = $authorInfo[2];
                $headerInfo['author_year'] = $authorInfo[3];
            }
            $revisionInfo = array();
            if (preg_match('@Revision-Date: (\d{4}-\d{2}-\d{2} \d{2}:\d{2}\+\d{4})@', $content, $revisionInfo)) {
                $headerInfo['po_revision_date'] = $revisionInfo[1];
            }
            $translatorInfo = array();
            if (preg_match('@Last-Translator: (.+) <(.+)>@', $content, $translatorInfo)) {
                $headerInfo['translator_name'] = $translatorInfo[1];
                $headerInfo['translator_mail'] = $translatorInfo[2];
            }
            $languageTeamInfo = array();
            if (preg_match('@Language-Team: (.+) <(.+)>@', $content, $languageTeamInfo)) {
                $headerInfo['language_team_name'] = $languageTeamInfo[1];
                $headerInfo['language_team_url'] = $languageTeamInfo[2];
            }
            $languageInfo = array();
            if (preg_match('@Language: ([a-z]{2}_[A-Z]{2})@', $content, $languageInfo)) {
                $headerInfo['language'] = $languageInfo[1];
            }
        }

        file_put_contents(
            $path,
            implode(
                PHP_EOL,
                array(
                    '# ' . $headerInfo['title'] . '.',
                    '# Copyright (C) ' . $headerInfo['copyright_year'] . ' ' . $headerInfo['copyright_holder'],
                    '# This file is distributed under the same license as ' . $headerInfo['project_name'] . '.',
                    '# ' . $headerInfo['author_name'] . ' <' . $headerInfo['author_mail']
                    . '>, ' . $headerInfo['author_year'] . '.',
                    '# ',
                    '#, fuzzy',
                    'msgid ""',
                    'msgstr ""',
                    '"Project-Id-Version: ' . $headerInfo['project_name'] . ' ('
                    . $headerInfo['project_version'] . ')\n"',
                    '"Report-Msgid-Bugs-To: ' . $headerInfo['project_bug_mail'] . '\n"',
                    '"POT-Creation-Date: ' . $headerInfo['pot_creation_date'] . '\n"',
                    '"PO-Revision-Date: ' . $headerInfo['po_revision_date'] . '\n"',
                    '"Last-Translator: ' . $headerInfo['translator_name'] . ' <'
                    . $headerInfo['translator_mail'] . '>\n"',
                    '"Language: ' . $headerInfo['language'] . '\n"',
                    '"Language-Team: ' . $headerInfo['language_team_name'] . ' <'
                    . $headerInfo['language_team_url'] . '>\n"',
                    '"MIME-Version: 1.0\n"',
                    '"Content-Type: text/plain; charset=' . $headerInfo['charset'] . '\n"',
                    '"Content-Transfer-Encoding: 8bit\n"',
                    '"Plural-Forms: nplurals=2; plural=(n != 1);\n"',
                    '"X-Poedit-Basepath: .\n"',
                    '"X-Poedit-SearchPath-0: .\n"',
                    ''
                )
            ) . PHP_EOL . substr($content, strpos($content, '#: '))
        );
    }

    /**
     * Adjust all absolute source file paths so that they're all relative to the catalog's location
     *
     * @param   string  $path
     */
    protected function fixSourceLocations($path)
    {
        shell_exec(sprintf(
            "sed -i 's;%s;../../../..;g' %s",
            dirname($this->appDir),
            $path
        ));
    }

    /**
     * Create the file catalog
     *
     * @throws  Exception   In case the catalog-file cannot be created
     */
    private function createFileCatalog()
    {
        $catalog = new File($this->catalogPath, 'w');

        try {
            if ($this->moduleDir) {
                $this->getSourceFileNames($this->moduleDir, $catalog);
            } else {
                $this->getSourceFileNames($this->appDir, $catalog);
                $this->getSourceFileNames($this->libDir, $catalog);
            }
        } catch (Exception $error) {
            throw $error;
        }

        $catalog->fflush();
    }

    /**
     * Recursively scan the given directory for translatable source files
     *
     * @param   string      $directory      The directory where to search for sources
     * @param   File        $file           The file where to write the results
     * @param   array       $blacklist      A list of directories to omit
     *
     * @throws  Exception                   In case the given directory is not readable
     */
    private function getSourceFileNames($directory, File $file)
    {
        $directoryHandle = opendir($directory);
        if (!$directoryHandle) {
            throw new IcingaException(
                'Unable to read files from %s',
                $directory
            );
        }

        $subdirs = array();
        while (($filename = readdir($directoryHandle)) !== false) {
            $filepath = $directory . DIRECTORY_SEPARATOR . $filename;
            if (preg_match('@^[^\.].+\.(' . implode('|', $this->sourceExtensions) . ')$@', $filename)) {
                $file->fwrite($filepath . PHP_EOL);
            } elseif (is_dir($filepath) && !preg_match('@^(\.|\.\.)$@', $filename)) {
                $subdirs[] = $filepath;
            }
        }
        closedir($directoryHandle);

        foreach ($subdirs as $subdir) {
            $this->getSourceFileNames($subdir, $file);
        }
    }

    /**
     * Compile the translation table
     */
    private function compileTranslationTable()
    {
        $targetPath = substr($this->tablePath, 0, strrpos($this->tablePath, '.')) . '.mo';
        shell_exec(
            implode(
                ' ',
                array(
                    $this->getConfig()->get('translation', 'msgfmt', '/usr/bin/env msgfmt'),
                    '-o ' . $targetPath,
                    $this->tablePath
                )
            )
        );
    }
}
