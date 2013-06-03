<?php

namespace Icinga\Protocol\Statusdat;
use Icinga\Protocol\Statusdat\Exception\ParsingException as ParsingException;

class Parser 
{
    private $deferred   = array();
    private $filehandle = null;
    private $currentObjectType = null;
    private $currentStateType = null;
    private $icingaState = null;
    private $lineCtr = 0;


    public function __construct($filehandle = null, $baseState = null)
    {
        if (!is_resource($filehandle))
            throw new  \Icinga\Exception\ConfigurationError("Statusdat parser can't find $filehandle");

        $this->filehandle = $filehandle;
        $this->icingaState = $baseState;
    }
    
    public function parseObjectsFile()
    {
        \Icinga\Application\Logger::debug("Reading new objects file");
        $DEFINE = strlen("define ");
        $filehandle = $this->filehandle;
        $this->icingaState = array();
        while (!feof($filehandle)) {

            $line = trim(fgets($filehandle));

            $this->lineCtr++;
            if ($line === "" || $line[0] === "#")
                continue;
            $this->currentObjectType = trim(substr($line,$DEFINE,-1));
            if (!isset($this->icingaState[$this->currentObjectType])) {
                $this->icingaState[$this->currentObjectType] = array();
            }
            $this->readCurrentObject();
        }
        $this->processDeferred();
    }

    public function parseRuntimeState($filehandle = null)
    {
        if($filehandle != null)
            $this->filehandle = $filehandle;
        else
            $filehandle = $this->filehandle;

        if(!$this->icingaState)
            throw new \Icinga\Exception\ProgrammingError("Tried to read runtime state without existing objects data");
        $this->overwrites = array();
        while (!feof($filehandle)) {

            $line = trim(fgets($filehandle));

            $this->lineCtr++;
            if ($line === "" || $line[0] === "#")
                continue;

            $this->currentStateType = trim(substr($line,0,-1));
            $this->readCurrentState();
        }

    }
    
    private function readCurrentObject()
    {
        $filehandle = $this->filehandle;
        $monitoringObject = new \stdClass();
        while (!feof($filehandle)) {
            $line = explode("\t",trim(fgets($filehandle)),2);
            $this->lineCtr++;
            if (!$line)
                continue;

            // End of object
            if ($line[0] === "}") {
                $this->registerObject($monitoringObject);
                return;
            }
            if(!isset($line[1]))
                $line[1] = "";
            $monitoringObject->{$line[0]} = trim($line[1]);
        }
        throw new ParsingException("Unexpected EOF in objects.cache, line ".$this->lineCtr);
    }

    /**
     * TODO: Discard old runtime data
     * @throws Exception\ParsingException
     */
    private function readCurrentState()
    {
        $filehandle = $this->filehandle;
        $statusdatObject = new RuntimeStateContainer();

        $objectType = $this->getObjectTypeForState();

        if($objectType != "host" && $objectType != "service") {
            $this->skipObject(); // ignore unknown objects
            return;
        }
        if(!isset($this->icingaState[$this->currentObjectType]))
            throw new ParsingException("No $this->currentObjectType objects registered in objects.cache");
        $base = &$this->icingaState[$this->currentObjectType];
        $state = &$this->skipObject(true);
        $statusdatObject->runtimeState = &$state;
        $name = $this->getObjectIdentifier($statusdatObject);

        if(!isset($base[$name]))
            throw new ParsingException("Unknown object $name ".$this->currentObjectType." - ".print_r($statusdatObject,true)."\n".print_r($base,true));
        $type = substr($this->currentStateType,strlen($objectType));

        if($type == "status") {
            $base[$name]->status = &$statusdatObject;
        } else {
            if(!isset($base[$name]->$type) || !in_array($base[$name]->$type,$this->overwrites)) {
                $base[$name]->$type = array();
                $this->overwrites[] = &$base[$name]->$type;
            }
            array_push($base[$name]->$type,$statusdatObject);
        }
        return;

    }

    private function getObjectTypeForState()
    {
        $pos = strpos($this->currentStateType,"service");

        if($pos === False) {
            $pos = strpos($this->currentStateType,"host");
        } else {
            $this->currentObjectType = "service";
            return "service";
        }

        if($pos === False)
            return $this->currentStateType;
        else {
            $this->currentObjectType = "host";
            return "host";
        }

        return $this->currentObjectType;
    }

    protected function skipObject($returnString = false)
    {
        if(!$returnString) {
            while(trim(fgets($this->filehandle)) !== "}") {
            }
            return;
        } else {
            $str = "";
            while(($val = trim(fgets($this->filehandle))) !== "}") {
                $str .= $val."\n";
            }
            return $str;
        }
    }

    protected function registerObject(&$object) {

        $name = $this->getObjectIdentifier($object);

        if($name !== false) {
            $this->icingaState[$this->currentObjectType][$name] = &$object;
        }
        $this->registerObjectAsProperty($object);
    }

    protected function registerObjectAsProperty(&$object)
    {
        if($this->currentObjectType == "service" || $this->currentObjectType == "host") {
            return;
        }
        $isService = strpos($this->currentObjectType,"service") !== False;
        $isHost = strpos($this->currentObjectType,"host") !== False;

        $name = $this->getObjectIdentifier($object);
        if($isService === false && $isHost === false) // this would be error in the parser implementation
            return;
        $property = $this->currentObjectType;
        if($isService) {
            $this->currentObjectType = "service";
            $property = substr($property,strlen("service"));
        } else {
            $this->currentObjectType = "host";
            $property = substr($property,strlen("host"));
        }
        if(!isset($this->icingaState[$this->currentObjectType]))
            return $this->deferRegistration($object,$this->currentObjectType.$property);

        // @TODO: Clean up, this differates between 1:n and 1:1 references
        if(strpos($property ,"group") !== False) {
            $sourceIdentifier = $this->getMembers($object);
            foreach($sourceIdentifier as $id) {
                $source = $this->icingaState[$this->currentObjectType][$id];
                if(!isset($source->$property))
                    $source->$property = array();
                array_push($source->$property, $name);
            }
        } else {
            $source = $this->icingaState[$this->currentObjectType][$this->getObjectIdentifier($object)];
            if(!isset($source->$property))
                $source->$property = array();
            array_push($source->$property, $object);
        }
    }

    protected function deferRegistration($object,$objType)
    {
        $this->deferred[] = array($object,$objType);
    }

    protected function processDeferred() {
        foreach($this->deferred as $obj) {
            $this->currentObjectType = $obj[1];
            $this->registerObjectAsProperty($obj[0]);
        }
    }

    protected function getMembers(&$object)
    {
        $members = explode(",",$object->members);
        if($this->currentObjectType == "service") {
            $res = array();
            for($i=0;$i<count($members);$i+=2) {
                $res[] = $members[$i].";".$members[$i+1];
            }
            return $res;
        } else {
            return $members;
        }

    }

    protected function getObjectIdentifier(&$object)
    {
        if ($this->currentObjectType == "service") {
            return $object->host_name.";".$object->service_description;
        }
        $name = $this->currentObjectType."_name";
        if(isset($object->{$name}))
            return $object->{$name};
        return false;


    }



    public function getRuntimeState()
    {
        return $this->icingaState;
    }

}