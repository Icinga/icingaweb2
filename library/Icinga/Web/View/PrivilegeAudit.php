<?php
/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\View;

use Icinga\Authentication\Role;
use Icinga\Forms\Security\RoleForm;
use Icinga\Util\StringHelper;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\HtmlString;
use ipl\Stdlib\Filter;
use ipl\Web\Common\BaseTarget;
use ipl\Web\Filter\QueryString;
use ipl\Web\Url;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;

class PrivilegeAudit extends BaseHtmlElement
{
    use BaseTarget;

    /** @var string */
    const UNRESTRICTED_PERMISSION = 'unrestricted';

    protected $tag = 'ul';

    protected $defaultAttributes = ['class' => 'privilege-audit'];

    /** @var Role[] */
    protected $roles;

    public function __construct(array $roles)
    {
        $this->roles = $roles;
        $this->setBaseTarget('_next');
    }

    protected function auditPermission($permission)
    {
        $grantedBy = [];
        $refusedBy = [];
        foreach ($this->roles as $role) {
            if ($permission === self::UNRESTRICTED_PERMISSION) {
                if ($role->isUnrestricted()) {
                    $grantedBy[] = $role->getName();
                }
            } elseif ($role->denies($permission)) {
                $refusedBy[] = $role->getName();
            } elseif ($role->grants($permission, false, false)) {
                $grantedBy[] = $role->getName();
            }
        }

        $header = new HtmlElement('div');
        if (! empty($refusedBy)) {
            $header->add([
                new Icon('times-circle', ['class' => 'refused']),
                count($refusedBy) > 2
                    ? sprintf(
                        tp(
                            'Refused by %s and %s as well as one other',
                            'Refused by %s and %s as well as %d others',
                            count($refusedBy) - 2
                        ),
                        $refusedBy[0],
                        $refusedBy[1],
                        count($refusedBy) - 2
                    )
                    : sprintf(
                        tp('Refused by %s', 'Refused by %s and %s', count($refusedBy)),
                        ...$refusedBy
                    )
            ]);
        } elseif (! empty($grantedBy)) {
            $header->add([
                new Icon('check-circle', ['class' => 'granted']),
                count($grantedBy) > 2
                    ? sprintf(
                        tp(
                            'Granted by %s and %s as well as one other',
                            'Granted by %s and %s as well as %d others',
                            count($grantedBy) - 2
                        ),
                        $grantedBy[0],
                        $grantedBy[1],
                        count($grantedBy) - 2
                    )
                    : sprintf(
                        tp('Granted by %s', 'Granted by %s and %s', count($grantedBy)),
                        ...$grantedBy
                    )
             ]);
        } else {
            $header->add([new Icon('minus-circle'), t('Not granted or refused by any role')]);
        }

        $vClass = null;
        $rolePaths = [];
        foreach (array_reverse($this->roles) as $role) {
            if (! in_array($role->getName(), $refusedBy, true) && ! in_array($role->getName(), $grantedBy, true)) {
                continue;
            }

            /** @var Role[] $rolesReversed */
            $rolesReversed = [];

            do {
                array_unshift($rolesReversed, $role);
            } while (($role = $role->getParent()) !== null);

            $path = new HtmlElement('ol');

            $class = null;
            $setInitiator = false;
            foreach ($rolesReversed as $role) {
                $granted = false;
                $refused = false;
                $icon = new Icon('minus-circle');
                if ($permission === self::UNRESTRICTED_PERMISSION) {
                    if ($role->isUnrestricted()) {
                        $granted = true;
                        $icon = new Icon('check-circle', ['class' => 'granted']);
                    }
                } elseif ($role->denies($permission, true)) {
                    $refused = true;
                    $icon = new Icon('times-circle', ['class' => 'refused']);
                } elseif ($role->grants($permission, true, false)) {
                    $granted = true;
                    $icon = new Icon('check-circle', ['class' => 'granted']);
                }

                $connector = null;
                if ($role->getParent() !== null) {
                    $connector = new HtmlElement('li', ['class' => ['connector', $class]]);
                    if ($setInitiator) {
                        $setInitiator = false;
                        $connector->getAttributes()->add('class', 'initiator');
                    }

                    $path->prepend($connector);
                }

                $path->prepend(new HtmlElement('li', [
                    'class' => ['role', $class],
                    'title' => $role->getName()
                ], new Link([$icon, $role->getName()], Url::fromPath('role/edit', ['role' => $role->getName()]))));

                if ($refused) {
                    $setInitiator = $class !== 'refused';
                    $class = 'refused';
                } elseif ($granted) {
                    $setInitiator = $class === null;
                    $class = $class ?: 'granted';
                }
            }

            if ($vClass === null || $vClass === 'granted') {
                $vClass = $class;
            }

            array_unshift($rolePaths, $path->prepend([
                empty($rolePaths) ? null : new HtmlElement('li', ['class' => ['vertical-line', $vClass]]),
                new HtmlElement('li', ['class' => [
                    'connector',
                    $class,
                    $setInitiator ? 'initiator' : null
                ]])
            ]));
        }

        return [
            empty($refusedBy) ? (empty($grantedBy) ? null : true) : false,
            new HtmlElement('div', [
                'class' => [empty($rolePaths) ? null : 'collapsible', 'inheritance-paths'],
                'data-toggle-element' => '.collapsible-control',
                'data-no-persistence' => true,
                'data-visible-height' => 0
            ], [
                empty($rolePaths) ? $header : $header->addAttributes(['class' => 'collapsible-control']),
                $rolePaths
            ])
        ];
    }

