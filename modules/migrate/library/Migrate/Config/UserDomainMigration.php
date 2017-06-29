<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Migrate\Config;

use Icinga\Application\Config;
use Icinga\Data\Db\DbConnection;
use Icinga\Data\Filter\Filter;
use Icinga\Data\ResourceFactory;
use Icinga\User;
use Icinga\Util\DirectoryIterator;
use Icinga\Util\StringHelper;
use Icinga\Web\Announcement\AnnouncementIniRepository;

class UserDomainMigration
{
    protected $toDomain;

    protected $fromDomain;

    protected $map;

    public static function fromMap(array $map)
    {
        $static = new static();

        $static->map = $map;

        return $static;
    }

    public static function fromDomains($toDomain, $fromDomain = null)
    {
        $static = new static();

        $static->toDomain = $toDomain;
        $static->fromDomain = $fromDomain;

        return $static;
    }

    protected function mustMigrate(User $user)
    {
        if ($user->getUsername() === '*') {
            return false;
        }

        if ($this->map !== null) {
            return isset($this->map[$user->getUsername()]);
        }

        if ($this->fromDomain !== null && $user->hasDomain() && $user->getDomain() !== $this->fromDomain) {
            return false;
        }

        return true;
    }

    protected function migrateUser(User $user)
    {
        $migrated = clone $user;

        if ($this->map !== null) {
            $migrated->setUsername($this->map[$user->getUsername()]);
        } else {
            $migrated->setDomain($this->toDomain);
        }

        return $migrated;
    }

    protected function migrateAnnounces()
    {
        $announces = new AnnouncementIniRepository();

        $query = $announces->select(array('author'));

        if ($this->map !== null) {
            $query->where('author', array_keys($this->map));
        }

        $migratedUsers = array();

        foreach ($announces->select(array('author')) as $announce) {
            $user = new User($announce->author);

            if (! $this->mustMigrate($user)) {
                continue;
            }

            if (isset($migratedUsers[$user->getUsername()])) {
                continue;
            }

            $migrated = $this->migrateUser($user);

            $announces->update(
                'announcement',
                array('author' => $migrated->getUsername()),
                Filter::where('author', $user->getUsername())
            );

            $migratedUsers[$user->getUsername()] = true;
        }
    }

    protected function migrateDashboards()
    {
        $directory = Config::resolvePath('dashboards');

        $migration = array();

        if (DirectoryIterator::isReadable($directory)) {
            foreach (new DirectoryIterator($directory) as $username => $path) {
                $user = new User($username);

                if (! $this->mustMigrate($user)) {
                    continue;
                }

                $migrated = $this->migrateUser($user);

                $migration[$path] = dirname($path) . '/' . $migrated->getUsername();
            }

            foreach ($migration as $from => $to) {
                rename($from, $to);
            }
        }

    }

    protected function migrateNavigation()
    {
        $directory = Config::resolvePath('navigation');

        foreach (new DirectoryIterator($directory, 'ini') as $file) {
            $config = Config::fromIni($file);

            foreach ($config as $navigation) {
                $owner = $navigation->owner;

                if (! empty($owner)) {
                    $user = new User($owner);

                    if ($this->mustMigrate($user)) {
                        $migrated = $this->migrateUser($user);

                        $navigation->owner = $migrated->getUsername();
                    }
                }

                $users = $navigation->users;

                if (! empty($users)) {
                    $users = StringHelper::trimSplit($users);

                    foreach ($users as &$username) {
                        $user = new User($username);

                        if (! $this->mustMigrate($user)) {
                            continue;
                        }

                        $migrated = $this->migrateUser($user);

                        $username = $migrated->getUsername();
                    }

                    $navigation->users = implode(',', $users);
                }
            }

            $config->saveIni();
        }
    }

