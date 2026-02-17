<?php
/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2 */

namespace Icinga\Data;

use Exception;
use Icinga\Application\Logger;
use Icinga\Authentication\User\DomainAwareInterface;
use Icinga\Authentication\UserGroup\UserGroupBackendInterface;
use Icinga\User;
use Icinga\Web\Notification;
use Icinga\Data\Filter\Filter;
use ipl\Web\Control\SimpleSuggestions;

class UserSuggestions extends SimpleSuggestions
{
    /** @var array User backends */
    protected $userBackends;

    /** @var string The name of user group */
    protected $userGroupName;

    /** @var UserGroupBackendInterface The user group backend */
    protected $userGroupBackend;

    /**
     * Set User group backend
     *
     * @param UserGroupBackendInterface $userGroupBackend
     *
     * @return $this
     */
    public function setUserGroupBackend(UserGroupBackendInterface $userGroupBackend): self
    {
        $this->userGroupBackend = $userGroupBackend;

        return $this;
    }

    /**
     * Get User group backend
     *
     * @return UserGroupBackendInterface
     */
    public function getUserGroupBackend(): UserGroupBackendInterface
    {
        return $this->userGroupBackend;
    }

    /**
     * Set User group name
     *
     * @param string $userGroupName
     *
     * @return $this
     */
    public function setUserGroupName(string $userGroupName): self
    {
        $this->userGroupName = $userGroupName;

        return $this;
    }

    /**
     * Get User group name
     *
     * @return string
     */
    public function getUserGroupName(): string
    {
        return $this->userGroupName;
    }

    /**
     * Get user backends
     *
     * @return array
     */
    public function getUserBackends(): array
    {
        return $this->userBackends;
    }

    /**
     * Set user backends
     *
     * @param array $userBackends
     *
     * @return $this
     */
    public function setUserBackends(array $userBackends): self
    {
        $this->userBackends = $userBackends;

        return $this;
    }

    protected function fetchSuggestions(string $searchTerm, array $exclude = [])
    {
        $filter = $this->prepareFilter($searchTerm, $exclude);
        $suggestions = [];
        foreach ($this->getUserBackends() as $userBackend) {
            try {
                if ($userBackend instanceof DomainAwareInterface) {
                    $domain = $userBackend->getDomain();
                } else {
                    $domain = null;
                }

                $users = $userBackend->select(['user_name'])
                    ->limit(self::DEFAULT_LIMIT)
                    ->applyFilter($filter)
                    ->fetchColumn();

                foreach ($users as $userName) {
                    $userObj = (new User($userName))->setDomain($domain);
                    $suggestions[] = $userObj->getUsername();
                }
            } catch (Exception $e) {
                Logger::error($e);
                Notification::warning(sprintf(
                    t('Failed to fetch any users from backend %s. Please check your log'),
                    $userBackend->getName()
                ));
            }
        }

        return array_unique($suggestions);
    }

    /**
     * Prepare the filter for db query
     *
     * @param string $searchTerm
     * @param array $exclude
     *
     * @return Filter
     */
    private function prepareFilter(string $searchTerm, array $exclude = []): Filter
    {
        $filter = Filter::where('user_name', $searchTerm);

        $members = $this->getUserGroupBackend()
            ->select()
            ->from('group_membership', ['user_name'])
            ->where('group_name', $this->getUserGroupName())
            ->fetchColumn();

        $members = array_merge($members, $exclude);

        if (! empty($members)) {
            $filter->andFilter(
                Filter::not(Filter::where('user_name', $members))
            );
        }

        return $filter;
    }
}
