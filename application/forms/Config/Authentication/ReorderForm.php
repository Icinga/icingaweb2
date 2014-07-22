<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Config\Authentication;

use \Zend_Config;
use Icinga\Web\Form;

/**
 * Form for modifying the authentication provider order
 */
class ReorderForm extends Form
{
    /**
     * The name of the current backend which will get action buttons for up and down movement
     *
     * @var string
     */
    protected $backend;

    /**
     * The current ordering of all backends, required to determine possible changes
     *
     * @var array
     */
    protected $currentOrder = array();

    /**
     * Set an array with the current order of all backends
     *
     * @param   array   $order      An array containing backend names in the order
     *                              they are defined in the authentication.ini
     */
    public function setCurrentOrder(array $order)
    {
        $this->currentOrder = $order;
    }

    /**
     * Set the name of the authentication backend for which to create the form
     *
     * @param   string      $backend    The name of the authentication backend
     */
    public function setBackendName($backend)
    {
        $this->backend = $backend;
    }

    /**
     * Return the name of the currently set backend as it will appear in the form
     *
     * @return  string  The name of the backend
     */
    public function getBackendName()
    {
        return $this->filterName($this->backend);
    }

    /**
     * Create this form
     *
     * @see Form::create
     */
    public function create()
    {
        if ($this->moveElementUp($this->backend, $this->currentOrder) !== $this->currentOrder) {
            $upForm = new Form();

            $upForm->addElement(
                'hidden',
                'form_backend_order',
                array(
                    'required'  => true,
                    'value'     => join(',', $this->moveElementUp($this->backend, $this->currentOrder))
                )
            );
            $upForm->addElement(
                'button',
                'btn_' . $this->getBackendName() . '_reorder_up',
                array(
                    'type'      => 'submit',
                    'escape'    => false,
                    'value'     => 'btn_' . $this->getBackendName() . '_reorder_up',
                    'name'      => 'btn_' . $this->getBackendName() . '_reorder_up',
                    'label'     => $this->getView()->icon('up.png', t('Move up in authentication order'))
                )
            );

            $this->addSubForm($upForm, 'btn_reorder_up');
        }

        if ($this->moveElementDown($this->backend, $this->currentOrder) !== $this->currentOrder) {
            $downForm = new Form();

            $downForm->addElement(
                'hidden',
                'form_backend_order',
                array(
                    'required'  => true,
                    'value'     => join(',', $this->moveElementDown($this->backend, $this->currentOrder))
                )
            );
            $downForm->addElement(
                'button',
                'btn_' . $this->getBackendName() . '_reorder_down',
                array(
                    'type'      => 'submit',
                    'escape'    => false,
                    'value'     => 'btn_' . $this->getBackendName() . '_reorder_down',
                    'name'      => 'btn_' . $this->getBackendName() . '_reorder_down',
                    'label'     => $this->getView()->icon('down.png', t('Move down in authentication order'))
                )
            );

            $this->addSubForm($downForm, 'btn_reorder_down');
        }
    }

    /**
     * Return the flattened result of $this->getValues
     *
     * @return  array   The currently set values
     *
     * @see Form::getValues()
     */
    protected function getFlattenedValues()
    {
        $result = array();
        foreach (parent::getValues() as $key => $value) {
            if (is_array($value)) {
                $result += $value;
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Determine whether this form is submitted by testing the submit buttons of both subforms
     *
     * @return  bool    Whether the form has been submitted or not
     */
    public function isSubmitted()
    {
        $checkData = $this->getRequest()->getParams();
        return isset($checkData['btn_' . $this->getBackendName() . '_reorder_up']) ||
                isset($checkData['btn_' . $this->getBackendName() . '_reorder_down']);
    }

    /**
     * Return the reordered configuration after a reorder button has been submitted
     *
     * @param   Zend_Config     $config     The configuration to reorder
     *
     * @return  array                       An array containing the reordered configuration
     */
    public function getReorderedConfig(Zend_Config $config)
    {
        $originalConfig = $config->toArray();
        $newOrder = $this->getFlattenedValues();
        $order = explode(',', $newOrder['form_backend_order']);

        $reordered = array();
        foreach ($order as $key) {
            if (isset($originalConfig[$key])) {
                $reordered[$key] = $originalConfig[$key];
            }
        }

        return $reordered;
    }

    /**
     * Static helper for moving an element in an array one slot up, if possible
     *
     * Example:
     *
     * <pre>
     * $array = array('first', 'second', 'third');
     * moveElementUp('third', $array); // returns ['first', 'third', 'second']
     * </pre>
     *
     * @param   string    $key      The key to bubble up one slot
     * @param   array     $array    The array to work with
     *
     * @return  array               The modified array
     */
    protected static function moveElementUp($key, array $array)
    {
        for ($i = 0; $i < count($array) - 1; $i++) {
            if ($array[$i + 1] === $key) {
                $swap = $array[$i];
                $array[$i] = $array[$i + 1];
                $array[$i + 1] = $swap;
                return $array;
            }
        }

        return $array;
    }

    /**
     * Static helper for moving an element in an array one slot down, if possible
     *
     * Example:
     *
     * <pre>
     * $array = array('first', 'second', 'third');
     * moveElementDown('first', $array); // returns ['second', 'first', 'third']
     * </pre>
     *
     * @param   string    $key      The key to bubble up one slot
     * @param   array     $array    The array to work with
     *
     * @return  array               The modified array
     */
    protected static function moveElementDown($key, array $array)
    {
        for ($i = 0; $i < count($array) - 1; $i++) {
            if ($array[$i] === $key) {
                $swap = $array[$i + 1];
                $array[$i + 1] = $array[$i];
                $array[$i] = $swap;
                return $array;
            }
        }

        return $array;
    }
}
