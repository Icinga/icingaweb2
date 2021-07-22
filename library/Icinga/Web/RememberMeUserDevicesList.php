<?php
/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Web;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Url as iplWebUrl;  //alias is needed for php5.6
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;

class RememberMeUserDevicesList extends BaseHtmlElement
{
    protected $tag = 'table';

    protected $defaultAttributes = [
        'class'             => 'common-table',
        'data-base-target'  => '_self'
    ];

    /**
     * @var array
     */
    protected $devicesList;

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
     * @return array List of devices. Each device contains user agent and fingerprint string
     */
    public function getDevicesList()
    {
        return $this->devicesList;
    }

    /**
     * @param $devicesList
     *
     * @return $this
     */
    public function setDevicesList($devicesList)
    {
        $this->devicesList = $devicesList;

        return $this;
    }

    protected function assemble()
    {
        $thead = Html::tag('thead');
        $theadRow = Html::tag('tr')
            ->add(Html::tag(
                'th',
                sprintf(t('List of devices and browsers %s is currently logged in:'), $this->getUsername())
            ));

        $thead->add($theadRow);

        $head = Html::tag('tr')
            ->add(Html::tag('th', t('OS')))
            ->add(Html::tag('th', t('Browser')))
            ->add(Html::tag('th', t('Fingerprint')));

        $thead->add($head);
        $tbody = Html::tag('tbody');

        if (empty($this->getDevicesList())) {
            $tbody->add(Html::tag('td', t('No device found')));
        } else {
            foreach ($this->getDevicesList() as $device) {
                $agent = new UserAgent($device);
                $element = Html::tag('tr')
                    ->add(Html::tag('td', $agent->getOs()))
                    ->add(Html::tag('td', $agent->getBrowser()))
                    ->add(Html::tag('td', $device->random_iv));

                $link = (new Link(
                    new Icon('trash'),
                    iplWebUrl::fromPath($this->getUrl())
                        ->addParams(
                            [
                                'name'          => $this->getUsername(),
                                'fingerprint'   => $device->random_iv,
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
