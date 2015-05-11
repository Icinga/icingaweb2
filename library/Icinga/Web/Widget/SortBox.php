<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Widget;

use Icinga\Web\Form;
use Icinga\Web\Request;

/**
 * SortBox widget
 *
 * The "SortBox" Widget allows you to create a generic sort input for sortable views. It automatically creates a form
 * containing a select box with all sort options and a dropbox with the sort direction. It also handles automatic
 * submission of sorting changes and draws an additional submit button when JavaScript is disabled.
 *
 * The constructor takes an string for the component name and an array containing the select options, where the key is
 * the value to be submitted and the value is the label that will be shown. You then should call applyRequest in order
 * to  make sure the form is correctly populated when a request with a sort parameter is being made.
 *
 * Example:
 *  <pre><code>
 *      $this->view->sortControl = new SortBox(
 *          $this->getRequest()->getActionName(),
 *          $columns
 *      );
 *      $this->view->sortControl->applyRequest($this->getRequest());
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
    public function applyRequest($request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Render this SortBox as HTML
     *
     * @return  string
     */
    public function render()
    {
        $form = new Form();
        $form->setTokenDisabled();
        $form->setName($this->name);
        $form->setAttrib('class', 'sort-control inline');

        $form->addElement(
            'select',
            'sort',
            array(
                'autosubmit'    => true,
                'label'         => $this->view()->translate('Sort by'),
                'multiOptions'  => $this->sortFields
            )
        );
        $form->getElement('sort')->setDecorators(array(
            array('ViewHelper'),
            array('Label')
        ));
        $form->addElement(
            'select',
            'dir',
            array(
                'autosubmit'    => true,
                'multiOptions'  => array(
                    'asc'       => 'Asc',
                    'desc'      => 'Desc',
                ),
                'decorators'    => array(
                    array('ViewHelper')
                )
            )
        );

        if ($this->request) {
            $form->populate($this->request->getParams());
        }

        return $form;
    }
}
