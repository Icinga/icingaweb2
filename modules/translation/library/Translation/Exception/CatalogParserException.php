<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Translation\Exception;

/**
 * Class CatalogParserException
 *
 * Will be thrown if CatalogParser finds a syntax error.
 *
 * @package Icinga\Module\Translation\Exception
 */
class CatalogParserException extends CatalogException
{
    /**
     * Path of catalog file
     *
     * @var string
     */
    protected $path;

    /**
     * Line where the exception appears
     *
     * @var int
     */
    protected $lineNumber;

    /**
     * Position of character that causes the exception
     *
     * @var int
     */
    protected $position;

    /**
     * CatalogParserException constructor
     *
     * @param   string  $path       Path in which the exception appears
     * @param   int     $lineNumber Line in which the exception appears
     * @param   int     $position   Position in which the exception appears
     * @param   string  $message    The reason for the syntax error
     */
    public function __construct($path, $lineNumber, $position, $message)
    {
        $this->path = $path;
        $this->lineNumber = $lineNumber;
        $this->position = $position;

        parent::__construct(
            'Syntax error in file %s on line %s and position %s: %s',
            $path,
            $lineNumber,
            $position,
            $message
        );
    }

    /**
     * Return path in which the exception appears
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Return line in which the exception appears
     *
     * @return int
     */
    public function getLineNumber()
    {
        return $this->lineNumber;
    }

    /**
     * Return position in which the exception appears
     *
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }
}