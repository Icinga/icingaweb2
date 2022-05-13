<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

use Icinga\Application\Hook as NewHookImplementation;

/**
 * Icinga Web Hook registry
 *
 * @deprecated It is highly recommended to use {@see Icinga\Application\Hook} instead. Though since this message
 *             (or rather the previous message) hasn't been visible for ages... This won't be removed anyway....
 */
class Hook extends NewHookImplementation
{
}
