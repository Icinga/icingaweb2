<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Widget\Dashboard;

use Icinga\Data\ConfigObject;
use Icinga\Web\Widget\AbstractWidget;
use Icinga\Exception\ProgrammingError;
use Icinga\Exception\ConfigurationError;

/**
 * A pane, displaying different Dashboard dashlets
 */
class Pane extends UserWidget
{
    /**
     * The name of this pane, as defined in the ini file
     *
     * @var string
     */
    private $name;

    /**
     * The title of this pane, as displayed in the dashboard tabs
     * @TODO: Currently the same as $name, evaluate if distinguishing is needed
     *
     * @var string
     */
    private $title;

    /**
     * An array of @see Dashlets that are displayed in this pane
     *
     * @var array
     */
    private $dashlets = array();

    /**
     * Disabled flag of a pane
     *
     * @var bool
     */
    private $disabled;

    /**
     * Create a new pane
     *
     * @param string $name         The pane to create
     */
    public function __construct($name)
    {
        $this->name  = $name;
        $this->title = $name;
    }

    /**
     * Returns the name of this pane
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the title of this pane
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Overwrite the title of this pane
     *
     * @param string $title     The new title to use for this pane
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Return true if a dashlet with the given title exists in this pane
     *
     * @param string $title     The title of the dashlet to check for existence
     *
     * @return bool
     */
    public function hasDashlet($title)
    {
        return array_key_exists($title, $this->dashlets);
    }

    /**
     * Checks if the current pane has any dashlets
     *
     * @return bool
     */
    public function hasDashlets()
    {
        return ! empty($this->dashlets);
    }

    /**
     * Return a dashlet with the given name if existing
     *
     * @param string $title         The title of the dashlet to return
     *
     * @return Dashlet            The dashlet with the given title
     * @throws ProgrammingError     If the dashlet doesn't exist
     */
    public function getDashlet($title)
    {
        if ($this->hasDashlet($title)) {
            return $this->dashlets[$title];
        }
        throw new ProgrammingError(
            'Trying to access invalid dashlet: %s',
            $title
        );
    }

    /**
     * Removes the dashlet with the given title if it exists in this pane
     *
     * @param string $title         The pane
     * @return Pane $this
     */
    public function removeDashlet($title)
    {
        if ($this->hasDashlet($title)) {
            $dashlet = $this->getDashlet($title);
            if ($dashlet->isUserWidget() === true) {
                unset($this->dashlets[$title]);
            } else {
                $dashlet->setDisabled(true);
                $dashlet->setUserWidget();
            }
        } else {
            throw new ProgrammingError('Dashlet does not exist: ' . $title);
        }
        return $this;
    }

    /**
     * Removes all or a given list of dashlets from this pane
     *
     * @param array $dashlets Optional list of dashlet titles
     * @return Pane $this
     */
    public function removeDashlets(array $dashlets = null)
    {
        if ($dashlets === null) {
            $this->dashlets = array();
        } else {
            foreach ($dashlets as $dashlet) {
                $this->removeDashlet($dashlet);
            }
        }
        return $this;
    }

    /**
     * Return all dashlets added at this pane
     *
     * @return array
     */
    public function getDashlets()
    {
        return $this->dashlets;
    }

    /**
     * @see Widget::render
     */
    public function render()
    {
        $dashlets = array_filter(
            $this->dashlets,
            function ($e) {
                return ! $e->getDisabled();
            }
        );
        return implode("\n", $dashlets) . "\n";
    }

    /**
     * Add a dashlet to this pane, optionally creating it if $dashlet is a string
     *
     * @param string|Dashlet $dashlet               The dashlet object or title
     *                                                  (if a new dashlet will be created)
     * @param string|null $url                          An Url to be used when dashlet is a string
     *
     * @return $this
     * @throws \Icinga\Exception\ConfigurationError
     */
    public function addDashlet($dashlet, $url = null)
    {
        if ($dashlet instanceof Dashlet) {
            $this->dashlets[$dashlet->getTitle()] = $dashlet;
        } elseif (is_string($dashlet) && $url !== null) {
             $this->dashlets[$dashlet] = new Dashlet($dashlet, $url, $this);
        } else {
            throw new ConfigurationError('Invalid dashlet added: %s', $dashlet);
        }
        return $this;
    }

    /**
     * Add new dashlets to existing dashlets
     *
     * @param array $dashlets
     * @return $this
     */
    public function addDashlets(array $dashlets)
    {
        /* @var $dashlet Dashlet */
        foreach ($dashlets as $dashlet) {
            if (array_key_exists($dashlet->getTitle(), $this->dashlets)) {
                if (preg_match('/_(\d+)$/', $dashlet->getTitle(), $m)) {
                    $name = preg_replace('/_\d+$/', $m[1]++, $dashlet->getTitle());
                } else {
                    $name = $dashlet->getTitle() . '_2';
                }
                $this->dashlets[$name] = $dashlet;
            } else {
                $this->dashlets[$dashlet->getTitle()] = $dashlet;
            }
        }

        return $this;
    }

    /**
     * Add a dashlet to the current pane
     *
     * @param $title
     * @param $url
     * @return Dashlet
     *
     * @see addDashlet()
     */
    public function add($title, $url = null)
    {
        $this->addDashlet($title, $url);

        return $this->dashlets[$title];
    }

    /**
     * Return the this pane's structure as array
     *
     * @return  array
     */
    public function toArray()
    {
        $pane =  array(
            'title'     => $this->getTitle(),
        );

        if ($this->getDisabled() === true) {
            $pane['disabled'] = 1;
        }

        return $pane;
    }

    /**
     * Create a new pane with the title $title from the given configuration
     *
     * @param $title                The title for this pane
     * @param ConfigObject  $config The configuration to use for setup
     *
     * @return Pane
     */
    public static function fromIni($title, ConfigObject $config)
    {
        $pane = new Pane($title);
        if ($config->get('title', false)) {
            $pane->setTitle($config->get('title'));
        }
        return $pane;
    }

    /**
     * Setter for disabled
     *
     * @param boolean $disabled
     */
    public function setDisabled($disabled = true)
    {
        $this->disabled = (bool) $disabled;
    }

    /**
     * Getter for disabled
     *
     * @return boolean
     */
    public function getDisabled()
    {
        return $this->disabled;
    }


}
