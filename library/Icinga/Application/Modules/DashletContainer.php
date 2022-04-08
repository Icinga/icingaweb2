<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Application\Modules;

use ipl\Web\Url;

/**
 * Container for module dashlets
 */
class DashletContainer extends NavigationItemContainer
{
    /**
     * Url of this dashlet item
     *
     * @var Url|string
     */
    protected $url;

    /**
     * Create a new Dashlet container
     *
     * @param string $name
     * @param Url|string $url
     * @param array $properties
     */
    public function __construct($name, $url, array $properties = [])
    {
        parent::__construct($name, $properties);

        $this->url = $url;
    }

    /**
     * Get url of this dashlet item
     *
     * @return Url|string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set url of this dashlet item
     *
     * @param Url|string $url
     *
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }
}
