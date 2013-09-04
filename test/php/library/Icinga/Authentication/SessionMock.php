<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Authentication;

require_once("../../library/Icinga/Authentication/Session.php");

use Icinga\Authentication\Session as Session;

class SessionMock extends Session
{
    public $isOpen = false;
    public $isWritten = false;

    public function open()
    {
        if (!$this->isOpen && $this->isWritten) {
            throw new \Exception("Session write after close");
        }
        $this->isOpen = true;
    }

    public function read($keepOpen = false)
    {
        $this->open();
        if (!$keepOpen) {
            $this->close();
        }
    }

    public function write($keepOpen = false)
    {
        $this->open();
        if (!$keepOpen) {
            $this->close();
        }
    }

    public function close()
    {
        $this->isOpen = false;
        $this->isWritten = true;
    }

    public function purge()
    {
    }
}
