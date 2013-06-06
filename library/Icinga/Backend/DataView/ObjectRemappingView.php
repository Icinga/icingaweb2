<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Backend\DataView;

/**
 * Class ObjectRemappingView
 *
 * Dataview that maps generic field names to storage specific fields or requests them via handlers.
 *
 * When accessing objects, every storage api returns them with other names. You can't simply say
 * $object->service_state, because this field is, e.g. under status.current_state in the status.dat
 * view, while IDO uses servicestate->current_state.
 *
 * This view is intended for normalizing these changes, so a request of service_state returns the
 * right field for the backend. When implementing it, you have to fill the mappedParameters and/or
 * the handlerParameters array. While mappedParameters simply translate logic field names to
 * storage specific ones, handlerParameters determins functions that handle data retrieval for
 * the specific fields.
 *
 * @package Icinga\Backend\DataView
 */
class ObjectRemappingView implements AbstractAccessorStrategy
{

    /**
     * When implementing your own Mapper, this contains the static mapping rules.
     * @see Icinga\Backend\Statusdat\DataView\StatusdatServiceView for an example
     *
     * @var array
     */
    protected $mappedParameters = array();

    /**
     * When implementing your own Mapper, this contains the handler for specific fields and allows you to lazy load
     * different fields if necessary. The methods are strings that will be mapped to methods of this class
     *
     * @see Icinga\Backend\Statusdat\DataView\StatusdatServiceView for an example
     *
     * @var array
     */
    protected $handlerParameters = array();

    /**
     *
     * @see Icinga\Backend\DataView\AbstractAccessorStrategy
     *
     * @param The $item
     * @param The $field
     * @return The|string
     * @throws \InvalidArgumentException
     */
    public function get(&$item, $field)
    {

        if (isset($item->$field)) {
            return $item->$field;
        }
        if (isset($this->mappedParameters[$field])) {
            $mapped = explode(".", $this->mappedParameters[$field]);
            $res = $item;

            foreach ($mapped as $map) {
                if (!isset($res->$map)) {
                    return "";
                }
                $res = $res->$map;
            }
            return $res;
        }

        if (isset($this->handlerParameters[$field])) {
            $hdl = $this->handlerParameters[$field];
            return $this->$hdl($item);
        }
        throw new \InvalidArgumentException("Field $field does not exist for status.dat services");
    }

    /**
     *
     * @see Icinga\Backend\DataView\AbstractAccessorStrategy
     *
     * @param The $field
     * @return The|string
     */
    public function getNormalizedFieldName($field)
    {
        if (isset($this->mappedParameters[$field])) {
            return $this->mappedParameters[$field];
        }
        return $field;
    }

    /**
     *
     * @see Icinga\Backend\DataView\AbstractAccessorStrategy
     *
     * @param The $item
     * @param The $field
     * @return bool
     */
    public function exists(&$item, $field)
    {
        return (isset($item->$field)
            || isset($this->mappedParameters[$field])
            || isset($this->handlerParameters[$field])
        );
    }
}
