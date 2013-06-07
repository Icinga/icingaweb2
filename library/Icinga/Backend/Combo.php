<?php

namespace Icinga\Backend;
use Icinga\Backend;
class Combo extends AbstractBackend
{
    protected $db;
    protected $backends;

    // TODO: Create a dummy query object to also catch errors with lazy connections
    public function from($view, $fields = array())
    {
        $backends = $this->listMyBackends();
        $query = null;
        $msg = '';
        while ($query === null) {
            try {
                $backend_name = array_shift($backends);
                $msg .= "Trying $backend_name";
                $backend = Backend::getInstance($backend_name);
                if ($backend->hasView($view)) {
                    $query = $backend->from($view, $fields);
                }
            } catch (\Exception $e) {
                $msg .= ' Failed: ' . $e->getMessage() . "\n";
            }

            if ($query !== null) $msg .= " Succeeded.\n";

            if ($query === null && empty($backends)) {
               throw new \Exception('All backends failed: ' . nl2br($msg));
            }
        }
        return $query;
    }

    public function hasView($virtual_table)
    {
        $backends = $this->listMyBackends();
        while ($backend_name = array_shift($backends)) {
            if (Backend::getInstance($backend_name)->hasView($virtual_table)) {
                return true;
            }
        }
        return false;
    }

    protected function listMyBackends()
    {
        return preg_split('~,\s*~', $this->config->backends, -1, PREG_SPLIT_NO_EMPTY);
    }
}

