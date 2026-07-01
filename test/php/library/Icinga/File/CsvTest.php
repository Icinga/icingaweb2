<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\File;

use Icinga\Data\DataArray\ArrayDatasource;
use Icinga\File\Csv;
use Icinga\Test\BaseTestCase;

class CsvTest extends BaseTestCase
{
    public function testWhetherValidCsvIsRendered()
    {
        $data = new ArrayDatasource([
            ['col1' => 'val1', 'col2' => 'val2', 'col3' => 'val3', 'col4' => 'val4'],
            ['col1' => 'val5', 'col2' => 'val6', 'col3' => 'val7', 'col4' => 'val8']
        ]);

        $csv = Csv::fromQuery($data->select());

        $this->assertEquals(
            implode(
                "\r\n",
                [
                    'col1,col2,col3,col4',
                    '"val1","val2","val3","val4"',
                    '"val5","val6","val7","val8"'
                ]
            ) . "\r\n",
            (string) $csv,
            'Csv does not render valid/correct csv structured data'
        );
    }
}
