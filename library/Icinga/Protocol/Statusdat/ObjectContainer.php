<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Protocol\Statusdat;

/**
 * Class ObjectContainer
 * @package Icinga\Protocol\Statusdat
 */
class ObjectContainer extends \stdClass
{
    /**
     * @var \stdClass
     */
    public $ref;

    /**
     * @var IReader
     */
    public $reader;

    /**
     * @param \stdClass $obj
     * @param IReader $reader
     */
    public function __construct(\stdClass &$obj, IReader &$reader)
    {
        $this->ref = & $obj;
        $this->reader = & $reader;
    }

    /**
     * @param $attribute
     * @return \stdClass
     */
    public function __get($attribute)
    {
        $exploded = explode(".", $attribute);
        $result = $this->ref;
        foreach ($exploded as $elem) {

            $result = $result->$elem;
        }
        return $result;
    }
}
