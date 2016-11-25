<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Translation\Catalog;

use ArrayAccess;
use Exception;
use Icinga\Exception\IcingaException;
use Icinga\Module\Translation\Exception\CatalogHeaderException;

/**
 * Class CatalogHeader
 *
 * Provides a convenient interface to handle headers of gettext PO files.
 *
 * @package Icinga\Module\Translation\Catalog
 */
class CatalogHeader implements ArrayAccess
{
    /**
     * The format used in header entries to represent date and time
     *
     * @var string
     */
    const DATETIME_FORMAT = 'Y-m-d H:iO';

    /**
     * The entries of this CatalogHeader
     *
     * @var array
     */
    protected $headers;

    /**
     * The copyright information for this CatalogHeader
     *
     * @var array
     */
    protected $copyrightInformation;

    /**
     * Create a new CatalogHeader
     *
     * @param   array   $headers
     */
    public function __construct(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * Create and return a new CatalogHeader from the given string
     *
     * @param   string  $header
     *
     * @return  CatalogHeader
     *
     * @throws  CatalogHeaderException
     */
    public static function fromString($header)
    {
        $lines = preg_split(
            '/\n(?=\S+: )/',
            substr($header, -1) === "\n"
                ? substr($header, 0, -1)
                : $header
        );

        $headers = array();
        foreach ($lines as $line)
        {
            try {
                list($key, $value) = explode(': ', $line, 2);
            } catch (Exception $_) {
                throw new CatalogHeaderException('Missing ": " in "' . $line . '"');
            }

            $headers[$key] = $value;
        }

        return new CatalogHeader($headers);
    }

    /**
     * Set copyright information for this CatalogHeader
     *
     * @param   array   $copyrightInformation
     *
     * @return  $this
     */
    public function setCopyrightInformation($copyrightInformation)
    {
        $this->copyrightInformation = $copyrightInformation;
        return $this;
    }

    /**
     * Return copyright information for this CatalogHeader
     *
     * @return array
     */
    public function getCopyrightInformation()
    {
        return $this->copyrightInformation;
    }

    /**
     * Return whether the given header exists
     *
     * @param   string  $name   The name of the header
     *
     * @return  bool
     */
    public function offsetExists($name)
    {
        return isset($this->headers[$name]);
    }

    /**
     * Return the value of the given header
     *
     * @param   string  $name   The name of the header
     *
     * @return  string
     */
    public function offsetGet($name)
    {
        return $this->headers[$name];
    }

    /**
     * Set the given header to the given value
     *
     * @param   string  $name   The name of the header
     * @param   string  $value  The value of the header
     */
    public function offsetSet($name, $value)
    {
        $this->headers[$name] = $value;
    }

    /**
     * Unset the given header
     *
     * @param   string  $name   The name of the header
     */
    public function offsetUnset($name)
    {
        unset($this->headers[$name]);
    }

    /**
     * Render and return this CatalogHeader as string
     *
     * @return  string
     *
     * @throws  CatalogHeaderException
     */
    public function render()
    {
        if (empty($this->headers)) {
            throw new CatalogHeaderException('No headers to render');
        }

        $entries = array();
        if (! empty($this->copyrightInformation)) {
            foreach ($this->copyrightInformation as $value) {
                $entries[] = '# ' . $value;
            }
        }

        $entries[] = "msgid \"\"\nmsgstr \"\"";
        foreach ($this->headers as $key => $value)
        {
            $entries[] = '"' . strtr(sprintf('%s: %s', $key, $value), array_flip(CatalogParser::$escapedChars)) . '\n"';
        }

        return implode("\n", $entries);
    }

    /**
     * @see CatalogHeader::render()
     */
    public function __toString()
    {
        try {
            return $this->render();
        } catch (Exception $e) {
            return 'Failed to render CatalogHeader: ' . IcingaException::describe($e);
        }
    }
}
