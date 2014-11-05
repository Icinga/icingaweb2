<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Application;

require_once dirname(__FILE__) . '/Web.php';
use Icinga\Exception\ProgrammingError;

class LegacyWeb extends Web
{
    // IcingaWeb 1.x base dir
    protected $legacyBasedir;

    protected function bootstrap()
    {
        parent::bootstrap();
        throw new ProgrammingError('Not yet');
        // $this->setupIcingaLegacyWrapper();
    }

    /**
     * Get the Icinga-Web 1.x base path
     *
     * @throws Exception
     * @return self
     */
    public function getLecacyBasedir()
    {
        return $this->legacyBasedir;
    }
}
