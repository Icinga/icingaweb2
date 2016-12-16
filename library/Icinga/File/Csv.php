<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\File;

use Icinga\Util\Buffer;
use Traversable;

class Csv
{
    protected $query;

    /**
     * Cache for {@link render()}
     *
     * @var Buffer|null
     */
    protected $renderBuffer;

    protected function __construct()
    {
    }

    public static function fromQuery(Traversable $query)
    {
        $csv = new static();
        $csv->query = $query;
        $csv->render();
        return $csv;
    }

    public function dump()
    {
        $this->render()->fpassthru();
    }

    public function __toString()
    {
        return (string) $this->render();
    }

    /**
     * Return the rendered CSV
     *
     * @return Buffer
     */
    protected function render()
    {
        if ($this->renderBuffer === null) {
            $this->renderBuffer = new Buffer();
            $first = true;
            foreach ($this->query as $row) {
                if ($first) {
                    $this->renderBuffer->append($this->renderRow(array_keys((array) $row)));
                    $first = false;
                }
                $this->renderBuffer->append($this->renderRow(array_values((array) $row)));
            }
        }

        return $this->renderBuffer;
    }

    /**
     * Return a single CSV row string representing the given columns
     *
     * @param   array   $columns
     *
     * @return  string
     */
    protected function renderRow(array $columns)
    {
        $quoted = array();
        foreach ($columns as $column) {
            $quoted[] = '"' . str_replace('"', '""', $column) . '"';
        }
        return implode(',', $quoted) . "\r\n";
    }
}
