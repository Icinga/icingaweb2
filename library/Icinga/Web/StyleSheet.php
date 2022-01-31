<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

use Exception;
use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Authentication\Auth;
use Icinga\Exception\IcingaException;

/**
 * Send CSS for Web 2 and all loaded modules to the client
 */
class StyleSheet
{
    /**
     * The name of the default theme
     *
     * @var string
     */
    const DEFAULT_THEME = 'Icinga';

    /**
     * The name of the default theme mode
     *
     * @var string
     */
    const DEFAULT_MODE = 'dark';

    /**
     * The themes that are compatible with the default theme
     *
     * @var array
     */
    const THEME_WHITELIST = [
        'colorblind',
        'high-contrast',
        'solarized-dark',
        'Winter'
    ];

    /**
     * RegEx pattern for matching full css @media query of theme mode
     *
     * @var string
     */
    const REGEX_ALL_MODE_QUERY = '/@media\s*\(\s*min-height\s*:\s*@prefer-light-color-scheme\s*\)\s*,' .
    '\s*\(\s*prefers-color-scheme\s*:\s*light\s*\)\s*and\s*\(\s*min-height\s*:\s*@enable-color-preference\s*\)/';

    /**
     * RegEx pattern for matching css @media query of theme mode
     *
     * @var string
     */
    const REGEX_AUTO_MODE_QUERY = '/@media.*prefers-color-scheme/';

    /**
     * Array of core LESS files Web 2 sends to the client
     *
     * @var string[]
     */
    protected static $lessFiles = [
        '../application/fonts/fontello-ifont/css/ifont-embedded.css',
        'css/vendor/normalize.css',
        'css/icinga/base.less',
        'css/icinga/badges.less',
        'css/icinga/mixins.less',
        'css/icinga/grid.less',
        'css/icinga/nav.less',
        'css/icinga/main.less',
        'css/icinga/animation.less',
        'css/icinga/layout.less',
        'css/icinga/layout-structure.less',
        'css/icinga/menu.less',
        'css/icinga/tabs.less',
        'css/icinga/forms.less',
        'css/icinga/setup.less',
        'css/icinga/widgets.less',
        'css/icinga/login.less',
        'css/icinga/about.less',
        'css/icinga/controls.less',
        'css/icinga/dev.less',
        'css/icinga/spinner.less',
        'css/icinga/compat.less',
        'css/icinga/print.less',
        'css/icinga/responsive.less',
        'css/icinga/modal.less',
        'css/icinga/audit.less',
        'css/icinga/health.less'
    ];

    /**
     * Application instance
     *
     * @var \Icinga\Application\EmbeddedWeb
     */
    protected $app;

    /**
     * Less compiler
     *
     * @var LessCompiler
     */
    protected $lessCompiler;

    /**
     * Path to the public directory
     *
     * @var string
     */
    protected $pubPath;

    /**
     * Create the StyleSheet
     */
    public function __construct()
    {
        $app = Icinga::app();
        $this->app = $app;
        $this->lessCompiler = new LessCompiler();
        $this->pubPath = $app->getBaseDir('public');
        $this->collect();
    }

    /**
     * Collect Web 2 and module LESS files and add them to the LESS compiler
     */
    protected function collect()
    {
        foreach ($this->app->getLibraries() as $library) {
            foreach ($library->getCssAssets() as $lessFile) {
                $this->lessCompiler->addLessFile($lessFile);
            }
        }

        foreach (self::$lessFiles as $lessFile) {
            $this->lessCompiler->addLessFile($this->pubPath . '/' . $lessFile);
        }

        $mm = $this->app->getModuleManager();

        foreach ($mm->getLoadedModules() as $moduleName => $module) {
            if ($module->hasCss()) {
                foreach ($module->getCssFiles() as $lessFilePath) {
                    $this->lessCompiler->addModuleLessFile($moduleName, $lessFilePath);
                }
            }
        }

        $themingConfig = $this->app->getConfig()->getSection('themes');
        $defaultTheme = $themingConfig->get('default');
        $theme = null;
        if ($defaultTheme !== null && $defaultTheme !== self::DEFAULT_THEME) {
            $theme = $defaultTheme;
        }

        if (! (bool) $themingConfig->get('disabled', false)) {
            $auth = Auth::getInstance();
            if ($auth->isAuthenticated()) {
                $userTheme = $auth->getUser()->getPreferences()->getValue('icingaweb', 'theme');
                if ($userTheme !== null) {
                    $theme = $userTheme;
                }
            }
        }

        if ($themePath = self::getThemeFile($theme)) {
            $this->lessCompiler->setTheme($themePath);
        }

        if (! $themePath || in_array($theme, self::THEME_WHITELIST, true)) {
            $this->lessCompiler->addLessFile($this->pubPath . '/css/icinga/login-orbs.less');
        }

        $mode = 'none';
        if ($user = Auth::getInstance()->getUser()) {
            $file = $themePath !== null ? @file_get_contents($themePath) : '';
            if ($file && $file !== '' && ! preg_match(self::REGEX_ALL_MODE_QUERY, $file)) {
                if (preg_match(self::REGEX_AUTO_MODE_QUERY, $file)) {
                    $mode = 'system';
                }
            } else {
                $mode = $user->getPreferences()->getValue('icingaweb', 'theme_mode', self::DEFAULT_MODE);
            }
        }

        $this->lessCompiler->setThemeMode($this->pubPath . '/css/modes/'. $mode . '.less');
    }

