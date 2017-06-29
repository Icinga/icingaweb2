<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Migrate\Clicommands;

use Icinga\Cli\Command;
use Icinga\Module\Migrate\Config\UserDomainMigration;
use Icinga\User;
use Icinga\Util\StringHelper;

class ConfigCommand extends Command
{
    /**
     * Rename users and user configurations according to a given domain
     *
     * The following configurations are taken into account:
     *      - Announcements
     *      - Preferences
     *      - Dashboards
     *      - Custom navigation items
     *      - Role configuration
     *      - Users and group memberships in database backends, if configured
     *
     * USAGE:
     *
     *  icingacli migrate config users [options]
     *
     * OPTIONS:
     *
     *  --to-domain=<to-domain>         The new domain for the users
     *
     *  --from-domain=<from-domain>     Migrate only the users with the given domain.
     *                                  Use this switch in combination with --to-domain.
     *
     *  --user=<user>                   Migrate only the given user in the format <user> or <user@domain>
     *
     *  --map-file=<mapfile>            File to use for renaming users
     *
     *  --separator=<separator>         Separator for the map file
     *
     * EXAMPLES:
     *
     *  icingacli migrate config users ...
     *
     *  Add the domain "icinga.com" to all users:
     *
     *      --to-domain icinga.com
     *
     *  Set the domain "example.com" on all users that have the domain "icinga.com":
     *
     *      --to-domain example.com --from-domain icinga.com
     *
     *  Set the domain "icinga.com" on the user "icingaadmin":
     *
     *      --to-domain icinga.com --user icingaadmin
     *
     *  Set the domain "icinga.com" on the users "icingaadmin@icinga.com"
     *
     *      --to-domain example.com --user icingaadmin@icinga.com
     *
     *  Rename users according to a map file:
     *
     *      --map-file /path/to/mapfile --separator :
     *
     * MAPFILE:
     *
     *  You may rename users according to a given map file. The map file must be separated by newlines. Each line then
     *  is specified in the format <from><separator><to>. The separator is specified with the --separator switch.
     *
     *  Example content:
     *
     *      icingaadmin:icingaadmin@icinga.com
     *      jdoe@example.com:jdoe@icinga.com
     *      rroe@icinga:rroe@icinga.com
     */
    public function usersAction()
    {
        if ($this->params->has('map-file')) {
            $mapFile = $this->params->get('map-file');
            $separator = $this->params->getRequired('separator');

            $source = trim(file_get_contents($mapFile));
            $source = StringHelper::trimSplit($source, "\n");

            $map = array();

            array_walk($source, function ($item) use ($separator, &$map) {
                list($from, $to) = StringHelper::trimSplit($item, $separator, 2);
                $map[$from] = $to;
            });

            $migration = UserDomainMigration::fromMap($map);
        } else {
            $toDomain = $this->params->getRequired('to-domain');
            $fromDomain = $this->params->get('from-domain');
            $user = $this->params->get('user');

            if ($user === null) {
                $migration = UserDomainMigration::fromDomains($toDomain, $fromDomain);
            } else {
                if ($fromDomain !== null) {
                    $this->fail(
                        "Ambiguous arguments: Can't use --user in combination with --from-domain."
                        . " Please use the user@domain syntax for the --user switch instead."
                    );
                }

                $user = new User($user);

                $migrated = clone $user;
                $migrated->setDomain($toDomain);

                $migration = UserDomainMigration::fromMap(array($user->getUsername() => $migrated->getUsername()));
            }
        }

        $migration->migrate();
    }
}
