<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Exception\Json\JsonDecodeException;
use Icinga\Util\Json;
use JShrink\Minifier;

class JavaScript
{
    /** @var string */
    const DEFINE_RE =
        '/(?<!\.)define\(\s*([\'"][^\'"]*[\'"])?[,\s]*(\[[^]]*\])?[,\s]*((?>function\s*\([^)]*\)|[^=]*=>|\w+).*)/';

    protected static $jsFiles = [
        'js/helpers.js',
        'js/icinga.js',
        'js/icinga/logger.js',
        'js/icinga/storage.js',
        'js/icinga/utils.js',
        'js/icinga/ui.js',
        'js/icinga/timer.js',
        'js/icinga/loader.js',
        'js/icinga/eventlistener.js',
        'js/icinga/events.js',
        'js/icinga/history.js',
        'js/icinga/module.js',
        'js/icinga/timezone.js',
        'js/icinga/behavior/application-state.js',
        'js/icinga/behavior/autofocus.js',
        'js/icinga/behavior/collapsible.js',
        'js/icinga/behavior/detach.js',
        'js/icinga/behavior/dropdown.js',
        'js/icinga/behavior/navigation.js',
        'js/icinga/behavior/form.js',
        'js/icinga/behavior/actiontable.js',
        'js/icinga/behavior/flyover.js',
        'js/icinga/behavior/filtereditor.js',
        'js/icinga/behavior/selectable.js',
        'js/icinga/behavior/modal.js',
        'js/icinga/behavior/input-enrichment.js',
        'js/icinga/behavior/datetime-picker.js',
        'js/icinga/behavior/copy-to-clipboard.js',
        'js/icinga/behavior/relative-time.js'
    ];

    protected static $vendorFiles = [];

    protected static $baseFiles = [
        'js/define.js'
    ];

    public static function sendMinified()
    {
        self::send(true);
    }

    /**
     * Send the client side script code to the client
     *
     * Does not cache the client side script code if the HTTP header Cache-Control or Pragma is set to no-cache.
     *
     * @param   bool    $minified   Whether to compress the client side script code
     */
    public static function send($minified = false)
    {
        header('Content-Type: application/javascript');
        $basedir = Icinga::app()->getBootstrapDirectory();
        $moduleManager = Icinga::app()->getModuleManager();

        $files = [];
        $js = $out = '';
        $min = $minified ? '.min' : '';

        // Prepare vendor file list
        $vendorFiles = [];
        foreach (self::$vendorFiles as $file) {
            $filePath = $basedir . '/' . $file . $min . '.js';
            $vendorFiles[] = $filePath;
            $files[] = $filePath;
        }

        // Prepare base file list
        $baseFiles = [];
        foreach (self::$baseFiles as $file) {
            $filePath = $basedir . '/' . $file;
            $baseFiles[] = $filePath;
            $files[] = $filePath;
        }

        // Prepare library file list
        foreach (Icinga::app()->getLibraries() as $library) {
            $files = array_merge($files, $library->getJsAssets());
        }

        // Prepare core file list
        $coreFiles = [];
        foreach (self::$jsFiles as $file) {
            $filePath = $basedir . '/' . $file;
            $coreFiles[] = $filePath;
            $files[] = $filePath;
        }

        $moduleFiles = [];
        foreach ($moduleManager->getLoadedModules() as $name => $module) {
            if ($module->hasJs()) {
                $jsDir = $module->getJsDir();
                foreach ($module->getJsFiles() as $path) {
                    if (file_exists($path)) {
                        $moduleFiles[$name][$jsDir][] = $path;
                        $files[] = $path;
                    }
                }
            }
        }

        $request = Icinga::app()->getRequest();
        $noCache = $request->getHeader('Cache-Control') === 'no-cache' || $request->getHeader('Pragma') === 'no-cache';

        header('Cache-Control: public,no-cache,must-revalidate');

        if (! $noCache && FileCache::etagMatchesFiles($files)) {
            header("HTTP/1.1 304 Not Modified");
            return;
        } else {
            $etag = FileCache::etagForFiles($files);
        }

        header('ETag: "' . $etag . '"');
        header('Content-Type: application/javascript');

        $cacheFile = 'icinga-' . $etag . $min . '.js';
        $cache = FileCache::instance();
        if (! $noCache && $cache->has($cacheFile)) {
            $cache->send($cacheFile);
            return;
        }

        // We do not minify vendor files
        foreach ($vendorFiles as $file) {
            $out .= ';' . ltrim(trim(file_get_contents($file)), ';') . "\n";
        }

        $baseJs = '';
        foreach ($baseFiles as $file) {
            $baseJs .= file_get_contents($file) . "\n\n\n";
        }

        // Library files need to be namespaced first before they can be included
        foreach (Icinga::app()->getLibraries() as $library) {
            foreach ($library->getJsAssets() as $file) {
                $alreadyMinified = false;
                if ($minified && file_exists(($minFile = substr($file, 0, -3) . '.min.js'))) {
                    $alreadyMinified = true;
                    $file = $minFile;
                }

                $content = self::optimizeDefine(
                    file_get_contents($file),
                    $file,
                    $library->getJsAssetPath(),
                    $library->getName()
                );

                if ($alreadyMinified) {
                    $out .= ';' . ltrim(trim($content), ';') . "\n";
                } else {
                    $js .= $content . "\n\n\n";
                }
            }
        }

        foreach ($coreFiles as $file) {
            $js .= file_get_contents($file) . "\n\n\n";
        }

        foreach ($moduleFiles as $name => $paths) {
            foreach ($paths as $basePath => $filePaths) {
                foreach ($filePaths as $file) {
                    $content = self::optimizeDefine(file_get_contents($file), $file, $basePath, $name);
                    if (substr($file, -7, 7) === '.min.js') {
                        $out .= ';' . ltrim(trim($content), ';') . "\n";
                    } else {
                        $js .= $content . "\n\n\n";
                    }
                }
            }
        }

        if ($minified) {
            $out .= Minifier::minify($js, ['flaggedComments' => false]);
            $baseOut = Minifier::minify($baseJs, ['flaggedComments' => false]);
            $out = ';' . ltrim($baseOut, ';') . "\n" . $out;
        } else {
            $out = $baseJs . $out . $js;
        }

        $cache->store($cacheFile, $out);
        echo $out;
    }

