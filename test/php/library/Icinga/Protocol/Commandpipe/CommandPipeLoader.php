<?php
// @codingStandardsIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Protocol\Commandpipe;

require_once("./library/Icinga/LibraryLoader.php");
use Test\Icinga\LibraryLoader;

class CommandPipeLoader extends LibraryLoader {

    public static function requireLibrary()
    {
        require_once("Zend/Config.php");
        require_once("Zend/Log.php");
        require_once("../../library/Icinga/Application/Logger.php");

        require_once("../../library/Icinga/Protocol/Commandpipe/IComment.php");
        require_once("../../library/Icinga/Protocol/Commandpipe/Comment.php");
        require_once("../../library/Icinga/Protocol/Commandpipe/CommandPipe.php");
        require_once("../../library/Icinga/Protocol/Commandpipe/Acknowledgement.php");
        require_once("../../library/Icinga/Protocol/Commandpipe/Downtime.php");
        require_once("../../library/Icinga/Protocol/Commandpipe/PropertyModifier.php");
        require_once("../../library/Icinga/Protocol/Commandpipe/Exception/InvalidCommandException.php");
        require_once("../../library/Icinga/Protocol/Commandpipe/Transport/Transport.php");
        require_once("../../library/Icinga/Protocol/Commandpipe/Transport/SecureShell.php");
        require_once("../../library/Icinga/Protocol/Commandpipe/Transport/LocalPipe.php");
        require_once('../../library/Icinga/Protocol/Commandpipe/CustomNotification.php');
    }
}
// @codingStandardsIgnoreEnd
