<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Data;

use Countable;

interface QueryInterface extends Browsable, Fetchable, Filterable, Limitable, Sortable, Countable {};
