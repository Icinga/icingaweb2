<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\File;

use Icinga\Data\DataArray\ArrayDatasource;
use Icinga\File\Csv;
use Icinga\Test\BaseTestCase;

class CsvTest extends BaseTestCase
{
    public function testWhetherValidCsvIsRendered()
    {
        $data = new ArrayDatasource(array(
            array('col1' => 'val1', 'col2' => 'val2', 'col3' => 'val3', 'col4' => 'val4'),
            array('col1' => 'val5', 'col2' => 'val6', 'col3' => 'val7', 'col4' => 'val8')
        ));

        $csv = Csv::fromQuery($data->select());

        $this->assertEquals(
            implode(
                "\r\n",
                array(
                    'col1,col2,col3,col4',
                    '"val1","val2","val3","val4"',
                    '"val5","val6","val7","val8"'
                )
            ) . "\r\n",
            (string) $csv,
            'Csv does not render valid/correct csv structured data'
        );
    }

    public function testTimestampToDateString()
    {
        $firstOfSeptember2018timestamp = 1535760000;

        $data = new ArrayDatasource([
            [
                'firstOfSeptember' => $firstOfSeptember2018timestamp,
                'notimportant' => 'somestring'
            ]
        ]);

        $data->setDataTypeForColumn('firstOfSeptember', 'timestamp');

        $csv = Csv::fromQuery($data->select(), 'Europe/Berlin');

        $this->assertEquals(
            "firstOfSeptember,notimportant\r\n\"2018-09-01T02:00:00+0200\",\"somestring\"\r\n",
            (string) $csv,
            "Csv does not convert timestamps properly to ISO 8601"
        );
    }
}
