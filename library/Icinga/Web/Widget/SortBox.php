<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Widget;

use Icinga\Web\Form;
use Icinga\Web\Request;
use Icinga\Data\Sortable;
use Icinga\Application\Icinga;

/**
 * SortBox widget
 *
 * The "SortBox" Widget allows you to create a generic sort input for sortable views. It automatically creates a form
 * containing a select box with all sort options and a dropbox with the sort direction. It also handles automatic
 * submission of sorting changes and draws an additional submit button when JavaScript is disabled.
 *
 * The constructor takes an string for the component name and an array containing the select options, where the key is
 * the value to be submitted and the value is the label that will be shown. You then should call setRequest in order
 * to  make sure the form is correctly populated when a request with a sort parameter is being made.
 *
 * Example:
 *  <pre><code>
 *      $this->view->sortControl = new SortBox(
 *          $this->getRequest()->getActionName(),
 *          $columns
 *      );
 *      $this->view->sortControl->setRequest($this->getRequest());
 *  </code></pre>
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
     * The name of the form that will be created
     *
     * @var string
     */
    protected $name;

    /**
     * A request object used for initial form population
     *
     * @var Request
     */
    protected $request;

    /**
     * What to apply sort parameters on
     *
     * @var Sortable
     */
    protected $query = null;

    /**
     * Create a SortBox with the entries from $sortFields
     *
     * @param   string  $name           The name for the SortBox
     * @param   array   $sortFields     An array containing the columns and their labels to be displayed in the SortBox
     */
    public function __construct($name, array $sortFields)
    {
        $this->name = $name;
        $this->sortFields = $sortFields;
    }

    /**
     * Create a SortBox
     *
     * @param   string  $name           The name for the SortBox
     * @param   array   $sortFields     An array containing the columns and their labels to be displayed in the SortBox
     *
     * @return  SortBox
     */
    public static function create($name, array $sortFields)
    {
        return new static($name, $sortFields);
    }

    /**
     * Apply the parameters from the given request on this SortBox
     *
     * @param   Request     $request    The request to use for populating the form
     *
     * @return  $this
     */
    public function setRequest($request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @param Sortable $query
     *
     * @return $this
     */
    public function setQuery(Sortable $query)
    {
        $this->query = $query;
        return $this;
    }

    public function handleRequest(Request $request = null)
    {
        if ($this->query !== null) {
            if ($request === null) {
                $request = Icinga::app()->getFrontController()->getRequest();
            }
            if ($sort = $request->getParam('sort')) {
                $this->query->order($sort, $request->getParam('dir'));
            }
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

        $orderForm = new Form();
        $orderForm->setTokenDisabled();
        $orderForm->setName($this->name . '-order');
        $orderForm->setAttrib('class', 'inline');
        $orderForm->addElement(
            'select',
            'dir',
            array(
                'autosubmit'    => true,
                'label'         => $this->view()->translate('Direction', 'sort direction'),
                'multiOptions'  => array(
                    'asc'       => $this->view()->translate('Ascending', 'sort direction'),
                    'desc'      => $this->view()->translate('Descending', 'sort direction')
                ),
                'decorators'    => array(
                    array('ViewHelper'),
                    array('Label', array('class' => 'no-js'))
                )
            )
        );

        if ($this->request) {
            $url = $this->request->getUrl();
            if ($url->hasParam('sort')) {
                $columnForm->populate(array('sort' => $url->getParam('sort')));
            }

            if ($url->hasParam('dir')) {
                $orderForm->populate(array('dir' => $url->getParam('dir')));
            }
        }

        return '<div class="sort-control">' . $columnForm . $orderForm . '</div>';
    }
}