    protected function auditRestriction($restriction)
    {
        $restrictedBy = [];
        $restrictions = [];
        foreach ($this->roles as $role) {
            if ($role->isUnrestricted()) {
                $restrictedBy = [];
                $restrictions = [];
                break;
            }

            foreach ($this->collectRestrictions($role, $restriction) as $role => $roleRestriction) {
                $restrictedBy[] = $role;
                $restrictions[] = $roleRestriction;
            }
        }

        $header = new HtmlElement('div');
        if (! empty($restrictedBy)) {
            $header->add([
                new Icon('filter', ['class' => 'restricted']),
                count($restrictedBy) > 2
                    ? sprintf(
                        tp(
                            'Restricted by %s and %s as well as one other',
                            'Restricted by %s and %s as well as %d others',
                            count($restrictedBy) - 2
                        ),
                        $restrictedBy[0]->getName(),
                        $restrictedBy[1]->getName(),
                        count($restrictedBy) - 2
                    )
                    : sprintf(
                        tp('Restricted by %s', 'Restricted by %s and %s', count($restrictedBy)),
                        ...array_map(function ($role) {
                            return $role->getName();
                        }, $restrictedBy)
                    )
            ]);
        } else {
            $header->add([new Icon('filter'), t('Not restricted by any role')]);
        }

        $roles = [];
        if (! empty($restrictions) && count($restrictions) > 1) {
            list($combinedRestrictions, $combinedLinks) = $this->createRestrictionLinks($restriction, $restrictions);
            $roles[] = new HtmlElement('li', null, [
                new HtmlElement('div', ['class' => 'flex-overflow'], [
                    new HtmlElement('span', [
                        'class' => 'role',
                        'title' => t('All roles combined')
                    ], join(' | ', array_map(function ($role) {
                        return $role->getName();
                    }, $restrictedBy))),
                    new HtmlElement('code', ['class' => 'restriction'], $combinedRestrictions)
                ]),
                $combinedLinks ? new HtmlElement('div', ['class' => 'previews'], [
                    new HtmlElement('em', null, t('Previews:')),
                    $combinedLinks
                ]) : null
            ]);
        }

        foreach ($restrictedBy as $role) {
            list($roleRestriction, $restrictionLinks) = $this->createRestrictionLinks(
                $restriction,
                [$role->getRestrictions($restriction)]
            );

            $roles[] = new HtmlElement('li', null, [
                new HtmlElement('div', ['class' => 'flex-overflow'], [
                    new Link($role->getName(), Url::fromPath('role/edit', ['role' => $role->getName()]), [
                        'class' => 'role',
                        'title' => $role->getName()
                    ]),
                    new HtmlElement('code', ['class' => 'restriction'], $roleRestriction)
                ]),
                $restrictionLinks ? new HtmlElement('div', ['class' => 'previews'], [
                    new HtmlElement('em', null, t('Previews:')),
                    $restrictionLinks
                ]) : null
            ]);
        }

        return [
            ! empty($restrictedBy),
            new HtmlElement('div', [
                'class' => [empty($roles) ? null : 'collapsible', 'restrictions'],
                'data-toggle-element' => '.collapsible-control',
                'data-no-persistence' => true,
                'data-visible-height' => 0
            ], [
                empty($roles) ? $header : $header->addAttributes(['class' => 'collapsible-control']),
                new HtmlElement('ul', null, $roles)
            ])
        ];
    }

