<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Protocol\Statusdat;

use Icinga\Util\File;
use Icinga\Exception\ProgrammingError;
use Icinga\Protocol\Statusdat\Exception\ParsingException as ParsingException;

/**
 * Status.dat and object.cache parser implementation
 */
class Parser
{
    /**
     * An array of objects that couldn't be resolved yet due to missing dependencies
     *
     * @var array
     */
    private $deferred = array();

    /**
     * The currently read file
     *
     * @var File
     */
    private $file;

    /**
     * String representation of the currently parsed object type
     *
     * @var string
     */
    private $currentObjectType;

    /**
     * The current state type (host, service)
     *
     * @var string
     */
    private $currentStateType;

    /**
     * The internal representation of the icinga statue
     *
     * @var array
     */
    private $icingaState;

    /**
     * The current line being read
     *
     * @var int
     */
    private $lineCtr = 0;

    /**
     * Create a new parser using the given file
     *
     * @param   File    $file       The file to parse
     * @param   array   $baseState  The state to use for the base
     */
    public function __construct(File $file, $baseState = null)
    {
        $this->file = $file;
        $this->icingaState = $baseState;
    }

    /**
     * Parse the given file handle as an objects file and read object information
     */
    public function parseObjectsFile()
    {
        $DEFINE = strlen("define ");
        $this->icingaState = array();
        foreach ($this->file as $line) {
            $line = trim($line);
            $this->lineCtr++;
            if ($line === "" || $line[0] === "#") {
                continue;
            }
            $this->currentObjectType = trim(substr($line, $DEFINE, -1));
            if (!isset($this->icingaState[$this->currentObjectType])) {
                $this->icingaState[$this->currentObjectType] = array();
            }
            $this->readCurrentObject();
        }
        $this->processDeferred();
    }

    /**
     * Parse the given file as an status.dat file and read runtime information
     *
     * @param   File    $file   The file to parse or null to parse the one passed to the constructor
     */
    public function parseRuntimeState(File $file = null)
    {
        if ($file != null) {
            $this->file = $file;
        } else {
            $file = $this->file;
        }

        if (!$this->icingaState) {
            throw new ProgrammingError("Tried to read runtime state without existing objects data");
        }
        $this->overwrites = array();
        foreach ($file as $line) {
            $line = trim($line);
            $this->lineCtr++;
            if ($line === "" || $line[0] === "#") {
                continue;
            }
            $this->currentStateType = trim(substr($line, 0, -1));
            $this->readCurrentState();
        }
    }

    /**
     * Read the next object from the object.cache file handle
     *
     * @throws ParsingException
     */
    private function readCurrentObject()
    {
        $monitoringObject = new PrintableObject();
        foreach ($this->file as $line) {
            $line = explode("\t", trim($line), 2);
            $this->lineCtr++;
            if (!$line) {
                continue;
            }

            // End of object
            if ($line[0] === "}") {
                $this->registerObject($monitoringObject);
                return;
            }
            if (!isset($line[1])) {
                $line[1] = "";
            }
            $monitoringObject->{$line[0]} = trim($line[1]);
        }
        throw new ParsingException("Unexpected EOF in objects.cache, line " . $this->lineCtr);
    }

    /**
     * Read the next state from the status.dat file handler
     *
     * @throws Exception\ParsingException
     */
    private function readCurrentState()
    {
        $statusdatObject = new RuntimeStateContainer();

        $objectType = $this->getObjectTypeForState();

        if ($objectType != "host" && $objectType != "service") {
            $this->skipObject(); // ignore unknown objects
            return;
        }
        if (!isset($this->icingaState[$this->currentObjectType])) {
            throw new ParsingException("No $this->currentObjectType objects registered in objects.cache");
        }
        $base = & $this->icingaState[$this->currentObjectType];
        $state = $this->skipObject(true);
        $statusdatObject->runtimeState = & $state;
        $name = $this->getObjectIdentifier($statusdatObject);

        if (!isset($base[$name])) {
            throw new ParsingException(
                "Unknown object $name " . $this->currentObjectType . " - "
                . print_r(
                    $statusdatObject,
                    true
                )
                . "\n" . print_r($base, true)
            );
        }
        $type = substr($this->currentStateType, strlen($objectType));

        if ($type == "status") {
            // directly set the status to the status field of the given object
            $base[$name]->status = & $statusdatObject;
        } else {
            if (!isset($base[$name]->$type) || !in_array($base[$name]->$type, $this->overwrites)) {
                $base[$name]->$type = array();
                $this->overwrites[] = & $base[$name]->$type;
            }
            array_push($base[$name]->$type, $statusdatObject);
            $this->currentObjectType = $type;
            if (!isset($this->icingaState[$type])) {
                $this->icingaState[$type] = array();
            }
            $this->icingaState[$type][] = &$statusdatObject;
            $id = $this->getObjectIdentifier($statusdatObject);
            if ($id !== false && isset($this->icingaState[$objectType][$id])) {
                $statusdatObject->$objectType = $this->icingaState[$objectType][$id];
            }
        }

        return;

    }