    /**
     * Optimize define() calls in the given JS
     *
     * @param string $js
     * @param string $filePath
     * @param string $basePath
     * @param string $packageName
     *
     * @return string
     */
    public static function optimizeDefine($js, $filePath, $basePath, $packageName)
    {
        if (! preg_match(self::DEFINE_RE, $js, $match) || strpos($js, 'define.amd') !== false) {
            return $js;
        }

        try {
            $assetName = $match[1] ? Json::decode($match[1]) : '';
            if (! $assetName) {
                $assetName = explode('.', basename($filePath))[0];
            }

            $assetName = join(DIRECTORY_SEPARATOR, array_filter([
                $packageName,
                ltrim(substr(dirname($filePath), strlen($basePath)), DIRECTORY_SEPARATOR),
                $assetName
            ]));

            $assetName = Json::encode($assetName, JSON_UNESCAPED_SLASHES);
        } catch (JsonDecodeException $_) {
            $assetName = $match[1];
            Logger::debug('Can\'t optimize name of "%s". Are single quotes used instead of double quotes?', $filePath);
        }

        try {
            $dependencies = $match[2] ? Json::decode($match[2]) : [];
            foreach ($dependencies as &$dependencyName) {
                if ($dependencyName === 'exports') {
                    // exports is a special keyword and doesn't need optimization
                    continue;
                }

                if (preg_match('~^((?:\.\.?/)+)*(.*)~', $dependencyName, $natch)) {
                    $dependencyName = join(DIRECTORY_SEPARATOR, array_filter([
                        $packageName,
                        ltrim(substr(
                            realpath(join(DIRECTORY_SEPARATOR, [dirname($filePath), $natch[1]])),
                            strlen(realpath($basePath))
                        ), DIRECTORY_SEPARATOR),
                        $natch[2]
                    ]));
                }
            }

            $dependencies = Json::encode($dependencies, JSON_UNESCAPED_SLASHES);
        } catch (JsonDecodeException $_) {
            $dependencies = $match[2];
            Logger::debug(
                'Can\'t optimize dependencies of "%s". Are single quotes used instead of double quotes?',
                $filePath
            );
        }

        return str_replace($match[0], sprintf("define(%s, %s, %s", $assetName, $dependencies, $match[3]), $js);
    }
}