    protected function assemble()
    {
        list($permissions, $restrictions) = RoleForm::collectProvidedPrivileges();
        list($wildcardState, $wildcardAudit) = $this->auditPermission('*');
        list($unrestrictedState, $unrestrictedAudit) = $this->auditPermission(self::UNRESTRICTED_PERMISSION);

        $this->add(new HtmlElement('li', [
            'class' => 'collapsible',
            'data-toggle-element' => 'h3',
            'data-visible-height' => 0
        ], [
            new HtmlElement('h3', null, [
                new HtmlElement('span', null, t('Administrative Privileges')),
                new HtmlElement('span', ['class' => 'audit-preview'], [
                    $wildcardState || $unrestrictedState
                        ? new Icon('check-circle', ['class' => 'granted'])
                        : null
                ])
            ]),
            new HtmlElement('ol', ['class' => 'privilege-list'], [
                new HtmlElement('li', null, [
                    new HtmlElement('p', ['class' => 'privilege-label'], t('Administrative Access')),
                    new HtmlElement('div', ['class' => 'spacer']),
                    $wildcardAudit
                ]),
                new HtmlElement('li', null, [
                    new HtmlElement('p', ['class' => 'privilege-label'], t('Unrestricted Access')),
                    new HtmlElement('div', ['class' => 'spacer']),
                    $unrestrictedAudit
                ])
            ])
        ]));

        $privilegeSources = array_unique(array_merge(array_keys($permissions), array_keys($restrictions)));
        foreach ($privilegeSources as $source) {
            $anythingGranted = false;
            $anythingRefused = false;
            $anythingRestricted = false;

            $permissionList = new HtmlElement('ol', ['class' => 'privilege-list']);
            foreach (isset($permissions[$source]) ? $permissions[$source] : [] as $permission => $metaData) {
                list($permissionState, $permissionAudit) = $this->auditPermission($permission);
                if ($permissionState !== null) {
                    if ($permissionState) {
                        $anythingGranted = true;
                    } else {
                        $anythingRefused = true;
                    }
                }

                $permissionList->add(new HtmlElement('li', null, [
                    new HtmlElement(
                        'p',
                        ['class' => 'privilege-label'],
                        isset($metaData['label'])
                            ? $metaData['label']
                            : array_map(function ($segment) {
                                return $segment[0] === '/' ? [
                                    // Adds a zero-width char after each slash to help browsers break onto newlines
                                    new HtmlString('/&#8203;'),
                                    new HtmlElement('span', ['class' => 'no-wrap'], substr($segment, 1))
                                ] : new HtmlElement('em', null, $segment);
                            }, preg_split(
                                '~(/[^/]+)~',
                                $permission,
                                -1,
                                PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY
                            ))
                    ),
                    new HtmlElement('div', ['class' => 'spacer']),
                    $permissionAudit
                ]));
            }

            $restrictionList = new HtmlElement('ol', ['class' => 'privilege-list']);
            foreach (isset($restrictions[$source]) ? $restrictions[$source] : [] as $restriction => $metaData) {
                list($restrictionState, $restrictionAudit) = $this->auditRestriction($restriction);
                if ($restrictionState) {
                    $anythingRestricted = true;
                }

                $restrictionList->add(new HtmlElement('li', null, [
                    new HtmlElement(
                        'p',
                        ['class' => 'privilege-label'],
                        isset($metaData['label'])
                            ? $metaData['label']
                            : array_map(function ($segment) {
                                return $segment[0] === '/' ? [
                                    // Adds a zero-width char after each slash to help browsers break onto newlines
                                    new HtmlString('/&#8203;'),
                                    new HtmlElement('span', ['class' => 'no-wrap'], substr($segment, 1))
                                ] : new HtmlElement('em', null, $segment);
                            }, preg_split(
                                '~(/[^/]+)~',
                                $restriction,
                                -1,
                                PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY
                            ))
                    ),
                    new HtmlElement('div', ['class' => 'spacer']),
                    $restrictionAudit
                ]));
            }

            if ($source === 'application') {
                $label = 'Icinga Web 2';
            } else {
                $label = [$source, ' ', new HtmlElement('em', null, t('Module'))];
            }

            $this->add(new HtmlElement('li', [
                'class' => 'collapsible',
                'data-toggle-element' => 'h3',
                'data-visible-height' => 0
            ], [
                new HtmlElement('h3', null, [
                    new HtmlElement('span', null, $label),
                    new HtmlElement('span', ['class' => 'audit-preview'], [
                        $anythingGranted ? new Icon('check-circle', ['class' => 'granted']) : null,
                        $anythingRefused ? new Icon('times-circle', ['class' => 'refused']) : null,
                        $anythingRestricted ? new Icon('filter', ['class' => 'restricted']) : null
                    ])
                ]),
                $permissionList->isEmpty() ? null : [
                    new HtmlElement('h4', null, t('Permissions')),
                    $permissionList
                ],
                $restrictionList->isEmpty() ? null : [
                    new HtmlElement('h4', null, t('Restrictions')),
                    $restrictionList
                ]
            ]));
        }
    }

