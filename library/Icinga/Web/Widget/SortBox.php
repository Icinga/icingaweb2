<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Widget;

use Icinga\Application\Icinga;
use Icinga\Data\Sortable;
use Icinga\Data\SortRules;
use Icinga\Web\Form;
use Icinga\Web\Request;

/**
 * SortBox widget
 *
 * The "SortBox" Widget allows you to create a generic sort input for sortable views. It automatically creates a select
 * box with all sort options and a dropbox with the sort direction. It also handles automatic submission of sorting
 * changes and draws an additional submit button when JavaScript is disabled.
 *
 * The constructor takes a string for the component name and an array containing the select options, where the key is
 * the value to be submitted and the value is the label that will be shown. You then should call setRequest in order
 * to  make sure the form is correctly populated when a request with a sort parameter is being made.
 *
 * Call setQuery in case you'll do not want to handle URL parameters manually, but to automatically apply the user's
 * chosen sort rules on the given sortable query. This will also allow the SortBox to display the user the correct
 * default sort rules if the given query provides already some sort rules.
 */
class SortBox extends AbstractWidget
{
    /**
     * An array containing all sort columns with their associated labels
     *
     * @var array
     */
    protected $sortFields;

    /**
     * An array containing default sort directions for specific columns
     *
     * The first entry will be used as default sort column.
     *
     * @var array
     */
    protected $sortDefaults;

    /**
     * The name used to uniquely identfy the forms being created
     *
     * @var string
     */
    protected $name;

    /**
     * The request to fetch sort rules from
     *
     * @var Request
     */
    protected $request;

    /**
     * The query to apply sort rules on
     *
     * @var Sortable
     */
    protected $query;

    /**
     * Create a SortBox with the entries from $sortFields
     *
     * @param   string  $name           The name for the SortBox
     * @param   array   $sortFields     An array containing the columns and their labels to be displayed in the SortBox
     * @param   array   $sortDefaults   An array containing default sort directions for specific columns
     */
    public function __construct($name, array $sortFields, array $sortDefaults = null)
    {
        $this->name = $name;
        $this->sortFields = $sortFields;
        $this->sortDefaults = $sortDefaults;
    }

    /**
     * Create a SortBox
     *
     * @param   string  $name           The name for the SortBox
     * @param   array   $sortFields     An array containing the columns and their labels to be displayed in the SortBox
     * @param   array   $sortDefaults   An array containing default sort directions for specific columns
     *
     * @return  SortBox
     */
    public static function create($name, array $sortFields, array $sortDefaults = null)
    {
        return new static($name, $sortFields, $sortDefaults);
    }

    /**
     * Set the request to fetch sort rules from
     *
     * @param   Request     $request
     *
     * @return  $this
     */
    public function setRequest($request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Set the query to apply sort rules on
     *
     * @param   Sortable    $query
     *
     * @return  $this
     */
    public function setQuery(Sortable $query)
    {
        $this->query = $query;
        return $this;
    }

    /**
     * Return the default sort rule for the query
     *
     * @param   string  $column     An optional column
     *
     * @return  array               An array of two values: $column, $direction
     */
    protected function getSortDefaults($column = null)
    {
        $direction = null;
        if (! empty($this->sortDefaults) && ($column === null || isset($this->sortDefaults[$column]))) {
            if ($column === null) {
                reset($this->sortDefaults);
                $column = key($this->sortDefaults);
            }

            $direction = $this->sortDefaults[$column];
        } elseif ($this->query !== null && $this->query instanceof SortRules) {
            $sortRules = $this->query->getSortRules();
            if ($column === null) {
                $column = key($sortRules);
            }

            if ($column !== null && isset($sortRules[$column]['order'])) {
                $direction = strtoupper($sortRules[$column]['order']) === Sortable::SORT_DESC ? 'desc' : 'asc';
            }
        } elseif ($column === null) {
            reset($this->sortFields);
            $column = key($this->sortFields);
        }

        return array($column, $direction);
    }

    /**
     * Apply the sort rules from the given or current request on the query
     *
     * @param   Request     $request
     *
     * @return  $this
     */
    public function handleRequest(Request $request = null)
    {
        if ($this->query !== null) {
            if ($request === null) {
                $request = Icinga::app()->getRequest();
            }

            if (! ($sort = $request->getParam('sort'))) {
                list($sort, $dir) = $this->getSortDefaults();
            } else {
                list($_, $dir) = $this->getSortDefaults($sort);
            }

            $this->query->order($sort, $request->getParam('dir', $dir));
        }

        return $this;
    }

    /**
     * Render this SortBox as HTML
     *
     * @return  string
     */
    public function render()
    {
        $columnForm = new Form();
        $columnForm->setTokenDisabled();
        $columnForm->setName($this->name . '-column');
        $columnForm->setAttrib('class', 'inline');
        $columnForm->addElement(
            'select',
            'sort',
            array(
                'autosubmit'    => true,
                'label'         => $this->view()->translate('Sort by'),
                'multiOptions'  => $this->sortFields,
                'decorators'    => array(
                    array('ViewHelper'),
                    array('Label')
                )
            )
        );

        $column = null;
        if ($this->request) {
            $url = $this->request->getUrl();
            if ($url->hasParam('sort')) {
                $column = $url->getParam('sort');

                if ($url->hasParam('dir')) {
                    $direction = $url->getParam('dir');
                } else {
                    list($_, $direction) = $this->getSortDefaults($column);
                }
            } elseif ($url->hasParam('dir')) {
                $direction = $url->getParam('dir');
                list($column, $_) = $this->getSortDefaults();
            }
        }

        if ($column === null) {
            list($column, $direction) = $this->getSortDefaults();
        }

        // TODO(el): ToggleButton :)
        $toggle = array('asc' => 'sort-name-down', 'desc' => 'sort-name-up');
        unset($toggle[strtolower($direction) ?: 'asc']);
        $newDirection = key($toggle);
        $icon = current($toggle);

        $orderForm = new Form();
        $orderForm->setTokenDisabled();
        $orderForm->setName($this->name . '-order');
        $orderForm->setAttrib('class', 'inline sort-direction-control');
        $orderForm->addElement(
            'hidden',
            'dir'
        );
        $orderForm->addElement(
            'button',
            'btn_submit',
            array(
                'ignore'        => true,
                'type'          => 'submit',
                'label'         => $this->view()->icon($icon),
                'decorators'    => array('ViewHelper'),
                'escape'        => false,
                'class'         => 'link-button spinner',
                'value'         => 'submit',
                'title'         => t('Change sort direction'),
            )
        );


        $columnForm->populate(array('sort' => $column));
        $orderForm->populate(array('dir' => $newDirection));
        return '<div class="sort-control">' . $columnForm . $orderForm . '</div>';
    }
}
