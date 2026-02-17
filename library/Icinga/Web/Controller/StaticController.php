<?php
/* Icinga Web 2 | (c) 2020 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Controller;

use Icinga\Application\Icinga;
use Icinga\Web\Request;

class StaticController
{
    /**
     * Handle incoming request
     *
     * @param Request $request
     *
     * @returns void
     */
    public function handle(Request $request)
    {
        $app = Icinga::app();

        // +4 because strlen('/lib') === 4
        $assetPath = ltrim(substr($request->getRequestUri(), strlen($request->getBaseUrl()) + 4), '/');

        $library = null;
        foreach ($app->getLibraries() as $candidate) {
            if (substr($assetPath, 0, strlen($candidate->getName())) === $candidate->getName()) {
                $library = $candidate;
                $assetPath = ltrim(substr($assetPath, strlen($candidate->getName())), '/');
                break;
            }
        }

        if ($library === null) {
            $app->getResponse()
                ->setHttpResponseCode(404);

            return;
        }

        preg_match('~^(\w+)/~', $assetPath, $m);
        switch ($m[1] ?? null) {
            case 'js':
                $assetPath = substr($assetPath, 3);
                $assetRoot = $library->getJsAssetPath();
                $contentType = 'text/javascript';

                break;
            case 'css':
                $assetPath = substr($assetPath, 4);
                $assetRoot = $library->getCssAssetPath();
                $contentType = 'text/css';

                break;
            case 'static':
                $assetPath = substr($assetPath, 7);

                // `static/` is the default
            default:
                $assetRoot = $library->getStaticAssetPath();
                $contentType = null;
        }

        if (empty($assetRoot)) {
            $app->getResponse()
                ->setHttpResponseCode(404);

            return;
        }

        $filePath = $assetRoot . DIRECTORY_SEPARATOR . $assetPath;
        $dirPath = realpath(dirname($filePath)); // dirname, because the file may be a link

        if ($dirPath === false
            || substr($dirPath, 0, strlen($assetRoot)) !== $assetRoot
            || ! is_file($filePath)
        ) {
            $app->getResponse()
                ->setHttpResponseCode(404);

            return;
        }

        $fileStat = stat($filePath);
        $eTag = sprintf(
            '%x-%x-%x',
            $fileStat['ino'],
            $fileStat['size'],
            (float) str_pad($fileStat['mtime'], 16, '0')
        );

        $app->getResponse()->setHeader(
            'Cache-Control',
            'public, max-age=1814400, stale-while-revalidate=604800',
            true
        );

        if ($request->getServer('HTTP_IF_NONE_MATCH') === $eTag) {
            $app->getResponse()
                ->setHttpResponseCode(304);
        } else {
            $app->getResponse()
                ->setHeader('ETag', $eTag)
                ->setHeader('Content-Type', $contentType ?? mime_content_type($filePath), true)
                ->setHeader('Last-Modified', gmdate('D, d M Y H:i:s', $fileStat['mtime']) . ' GMT')
                ->setBody(file_get_contents($filePath));
        }
    }
}
