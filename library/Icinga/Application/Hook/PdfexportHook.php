<?php
/* Icinga Web 2 | (c) 2018 Icinga Development Team | GPLv2+ */

namespace Icinga\Application\Hook;

use Icinga\Module\Pdfexport\PrintableHtmlDocument;

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
    abstract public function isSupported();

    /**
     * Render the specified HTML to PDF and stream it to the client
     *
     * @param   string|PrintableHtmlDocument    $html       The HTML to render to PDF
     * @param   string                          $filename   The filename for the generated PDF
     */
    abstract public function streamPdfFromHtml($html, string $filename);
}
