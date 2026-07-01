<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\File;

use Traversable;

class Csv
{
    protected $query;

    protected function __construct()
    {
    }

    public static function fromQuery(Traversable $query)
    {
        $csv = new static();
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
        $first = true;
        $csv = '';
        foreach ($this->query as $row) {
            if ($first) {
                $csv .= implode(',', array_keys((array) $row)) . "\r\n";
                $first = false;
            }
            $out = [];
            foreach ($row as & $val) {
                $out[] = '"' . ($val == '0' ? '0' : ($val ? str_replace('"', '""', $val) : '')) . '"';
            }
            $csv .= implode(',', $out) . "\r\n";
        }

        return $csv;
    }
}
