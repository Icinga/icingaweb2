<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\File;

use Icinga\Data\Browsable;
use Exception;

class Csv
{
    protected $query;

    protected function __construct() {}

    public static function fromQuery(Browsable $query)
    {
        $csv = new Csv();
        $csv->query = $query;
        return $csv;
    }

    public function dump()
    {
        header('Content-type: text/csv');
        echo (string) $this;
    }

    public function __toString()
    {
        try {
            $first = true;
            $csv = '';
            foreach ($this->query->getQuery()->fetchAll() as $row) {
                if ($first) {
                    $csv .= implode(',', array_keys((array) $row)) . "\r\n";
                    $first = false;
                }
                $out = array();
                foreach ($row as & $val) {
                    $out[] = '"' . $val . '"';
                }
                $csv .= implode(',', $out) . "\r\n";
            }

            return $csv;
        } catch (Exception $e) {
            return (string) $e;
        }
    }
}
