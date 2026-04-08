<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Application\Hook;

use ipl\Html\ValidHtml;

/**
 * Base class for the PDF Export Hook
 */
abstract class PdfexportHook
{
    /**
     * Get whether PDF export is supported
     *
     * @return  bool
     */
    abstract public function isSupported(): bool;

    /**
     * Render the specified HTML to PDF and stream it to the client
     *
     * @param ValidHtml $html The HTML to render to PDF
     * @param string $filename The filename for the generated PDF
     */
    abstract public function streamPdfFromHtml(ValidHtml $html, string $filename): never;

    /**
     * Render the specified HTML to PDF and return the PDF document as a string
     *
     * @param ValidHtml $html The HTML to render to PDF
     *
     * @return string
     */
    abstract public function htmlToPdf(ValidHtml $html): string;
}
