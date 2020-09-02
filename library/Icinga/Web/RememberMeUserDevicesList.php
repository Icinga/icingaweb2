<?php
/* Icinga Web 2 | (c) 2020 Icinga GmbH | GPLv2+ */
namespace Icinga\Web;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Url;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use phpDocumentor\Reflection\Types\This;

class RememberMeUserDevicesList extends BaseHtmlElement
{
    protected $tag = 'table';

    protected $defaultAttributes = [
        'class' => 'common-table',
        'data-base-target' => '_self',
        'title' => 'click to remove this cookie from the database'
    ];

    /**
     * @var array
     */
    protected $userList;

    /**
     * @var string
     */
    protected $username;

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
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     *
     * @return $this
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @return array $_SERVER['HTTP_USER_AGENT'] output string for all devices as associative array
     */
    public function getDevicesList()
    {
        return $this->userList;
    }

    /**
     * @param $userList
     * @return $this
     */
    public function setDevicesList($userList)
    {
        $this->userList = $userList;
        return $this;
    }

    protected function assemble()
    {
        $thead = Html::tag('thead');
        $theadRow = Html::tag('tr');
        $theadRow->add(Html::tag(
            'th',
            ucfirst("List of devices and browsers {$this->getUsername()} is currently logged in:")
        ));

        $thead->add($theadRow);

        $tbody = Html::tag('tbody');

        $head = Html::tag('tr');
        $head->add(Html::tag('th', 'os'));
        $head->add(Html::tag('th', 'browser'));
        $thead->add($head);

        if (empty($this->getDevicesList())) {
            $tbody->add(Html::tag('td', 'No device found'));
        } else {
            foreach ($this->getDevicesList() as $userAgent) {
                $agent = new UserAgent($userAgent);

                $element = Html::tag('tr');

                $element->add(Html::tag('td', $agent->getOs()));
                $element->add(Html::tag('td', $agent->getBrowser()));

                $link =(new Link(
                    new Icon('trash'),
                    Url::fromPath($this->getUrl())
                        ->addParams(
                            [
                                'name' => $this->getUsername(),
                                'agent' =>  $userAgent->http_user_agent,
                            ]
                        )
                ));

                $element->add(Html::tag('td', $link));

                $tbody->add($element);
            }
        }

        $this->add($thead);
        $this->add($tbody);
    }
}