    /**
     * Get the corresponding object type name for the given state
     *
     * @return string
     */
    private function getObjectTypeForState()
    {
        $pos = strpos($this->currentStateType, "service");

        if ($pos === false) {
            $pos = strpos($this->currentStateType, "host");
        } else {
            $this->currentObjectType = "service";
            return "service";
        }

        if ($pos === false) {
            return $this->currentStateType;
        } else {
            $this->currentObjectType = "host";
            return "host";
        }

        return $this->currentObjectType;
    }

    /**
     * Skip the current object definition
     *
     * @param bool $returnString        If true, the object string will be returned
     * @return string                   The skipped object if $returnString is true
     */
    protected function skipObject($returnString = false)
    {
        if (!$returnString) {
            while (trim($this->file->fgets()) !== "}") {
            }
            return null;
        } else {
            $str = "";
            while (($val = trim($this->file->fgets())) !== "}") {
                $str .= $val . "\n";
            }
            return $str;
        }
    }

    /**
     * Register the given object in the icinga state
     *
     * @param object $object        The monitoring object to register
     */
    protected function registerObject(&$object)
    {

        $name = $this->getObjectIdentifier($object);
        if ($name !== false) {
            $this->icingaState[$this->currentObjectType][$name] = &$object;
        }
        $this->registerObjectAsProperty($object);
    }

    /**
     * Register the given object as a property in related objects
     *
     * This registers for example hosts underneath their hostgroup and vice cersa
     *
     * @param object $object        The object to register as a property
     */
    protected function registerObjectAsProperty(&$object)
    {
        if ($this->currentObjectType == 'service'
            || $this->currentObjectType == 'host'
            || $this->currentObjectType == 'contact') {
            return null;
        }
        $isService = strpos($this->currentObjectType, "service") !== false;
        $isHost = strpos($this->currentObjectType, "host") !== false;
        $isContact = strpos($this->currentObjectType, "contact") !== false;
        $name = $this->getObjectIdentifier($object);

        if ($isService === false && $isHost === false && $isContact === false) {
            // this would be error in the parser implementation
            return null;
        }
        $property = $this->currentObjectType;
        if ($isService) {
            $this->currentObjectType = "service";
            $property = substr($property, strlen("service"));
        } elseif ($isHost) {
            $this->currentObjectType = "host";
            $property = substr($property, strlen("host"));
        } elseif ($isContact) {
            $this->currentObjectType = "contact";
            $property = substr($property, strlen("contact"));
        }

        if (!isset($this->icingaState[$this->currentObjectType])) {
            return $this->deferRegistration($object, $this->currentObjectType . $property);
        }

        // @TODO: Clean up, this differates between 1:n and 1:1 references
        if (strpos($property, "group") !== false) {
            $sourceIdentifier = $this->getMembers($object);
            foreach ($sourceIdentifier as $id) {
                $source = $this->icingaState[$this->currentObjectType][$id];
                if (!isset($source->$property)) {
                    $source->$property = array();
                }
                $type = $this->currentObjectType;
                if (!isset($object->$type)) {
                    $object->$type = array();
                }
                // add the member to the group object
                array_push($object->$type, $source);
                // add the group to the member object
                array_push($source->$property, $name);
            }
        } else {
            $source = $this->icingaState[$this->currentObjectType][$this->getObjectIdentifier($object)];
            if (!isset($source->$property)) {
                $source->$property = array();
            }

            array_push($source->$property, $object);
        }

        return null;
    }

    /**
     * Defer registration of the given object
     *
     * @param object $object        The object to defer
     * @param String $objType       The name of the object type
     */
    protected function deferRegistration($object, $objType)
    {
        $this->deferred[] = array($object, $objType);
    }

    /**
     * Process deferred objects
     */
    protected function processDeferred()
    {
        foreach ($this->deferred as $obj) {
            $this->currentObjectType = $obj[1];
            $this->registerObjectAsProperty($obj[0]);
        }
    }

    /**
     * Return the resolved members directive of an object
     *
     * @param  object $object       The object to get the members from
     * @return array                An array of member names
     */
    protected function getMembers(&$object)
    {
        if (!isset($object->members)) {
            return array();
        }

        $members = explode(",", $object->members);

        if ($this->currentObjectType == "service") {
            $res = array();
            for ($i = 0; $i < count($members); $i += 2) {
                $res[] = $members[$i] . ";" . $members[$i + 1];
            }
            return $res;
        } else {
            return $members;
        }

    }

    /**
     * Return the unique name of the given object
     *
     * @param  object $object   The object to retrieve the name from
     * @return string           The name of the object or null if no name can be retrieved
     */
    protected function getObjectIdentifier(&$object)
    {
        if ($this->currentObjectType == 'contact') {
            return $object->contact_name;
        }

        if ($this->currentObjectType == "service") {
            return $object->host_name . ";" . $object->service_description;
        }
        $name = $this->currentObjectType . "_name";
        if (isset($object->{$name})) {
            return $object->{$name};
        }
        if (isset($object->service_description)) {
            return $object->host_name . ";" . $object->service_description;
        } elseif (isset($object->host_name)) {
            return $object->host_name;
        }
        return null;

    }

    /**
     * Return the internal state of the parser
     *
     * @return null
     */
    public function getRuntimeState()
    {
        return $this->icingaState;
    }
}