    private function collectRestrictions(Role $role, $restrictionName)
    {
        do {
            $restriction = $role->getRestrictions($restrictionName);
            if ($restriction) {
                yield $role => $restriction;
            }
        } while (($role = $role->getParent()) !== null);
    }

    private function createRestrictionLinks($restrictionName, array $restrictions)
    {
        // TODO: Remove this hardcoded mess. Do this based on the restriction's meta data
        switch ($restrictionName) {
            case 'icingadb/filter/objects':
                $filterString = join('|', $restrictions);
                $list = new HtmlElement('ul', ['class' => 'links'], [
                    new HtmlElement('li', null, new Link(
                        'icingadb/hosts',
                        Url::fromPath('icingadb/hosts')->setQueryString($filterString)
                    )),
                    new HtmlElement('li', null, new Link(
                        'icingadb/services',
                        Url::fromPath('icingadb/services')->setQueryString($filterString)
                    )),
                    new HtmlElement('li', null, new Link(
                        'icingadb/hostgroups',
                        Url::fromPath('icingadb/hostgroups')->setQueryString($filterString)
                    )),
                    new HtmlElement('li', null, new Link(
                        'icingadb/servicegroups',
                        Url::fromPath('icingadb/servicegroups')->setQueryString($filterString)
                    ))
                ]);

                break;
            case 'icingadb/filter/hosts':
                $filterString = join('|', $restrictions);
                $list = new HtmlElement('ul', ['class' => 'links'], [
                    new HtmlElement('li', null, new Link(
                        'icingadb/hosts',
                        Url::fromPath('icingadb/hosts')->setQueryString($filterString)
                    )),
                    new HtmlElement('li', null, new Link(
                        'icingadb/services',
                        Url::fromPath('icingadb/services')->setQueryString($filterString)
                    ))
                ]);

                break;
            case 'icingadb/filter/services':
                $filterString = join('|', $restrictions);
                $list = new HtmlElement('ul', ['class' => 'links'], [
                    new HtmlElement('li', null, new Link(
                        'icingadb/services',
                        Url::fromPath('icingadb/services')->setQueryString($filterString)
                    ))
                ]);

                break;
            case 'monitoring/filter/objects':
                $filterString = join('|', $restrictions);
                $list = new HtmlElement('ul', ['class' => 'links'], [
                    new HtmlElement('li', null, new Link(
                        'monitoring/list/hosts',
                        Url::fromPath('monitoring/list/hosts')->setQueryString($filterString)
                    )),
                    new HtmlElement('li', null, new Link(
                        'monitoring/list/services',
                        Url::fromPath('monitoring/list/services')->setQueryString($filterString)
                    )),
                    new HtmlElement('li', null, new Link(
                        'monitoring/list/hostgroups',
                        Url::fromPath('monitoring/list/hostgroups')->setQueryString($filterString)
                    )),
                    new HtmlElement('li', null, new Link(
                        'monitoring/list/servicegroups',
                        Url::fromPath('monitoring/list/servicegroups')->setQueryString($filterString)
                    ))
                ]);

                break;
            case 'application/share/users':
                $filter = Filter::any();
                foreach ($restrictions as $roleRestriction) {
                    $userNames = StringHelper::trimSplit($roleRestriction);
                    foreach ($userNames as $userName) {
                        $filter->add(Filter::equal('user_name', $userName));
                    }
                }

                $filterString = QueryString::render($filter);
                $list = new HtmlElement('ul', ['class' => 'links'], [
                    new HtmlElement('li', null, new Link(
                        'user/list',
                        Url::fromPath('user/list')->setQueryString($filterString)
                    ))
                ]);

                break;
            case 'application/share/groups':
                $filter = Filter::any();
                foreach ($restrictions as $roleRestriction) {
                    $groupNames = StringHelper::trimSplit($roleRestriction);
                    foreach ($groupNames as $groupName) {
                        $filter->add(Filter::equal('group_name', $groupName));
                    }
                }

                $filterString = QueryString::render($filter);
                $list = new HtmlElement('ul', ['class' => 'links'], [
                    new HtmlElement('li', null, new Link(
                        'group/list',
                        Url::fromPath('group/list')->setQueryString($filterString)
                    ))
                ]);

                break;
            default:
                $filterString = join(', ', $restrictions);
                $list = null;
        }

        return [$filterString, $list];
    }
}
