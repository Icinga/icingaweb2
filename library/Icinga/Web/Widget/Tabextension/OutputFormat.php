<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Widget\Tabextension;

use Icinga\Application\Platform;
use Icinga\Web\Url;
use Icinga\Web\Widget\Tab;
use Icinga\Web\Widget\Tabs;

/**
 * Tabextension that offers different output formats for the user in the dropdown area
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
     * @param array $disabled An array of output types to <b>not</b> show.
     */
    public function __construct(array $disabled = array())
    {
        foreach ($this->getSupportedTypes() as $type => $tabConfig) {
            if (!in_array($type, $disabled)) {
                $tabConfig['url'] = Url::fromRequest();
                $tab = new Tab($tabConfig);
                $tab->setTargetBlank();
                $this->tabs[] = $tab;
            }
        }
    }

    /**
     * Applies the format selectio to the provided tabset
     *
     * @param   Tabs $tabs The tabs object to extend with
     *
     * @see     Tabextension::apply()
     */
    public function apply(Tabs $tabs)
    {
        foreach ($this->tabs as $tab) {
            $tabs->addAsDropdown($tab->getName(), $tab);
        }
    }

    /**
     * Return an array containing the tab definitions for all supported types
     *
     * Using array_keys on this array or isset allows to check whether a
     * requested type is supported
     *
     * @return  array
     */
    public function getSupportedTypes()
    {
        $supportedTypes = array();

        if (Platform::extensionLoaded('gd')) {
            $supportedTypes[self::TYPE_PDF] = array(
                'name'      => 'pdf',
                'label'     => 'PDF',
                'icon'      => 'file-pdf',
                'urlParams' => array('format' => 'pdf'),
            );
        }

        $supportedTypes[self::TYPE_CSV] = array(
            'name'      => 'csv',
            'label'     => 'CSV',
            'icon'      => 'file-excel',
            'urlParams' => array('format' => 'csv')
        );

        if (Platform::extensionLoaded('json')) {
            $supportedTypes[self::TYPE_JSON] = array(
                'name'      => 'json',
                'label'     => 'JSON',
                'icon'      => 'img/icons/json.png',
                'urlParams' => array('format' => 'json')
            );
        }

        return $supportedTypes;
    }
}
