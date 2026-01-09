<?php

namespace Icinga\Clicommands;

use DateTime;
use Icinga\Cli\Command;
use Icinga\Common\Database;
use ipl\Sql\Delete;
use ipl\Sql\Select;
use Throwable;

class TwofactorCommand extends Command
{
    use Database;

    /**
     * List all users that have 2FA enabled
     *
     * This command lists all users that have 2FA enabled and when they enabled it.
     *
     * USAGE
     *
     * icingacli twofactor list
     */
    public function listAction(): void
    {
        $rows = $this->getDb()->select(
            (new Select())
                ->from('icingaweb_2fa')
                ->columns(['username', 'ctime'])
        )->fetchAll();

        if (empty($rows)) {
            echo "Currently there are no users that have 2FA enabled.\n";

            return;
        }

        printf("%-20s %-20s\n", 'USER', 'ENABLED 2FA');

        foreach ($rows as $row) {
            printf(
                "%-20s %-20s\n",
                $row->username,
                DateTime::createFromFormat('U.u', $row->ctime / 1000)->format('Y-m-d H:i:s')
            );
        }

        echo "\n";
    }

    /**
     * Disable 2FA for a specific user
     *
     * This command disables 2FA for a specific user. It asks for confirmation before deleting the secret. The deletion
     * cannot be undone.
     *
     * USAGE
     *
     * icingacli twofactor disable [<user>]
     *
     * OPTIONS
     *
     * --force  Disable 2FA for user without confirmation
     */
    public function disableAction(): void
    {
        $user = $this->params->shift();

        if (! $user) {
            fwrite(STDERR, "User must be provided!\n");
            $this->showUsage('disable');

            exit(1);
        }

        if (! $this->params->shift('force')) {
            $input = readline(sprintf(
                "Are you sure you want to disable 2FA for user '%s'? This cannot be undone! [y/N] ",
                $user
            ));

            if (! $input || ! in_array(strtolower(trim($input)), ['y', 'yes'])) {
                echo "No changes made.\n";

                return;
            }
        }

        try {
            $delete = $this->getDb()->prepexec(
                (new Delete())
                    ->from('icingaweb_2fa')
                    ->where(['LOWER(username) = ?' => strtolower($user)])
            );
        } catch (Throwable $e) {
            fprintf(
                STDERR,
                "%s: Failed to disable 2FA for '%s': %s\n",
                $this->screen->colorize('ERROR', 'red'),
                $user,
                $e->getMessage()
            );

            exit(1);
        }

        if ($delete->rowCount() < 1) {
            printf("The user '%s' doesn't have 2FA enabled.\n", $user);

            return;
        }

        printf("Successfully disabled 2FA for user '%s'.\n", $user);
    }
}
