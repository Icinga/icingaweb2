<?php
namespace Tests\Icinga\Protocol\Statusdat;
require_once("../../library/Icinga/Protocol/Statusdat/IReader.php");
require_once(realpath("../../library/Icinga/Data/DatasourceInterface.php"));
use Icinga\Data\DatasourceInterface;
use Icinga\Protocol\Statusdat\IReader;

class ReaderMock implements IReader, DatasourceInterface
{
    private $objects;
    private $indices;

    public function __construct(array $objects = array())
    {
        $this->objects = $objects;
    }

    public function getState()
    {
        return $this->objects;
    }

    public function getInternalState()
    {

        return array(
            "objects" => $this->objects,
            "indices" => $this->indices
        );
    }

    public function getObjects()
    {
        return $this->objects;
    }

    public function __call($arg1,$arg2) {
        return $this;
    }

    public function select()
    {
        return $this;
    }

    public function getObjectByName($type, $idx)
    {
        if (isset($this->objects[$type]) && isset($this->objects[$type][$idx]))
            return $this->objects[$type][$idx];
        return null;
    }

}
