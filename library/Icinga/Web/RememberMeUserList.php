<?php
/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Web;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Url as iplWebUrl;  //alias is needed for php5.6
use ipl\Web\Widget\Link;

/**
 * Class RememberMeUserList
 *
 * @package Icinga\Web
 */
class RememberMeUserList extends BaseHtmlElement
{
    protected $tag = 'table';

    protected $defaultAttributes = [
        'class'             => 'common-table table-row-selectable',
        'data-base-target'  => '_next',
    ];

    /**
     * @var array
     */
    protected $users;

    /**
     * @var string
     */
    protected $url;

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     *
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return array
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @param array $users
     *
     * @return $this
     */
    public function setUsers($users)
    {
        $this->users = $users;

        return $this;
    }

    protected function assemble()
    {
        $thead = Html::tag('thead');
        $theadRow = Html::tag('tr')
            ->add(Html::tag(
                'th',
                t('List of users who stay logged in')
            ));

        $thead->add($theadRow);
        $tbody = Html::tag('tbody');

        if (empty($this->getUsers())) {
            $tbody->add(Html::tag('td', t('No user found')));
        } else {
            foreach ($this->getUsers() as $user) {
                $element = Html::tag('tr');
                $link = new Link(
                    $user->username,
                    iplWebUrl::fromPath($this->getUrl())->addParams(['name' => $user->username]),
                    ['title' => sprintf(t('Device list of %s'), $user->username)]
                );

                $element->add(Html::tag('td', $link));
                $tbody->add($element);
            }
        }

        $this->add($thead);
        $this->add($tbody);
    }
}
