<?php
/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard;

use Icinga\Common\DBUserManager;
use Icinga\Common\Relation;
use Icinga\DBUser;
use Icinga\Web\Navigation\DashboardHome;
use Icinga\Web\Url;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Sql\Select;
use ipl\Web\Widget\Link;

/**
 * A dashboard pane dashlet
 *
 * This is the new element being used for the Dashlets view
 */
class Dashlet extends BaseHtmlElement
{
    use Relation;
    use UserWidget;

    /** @var string Database table name */
    const TABLE = 'dashlet';

    /** @var string Database overriding table name */
    const OVERRIDING_TABLE = 'dashlet_override';

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'container dashlet-sortable'];

    protected $tableMembership = 'dashlet_member';

    /**
     * The url of this Dashlet
     *
     * @var Url|null
     */
    protected $url;

    /**
     * Not translatable name of this dashlet
     *
     * @var string
     */
    protected $name;

    /**
     * The title being displayed on top of the dashlet
     * @var
     */
    protected $title;

    /**
     * The pane this dashlet belongs to
     *
     * @var Pane
     */
    protected $pane;

    /**
     * The progress label being used
     *
     * @var string
     */
    protected $progressLabel;

    /**
     * Unique identifier of this dashlet
     *
     * @var string
     */
    protected $dashletId;

    /**
     * The priority order of this dashlet
     *
     * @var int
     */
    protected $order;

    /**
     * Create a new dashlet displaying the given url in the provided pane
     *
     * @param string $title     The title to use for this dashlet
     * @param Url|string $url   The url this dashlet uses for displaying information
     * @param Pane|null $pane   The pane this Dashlet will be added to
     */
    public function __construct($title, $url, Pane $pane = null)
    {
        $this->name = $title;
        $this->title = $title;
        $this->pane = $pane;
        $this->url = $url;

        $this->loadMembers();
    }

    public function loadMembers()
    {
        $conn = DashboardHome::getConn();
        $members = $conn->select((new Select())
            ->columns('user.id, user.name')
            ->from($this->getTableMembership() . ' member')
            ->join(self::TABLE, 'member.dashlet_id = dashlet.id')
            ->join(DBUserManager::$dashboardUsersTable . ' user', 'user.id = member.user_id')
            ->where(['dashlet.id = ?' => $this->getDashletId()]));

        $users = [];
        foreach ($members as $member) {
            $member = (new DBUser($member->name))->setIdentifier($member->id);
            $users[$member->getUsername()] = $member;
        }

        $this->setMembers($users);
    }

    /**
     * Set the identifier of this dashlet
     *
     * @param string $id
     *
     * @return Dashlet
     */
    public function setDashletId($id)
    {
        $this->dashletId = $id;

        return $this;
    }

    /**
     * Get the unique identifier of this dashlet
     *
     * @return string
     */
    public function getDashletId()
    {
        return $this->dashletId;
    }

    /**
     * Setter for this name
     *
     * @param $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Getter for this name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Retrieve the dashlets title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title !== null ? $this->title : $this->getName();
    }

    /**
     * Set the title of this dashlet
     *
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the priority order of this dashlet
     *
     * @return int
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set the priority order of this dashlet
     *
     * @param $order
     *
     * @return $this
     */
    public function setOrder($order)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Retrieve the dashlets url
     *
     * @return Url|null
     */
    public function getUrl()
    {
        if ($this->url !== null && ! $this->url instanceof Url) {
            $this->url = Url::fromPath($this->url);
        }

        return $this->url;
    }

    /**
     * Set the dashlets URL
     *
     * @param  string|Url $url  The url to use, either as an Url object or as a path
     *
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Set the progress label to use
     *
     * @param   string  $label
     *
     * @return  $this
     */
    public function setProgressLabel($label)
    {
        $this->progressLabel = $label;

        return $this;
    }

    /**
     * Return the progress label to use
     *
     * @return  string
     */
    public function getProgressLabe()
    {
        if ($this->progressLabel === null) {
            return $this->progressLabel = t('Loading');
        }

        return $this->progressLabel;
    }

    /**
     * Set the Pane of this dashlet
     *
     * @param Pane $pane
     *
     * @return Dashlet
     */
    public function setPane(Pane $pane)
    {
        $this->pane = $pane;

        return $this;
    }

    /**
     * Get the pane of this dashlet
     *
     * @return Pane
     */
    public function getPane()
    {
        return $this->pane;
    }

    protected function assemble()
    {
        if (! $this->getUrl()) {
            $this->addHtml(HtmlElement::create('h1', null, t($this->getTitle())));
            $this->addHtml(HtmlElement::create(
                'p',
                ['class' => 'error-message'],
                sprintf(t('Cannot create dashboard dashlet "%s" without valid URL'), t($this->getTitle()))
            ));
        } else {
            $url = $this->getUrl();
            $url->setParam('showCompact', true);

            $this->setAttribute('data-icinga-url', $url);
            $this->addHtml(new HtmlElement('h1', null, new Link(
                t($this->getTitle()),
                $url->getUrlWithout(['showCompact', 'limit'])->getRelativeUrl(),
                [
                    'aria-label'        => t($this->getTitle()),
                    'title'             => t($this->getTitle()),
                    'data-base-target'  => 'col1'
                ]
            )));

            $this->addHtml(HtmlElement::create(
                'p',
                ['class' => 'progress-label'],
                [
                    $this->getProgressLabe(),
                    HtmlElement::create('span', null, '.'),
                    HtmlElement::create('span', null, '.'),
                    HtmlElement::create('span', null, '.'),
                ]
            ));
        }
    }

    /**
     * Get this dashlet's structure as array
     *
     * @return  array
     */
    public function toArray()
    {
        return array(
            'id'        => $this->getDashletId(),
            'pane'      => $this->getPane() ? $this->getPane()->getName() : null,
            'name'      => $this->getName(),
            'url'       => $this->getUrl()->getRelativeUrl(),
            'label'     => $this->getTitle(),
            'order'     => $this->getOrder(),
            'disabled'  => (int) $this->isDisabled(),
        );
    }
}
