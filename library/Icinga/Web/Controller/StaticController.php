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

        $assetRoot = $library->getStaticAssetPath();
        $filePath = $assetRoot . DIRECTORY_SEPARATOR . $assetPath;

        // Doesn't use realpath as it isn't supposed to access files outside asset/static
        if (! is_readable($filePath) || ! is_file($filePath)) {
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
                ->setHeader('Content-Type', mime_content_type($filePath), true)
                ->setHeader('Last-Modified', gmdate('D, d M Y H:i:s', $fileStat['mtime']) . ' GMT')
                ->setBody(file_get_contents($filePath));
        }
    }
}
