<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Web\Widget\Tabextension;

require_once('../../library/Icinga/Web/Widget/Widget.php');
require_once('../../library/Icinga/Web/Widget/Tab.php');
require_once('../../library/Icinga/Web/Widget/Tabs.php');
require_once('../../library/Icinga/Web/Widget/Tabextension/Tabextension.php');
require_once('../../library/Icinga/Web/Widget/Tabextension/OutputFormat.php');
require_once('../../library/Icinga/Web/Url.php');

require_once('library/Icinga/Web/RequestMock.php');
require_once('library/Icinga/Web/ViewMock.php');

require_once('Zend/View/Abstract.php');

use Icinga\Web\View;
use Icinga\Web\Url;
use Icinga\Web\Widget\Tabextension\OutputFormat;
use PHPUnit_Framework_TestCase;
use Icinga\Web\Widget\Tabs;
use Tests\Icinga\Web\RequestMock;
use Tests\Icinga\Web\ViewMock;
use \Zend_View_Abstract;


/**
 * Test for the OutputFormat Tabextension
 *
 */
class OutputFormatTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test if a simple apply adds all tabs from the extender
     *
     */
    public function testApply()
    {
        $tabs = new Tabs();
        Url::$overwrittenRequest = new RequestMock();
        $tabs->extend(new OutputFormat());
        $this->assertEquals(3, $tabs->count(), "Asserting new tabs being available after extending the tab bar");
        Url::$overwrittenRequest = null;
    }

    /**
     * Test if an apply with disabled output formats doesn't add these tabs
     *
     */
    public function testDisableOutputFormat()
    {
        Url::$overwrittenRequest = new RequestMock();
        $tabs = new Tabs();
        $tabs->extend(new OutputFormat(array(OutputFormat::TYPE_PDF)));
        $this->assertEquals(
            2,
            $tabs->count(),
            "Asserting two tabs being available after extending the tab bar and ignoring PDF"
        );
        Url::$overwrittenRequest = null;
    }
}