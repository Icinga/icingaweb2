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
    const DEFINE_RE = '/(?<!\.)define\(\s*([\'"][^\'"]*[\'"])?[,\s]*(\[[^]]*\])?[,\s]*(function\s*\([^)]*\)|[^=]*=>)/';

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
        'js/icinga/behavior/expandable.js',
        'js/icinga/behavior/filtereditor.js',
        'js/icinga/behavior/selectable.js',
        'js/icinga/behavior/modal.js'
    ];

    protected static $vendorFiles = [
        'js/vendor/jquery-3.4.1',
        'js/vendor/jquery-migrate-3.1.0'
    ];

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

        $js = $out = '';
        $min = $minified ? '.min' : '';

        // Prepare vendor file list
        $vendorFiles = [];
        foreach (self::$vendorFiles as $file) {
            $vendorFiles[] = $basedir . '/' . $file . $min . '.js';
        }

        // Prepare base file list
        $baseFiles = [];
        foreach (self::$baseFiles as $file) {
            $baseFiles[] = $basedir . '/' . $file;
        }

        // Prepare library file list
        $libraryFiles = [];
        foreach (Icinga::app()->getLibraries() as $library) {
            $libraryFiles = array_merge(
                $libraryFiles,
                $library->getJsAssets()
            );
        }

        // Prepare Icinga JS file list
        $jsFiles = [];
        foreach (self::$jsFiles as $file) {
            $jsFiles[] = $basedir . '/' . $file;
        }

        $sharedFiles = [];
        foreach (Icinga::app()->getModuleManager()->getLoadedModules() as $name => $module) {
            if ($module->hasJs()) {
                foreach ($module->getJsFiles() as $path) {
                    if (file_exists($path)) {
                        $jsFiles[] = $path;
                    }
                }
            }

            if ($module->requiresJs()) {
                foreach ($module->getJsRequires() as $path) {
                    $sharedFiles[] = $path;
                }
            }
        }

        $sharedFiles = array_unique($sharedFiles);
        $files = array_merge($vendorFiles, $baseFiles, $libraryFiles, $jsFiles, $sharedFiles);

        $request = Icinga::app()->getRequest();
        $noCache = $request->getHeader('Cache-Control') === 'no-cache' || $request->getHeader('Pragma') === 'no-cache';

        header('Cache-Control: public');
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

        foreach ($baseFiles as $file) {
            $js .= file_get_contents($file) . "\n\n\n";
        }

        // Library files need to be namespaced first before they can be included
        foreach (Icinga::app()->getLibraries() as $library) {
            foreach ($library->getJsAssets() as $file) {
                $content = file_get_contents($file) . "\n\n\n";
                if (preg_match(self::DEFINE_RE, $content, $match)) {
                    try {
                        $assetName = $match[1] ? Json::decode($match[1]) : '';
                        if (! $assetName) {
                            $assetName = explode('.', basename($file))[0];
                        }

                        $assetName = join(DIRECTORY_SEPARATOR, array_filter([
                            $library->getName(),
                            ltrim(substr(dirname($file), strlen($library->getJsAssetPath())), DIRECTORY_SEPARATOR),
                            $assetName
                        ]));

                        $assetName = Json::encode($assetName, JSON_UNESCAPED_SLASHES);
                    } catch (JsonDecodeException $_) {
                        $assetName = $match[1];
                        Logger::error(
                            'Can\'t optimize name of "%s". Are single quotes used instead of double quotes?',
                            $file
                        );
                    }

                    try {
                        $dependencies = $match[2] ? Json::decode($match[2]) : [];
                        foreach ($dependencies as &$dependencyName) {
                            if (preg_match('~^((?:\.\.?/)+)*(.*)~', $dependencyName, $natch)) {
                                $dependencyName = join(DIRECTORY_SEPARATOR, array_filter([
                                    $library->getName(),
                                    ltrim(substr(
                                        realpath(join(DIRECTORY_SEPARATOR, [dirname($file), $natch[1]])),
                                        strlen(realpath($library->getJsAssetPath()))
                                    ), DIRECTORY_SEPARATOR),
                                    $natch[2]
                                ]));
                            }
                        }

                        $dependencies = Json::encode($dependencies, JSON_UNESCAPED_SLASHES);
                    } catch (JsonDecodeException $_) {
                        $dependencies = $match[2];
                        Logger::error(
                            'Can\'t optimize dependencies of "%s". Are single quotes used instead of double quotes?',
                            $file
                        );
                    }

                    $content = str_replace(
                        $match[0],
                        sprintf("define(%s, %s, %s", $assetName, $dependencies, $match[3]),
                        $content
                    );
                }

                $js .= $content;
            }
        }

        foreach ($jsFiles as $file) {
            $js .= file_get_contents($file) . "\n\n\n";
        }

        foreach ($sharedFiles as $file) {
            if (substr($file, -7, 7) === '.min.js') {
                $out .= ';' . ltrim(trim(file_get_contents($file)), ';') . "\n";
            } else {
                $js .= file_get_contents($file) . "\n\n\n";
            }
        }

        if ($minified) {
            require_once 'JShrink/Minifier.php';
            $out .= Minifier::minify($js, array('flaggedComments' => false));
        } else {
            $out .= $js;
        }
        $cache->store($cacheFile, $out);
        echo $out;
    }
}
