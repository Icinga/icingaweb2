<?php

/* Icinga Web 2 | (c) 2024 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Widget;

use Icinga\Web\Url;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\I18n\Translation;

/**
 * Render roles as table
 */
class RolesTable extends BaseHtmlElement
{
    use Translation;

    protected $tag = 'table';

    /**
     * The roles to display
     *
     * @var iterable
     */
    protected $roles = [];

    protected function assemble(): void
    {
        $this->setAttributes([
            'class'            => 'table-row-selectable common-table',
            'data-base-target' => '_next'
        ]);

        $this->addHtml(Html::tag('thead', [], [
            Html::tag('tr', [], [
                Html::tag('th', [], [$this->translate('Name')]),
                Html::tag('th', [], [$this->translate('Users')]),
                Html::tag('th', [], [$this->translate('Groups')]),
                Html::tag('th', [], [$this->translate('Inherits From')]),
                Html::tag('th')
            ])
        ]));

        $tbody = Html::tag('tbody');

        $this->addHtml($tbody);

        foreach ($this->roles as $role) {
            $users = [];
            $groups = [];

            foreach ($role->users as $user) {
                $users[] = $user->user_name;
            }

            foreach ($role->groups as $group) {
                $groups[] = $group->group_name;
            }

            sort($users);
            sort($groups);

            $tbody->addHtml(Html::tag('tr', [], [
                Html::tag('td', [], [Html::tag(
                    'a',
                    [
                        'href'  => Url::fromPath('role/edit', ['role' => $role->name]),
                        'title' => sprintf($this->translate('Edit role %s'), $role->name)
                    ],
                    $role->name
                )]),
                Html::tag('td', [], [implode(',', $users)]),
                Html::tag('td', [], [implode(',', $groups)]),
                Html::tag('td', [], $role->parent ? [$role->parent->name] : null),
                Html::tag('td', ['class' => 'icon-col'], [Html::tag(
                    'a',
                    [
                        'href'  => Url::fromPath('role/remove', ['role' => $role->name]),
                        'class' => 'action-link icon-cancel',
                        'title' => sprintf($this->translate('Remove role %s'), $role->name)
                    ]
                )])
            ]));
        }
    }

    /**
     * Set the roles to display
     *
     * @param iterable $roles
     *
     * @return $this
     */
    public function setRoles(iterable $roles): self
    {
        $this->roles = $roles;

        return $this;
    }
}
