<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Application\Hook;

use Icinga\Application\Hook;
use Icinga\Application\Logger;
use Icinga\Exception\IcingaException;
use ipl\Html\ValidHtml;
use RuntimeException;
use Throwable;

/**
 * Base class for the PDF Export Hook
 */
abstract class PdfexportHook
{
    /**
     * Get the first hook
     *
     * @return static
     */
    public static function first()
    {
        foreach (Hook::all('Pdfexport') as $exporter) {
            try {
                if ($exporter->isSupported()) {
                    return $exporter;
                }
            } catch (Throwable $e) {
                Logger::error(
                    "PDF exporter reported an error during support check: %s\n%s",
                    $e,
                    IcingaException::getConfidentialTraceAsString($e),
                );
            }
        }

        throw new RuntimeException('No supported PDF exporter available');
    }

    /**
     * Get whether PDF export is supported
     *
     * @return bool
     */
    abstract public function isSupported();

    /**
     * Render the specified HTML to PDF and stream it to the client
     *
     * @param ValidHtml $html The HTML to render to PDF
     * @param string $filename The filename for the generated PDF
     *
     * @return never
     */
    abstract public function streamPdfFromHtml($html, $filename);

    /**
     * Render the specified HTML to PDF and return the PDF document as a string
     *
     * @param ValidHtml $html The HTML to render to PDF
     *
     * @return string
     */
    abstract public function htmlToPdf($html);
}
