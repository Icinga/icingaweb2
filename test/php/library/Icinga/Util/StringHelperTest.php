<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Util;

use Icinga\Test\BaseTestCase;
use Icinga\Util\StringHelper;

class StringHelperTest extends BaseTestCase
{
    public function testWhetherTrimSplitReturnsACorrectValue()
    {
        $this->assertEquals(
            ['one', 'two', 'three'],
            StringHelper::trimSplit(' one ,two  , three'),
            'String::trimSplit does not properly split a string and/or trim its elements'
        );
    }

    public function testWhetherTrimSplitSplitsByTheGivenDelimiter()
    {
        $this->assertEquals(
            ['one', 'two', 'three'],
            StringHelper::trimSplit('one.two.three', '.'),
            'String::trimSplit does not split a string by the given delimiter'
        );
    }
}
