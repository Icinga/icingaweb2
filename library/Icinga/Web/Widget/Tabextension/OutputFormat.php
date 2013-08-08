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

namespace Icinga\Web\Widget\Tabextension;

use Icinga\Application\Logger;
use Icinga\Web\Widget\Tab;
use Icinga\Web\Widget\Tabs;
use Icinga\Web\Url;

/**
 * Tabextension that offers different output formats for the user in the dropdown area
 *
 *
 */
class OutputFormat implements Tabextension
{
    /**
     * PDF output type
     */
    const TYPE_PDF = 'pdf';

    /**
     * JSON output type
     */
    const TYPE_JSON = 'json';

    /**
     * CSV output type
     */
    const TYPE_CSV = 'csv';

    /**
     * An array containing the tab definitions for all supported types
     *
     * Using array_keys on this array or isset allows to check whether a
     * requested type is supported
     *
     * @var array
     */
    private $supportedTypes = array(
        self::TYPE_PDF => array(
            'name'      => 'pdf',
            'title'     => 'PDF',
            'icon'      => 'img/classic/application-pdf.png',
            'urlParams' => array('filetype' => 'pdf')
        ),
        self::TYPE_CSV => array(
            'name'      => 'csv',
            'title'     => 'CSV',
            'icon'      => 'img/classic/application-csv.png',
            'urlParams' => array('filetype' => 'csv')
        ),
        self::TYPE_JSON => array(
            'name'      => 'json',
            'title'     => 'JSON',
            'icon'      => 'img/classic/application-json.png',
            'urlParams' => array('filetype' => 'json')
        )
    );

    /**
     * An array of tabs to be added to the dropdown area
     *
     * @var array
     */
    private $tabs = array();

    /**
     * Create a new OutputFormat extender
     *
     * In general, it's assumed that all types are supported when an outputFormat extension
     * is added, so this class offers to remove specific types instead of adding ones
     *
     * @param array $disabled       An array of output types to <b>not</b> show.
     *
     */
    public function __construct(array $disabled = array())
    {
        foreach ($this->supportedTypes as $type => $values) {
            if (in_array($type, $disabled)) {
                continue;
            }
            if (!isset($this->supportedTypes[$type])) {
                Logger::error('Tried to add an unsupported output type: %s', $type);
                continue;
            }
            $tabConfig = $this->supportedTypes[$type];
            $tabConfig["url"] = Url::fromRequest();
            $this->tabs[] = new Tab($tabConfig);
        }
    }

    /**
     * @see Tabextension::apply()
     */
    public function apply(Tabs $tabs)
    {
        foreach ($this->tabs as $tab) {
            $tabs->addAsDropdown($tab->getName(), $tab);
        }
    }
}