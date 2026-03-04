<?php
/* Icinga Web 2 | (c) 2018 Icinga Development Team | GPLv2+ */

namespace Icinga\Application\Hook;

/**
 * Base class for the PDF Export Hook
 */
abstract class PdfexportHook
{
    use Essentials;

    protected static function getHookName(): string
    {
        return 'Pdfexport';
    }

    /**
     * Get whether PDF export is supported
     *
     * @return  bool
     */
    abstract public function isSupported();

    /**
     * Render the specified HTML to PDF and stream it to the client
     *
     * @param   string  $html       The HTML to render to PDF
     * @param   string  $filename   The filename for the generated PDF
     */
    abstract public function streamPdfFromHtml($html, $filename);
}
