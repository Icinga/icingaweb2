<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

namespace Icinga\Data;

use Countable;

interface QueryInterface extends Browsable, Fetchable, Filterable, Limitable, Sortable, Countable {};
