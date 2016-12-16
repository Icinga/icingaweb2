<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\File;

use Icinga\Exception\IcingaException;
use Icinga\Util\Buffer;
use stdClass;
use Traversable;

/**
 * Generates JSON from a query result
 */
class Json
{
    /**
     * The query to generate JSON from the result of
     *
     * @var Traversable
     */
    protected $query;

    /**
     * Cache for {@link render()}
     *
     * @var Buffer|null
     */
    protected $renderBuffer;

    /**
     * Json constructor
     *
     * @param   Traversable $query  The query to generate JSON from the result of
     */
    protected function __construct(Traversable $query)
    {
        $this->query = $query;
        $this->render();
    }

    /**
     * Factory
     *
     * @param   Traversable $query  The query to generate JSON from the result of
     *
     * @return  static
     */
    public static function create(Traversable $query)
    {
        return new static($query);
    }

    /**
     * Render JSON and pass it to the user agent (as with {@link fpassthru()})
     */
    public function dump()
    {
        $this->render()->fpassthru();
    }

    /**
     * Return the rendered JSON
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->render();
    }

    /**
     * Return the rendered JSON
     *
     * @return Buffer
     */
    protected function render()
    {
        if ($this->renderBuffer === null) {
            $this->renderBuffer = new Buffer();
            $this->renderBuffer->append('[');

            $first = true;
            foreach ($this->query as $row) {
                if ($first) {
                    $first = false;
                } else {
                    $this->renderBuffer->append(',');
                }
                $this->renderBuffer->append($this->renderRow($row));
            }

            $this->renderBuffer->append(']');
        }

        return $this->renderBuffer;
    }

    /**
     * Return a JSON string representing the given columns of a single row
     *
     * @param   stdClass    $columns
     *
     * @return  string
     *
     * @throws  IcingaException     In case of an error
     */
    protected function renderRow(stdClass $columns)
    {
        $result = json_encode($columns);
        if ($result === false) {
            if (function_exists('json_last_error_msg')) {
                // since PHP 5.5
                $errorMessage = json_last_error_msg();
            } else {
                $lastError = json_last_error();
                $constants = get_defined_constants(true);
                $errorMessage = 'Unknown error';
                foreach ($constants['json'] as $constant => $value) {
                    if ($value === $lastError && substr($constant, 0, 11) === 'JSON_ERROR_') {
                        $errorMessage = $constant;
                        break;
                    }
                }
            }

            throw new IcingaException('Couldn\'t encode %s as JSON: %s', print_r($columns, true), $errorMessage);
        }
        return $result;
    }
}