    protected function migratePreferences()
    {
        $config = Config::app();

        $type = $config->get('global', 'config_backend', 'ini');

        switch ($type) {
            case 'ini':
                $directory = Config::resolvePath('preferences');

                $migration = array();

                if (DirectoryIterator::isReadable($directory)) {
                    foreach (new DirectoryIterator($directory) as $username => $path) {
                        $user = new User($username);

                        if (! $this->mustMigrate($user)) {
                            continue;
                        }

                        $migrated = $this->migrateUser($user);

                        $migration[$path] = dirname($path) . '/' . $migrated->getUsername();
                    }

                    foreach ($migration as $from => $to) {
                        rename($from, $to);
                    }
                }

                break;
            case 'db':
                /** @var DbConnection $conn */
                $conn = ResourceFactory::create($config->get('global', 'config_resource'));

                $query = $conn
                    ->select()
                    ->from('icingaweb_user_preference', array('username'))
                    ->group('username');

                if ($this->map !== null) {
                    $query->applyFilter(Filter::matchAny(Filter::where('username', array_keys($this->map))));
                }

                $users = $query->fetchColumn();

                $migration = array();

                foreach ($users as $username) {
                    $user = new User($username);

                    if (! $this->mustMigrate($user)) {
                        continue;
                    }

                    $migrated = $this->migrateUser($user);

                    $migration[$username] = $migrated->getUsername();
                }

                if (! empty($migration)) {
                    $conn->getDbAdapter()->beginTransaction();

                    foreach ($migration as $originalUsername => $username) {
                        $conn->update(
                            'icingaweb_user_preference',
                            array('username' => $username),
                            Filter::where('username', $originalUsername)
                        );
                    }

                    $conn->getDbAdapter()->commit();
                }
        }
    }

    protected function migrateRoles()
    {
        $roles = Config::app('roles');

        foreach ($roles as $role) {
            $users = $role->users;

            if (empty($users)) {
                continue;
            }

            $users = StringHelper::trimSplit($users);

            foreach ($users as &$username) {
                $user = new User($username);

                if (! $this->mustMigrate($user)) {
                    continue;
                }

                $migrated = $this->migrateUser($user);

                $username = $migrated->getUsername();
            }

            $role->users = implode(',', $users);
        }

        $roles->saveIni();
    }

    protected function migrateUsers()
    {
        foreach (Config::app('authentication') as $name => $config) {
            if (strtolower($config->backend) !== 'db') {
                continue;
            }

            /** @var DbConnection $conn */
            $conn = ResourceFactory::create($config->resource);

            $query = $conn
                ->select()
                ->from('icingaweb_user', array('name'))
                ->group('name');

            if ($this->map !== null) {
                $query->applyFilter(Filter::matchAny(Filter::where('name', array_keys($this->map))));
            }

            $users = $query->fetchColumn();

            $migration = array();

            foreach ($users as $username) {
                $user = new User($username);

                if (! $this->mustMigrate($user)) {
                    continue;
                }

                $migrated = $this->migrateUser($user);

                $migration[$username] = $migrated->getUsername();
            }

            if (! empty($migration)) {
                $conn->getDbAdapter()->beginTransaction();

                foreach ($migration as $originalUsername => $username) {
                    $conn->update(
                        'icingaweb_user',
                        array('name' => $username),
                        Filter::where('name', $originalUsername)
                    );
                }

                $conn->getDbAdapter()->commit();
            }
        }

        foreach (Config::app('groups') as $name => $config) {
            if (strtolower($config->backend) !== 'db') {
                continue;
            }

            /** @var DbConnection $conn */
            $conn = ResourceFactory::create($config->resource);

            $query = $conn
                ->select()
                ->from('icingaweb_group_membership', array('username'))
                ->group('username');

            if ($this->map !== null) {
                $query->applyFilter(Filter::matchAny(Filter::where('username', array_keys($this->map))));
            }

            $users = $query->fetchColumn();

            $migration = array();

            foreach ($users as $username) {
                $user = new User($username);

                if (! $this->mustMigrate($user)) {
                    continue;
                }

                $migrated = $this->migrateUser($user);

                $migration[$username] = $migrated->getUsername();
            }

            if (! empty($migration)) {
                $conn->getDbAdapter()->beginTransaction();

                foreach ($migration as $originalUsername => $username) {
                    $conn->update(
                        'icingaweb_group_membership',
                        array('username' => $username),
                        Filter::where('username', $originalUsername)
                    );
                }

                $conn->getDbAdapter()->commit();
            }
        }
    }

    public function migrate()
    {
        $this->migrateAnnounces();
        $this->migrateDashboards();
        $this->migrateNavigation();
        $this->migratePreferences();
        $this->migrateRoles();
        $this->migrateUsers();
    }
}