    /**
     * Get the stylesheet for PDF export
     *
     * @return  $this
     */
    public static function forPdf()
    {
        $styleSheet = new self();
        $styleSheet->lessCompiler->addLessFile($styleSheet->pubPath . '/css/pdf/pdfprint.less');
        // TODO(el): Caching
        return $styleSheet;
    }

    /**
     * Render the stylesheet
     *
     * @param   bool    $minified   Whether to compress the stylesheet
     *
     * @return  string              CSS
     */
    public function render($minified = false)
    {
        if ($minified) {
            $this->lessCompiler->compress();
        }
        return $this->lessCompiler->render();
    }

    /**
     * Send the stylesheet to the client
     *
     * Does not cache the stylesheet if the HTTP header Cache-Control or Pragma is set to no-cache.
     *
     * @param   bool    $minified   Whether to compress the stylesheet
     */
    public static function send($minified = false)
    {
        $styleSheet = new self();

        $request = $styleSheet->app->getRequest();
        $response = $styleSheet->app->getResponse();
        $response->setHeader('Cache-Control', 'private,no-cache,must-revalidate', true);

        $noCache = $request->getHeader('Cache-Control') === 'no-cache' || $request->getHeader('Pragma') === 'no-cache';

        if (! $noCache && FileCache::etagMatchesFiles($styleSheet->lessCompiler->getLessFiles())) {
            $response
                ->setHttpResponseCode(304)
                ->sendHeaders();
            return;
        }

        $etag = FileCache::etagForFiles($styleSheet->lessCompiler->getLessFiles());

        $response->setHeader('ETag', $etag, true)
            ->setHeader('Content-Type', 'text/css', true);

        $cacheFile = 'icinga-' . $etag . ($minified ? '.min' : '') . '.css';
        $cache = FileCache::instance();

        if (! $noCache && $cache->has($cacheFile)) {
            $response->setBody($cache->get($cacheFile));
        } else {
            $css = $styleSheet->render($minified);
            $response->setBody($css);
            $cache->store($cacheFile, $css);
        }

        $response->sendResponse();
    }

    /**
     * Render the stylesheet
     *
     * @return  string
     */
    public function __toString()
    {
        try {
            return $this->render();
        } catch (Exception $e) {
            Logger::error($e);
            return IcingaException::describe($e);
        }
    }

    /**
     * Get the path to the current LESS theme file
     *
     * @param $theme
     *
     * @return  string|null Return null if self::DEFAULT_THEME is set as theme, path otherwise
     */
    public static function getThemeFile($theme)
    {
        $app = Icinga::app();

        if ($theme && $theme !== self::DEFAULT_THEME) {
            if (($pos = strpos($theme, '/')) !== false) {
                $moduleName = substr($theme, 0, $pos);
                $theme = substr($theme, $pos + 1);
                if ($app->getModuleManager()->hasLoaded($moduleName)) {
                    $module = $app->getModuleManager()->getModule($moduleName);

                    return $module->getCssDir() . '/themes/' . $theme . '.less';
                }
            } else {
                return $app->getBaseDir('public') . '/css/themes/' . $theme . '.less';
            }
        }

        return null;
    }
}
