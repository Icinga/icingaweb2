<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Hook\Configuration;

use Icinga\Exception\ProgrammingError;

/**
 * Class ConfigurationTab
 *
 * Hook to represent configuration tabs
 *
 * @package Icinga\Web\Hook\Configuration
 */
class ConfigurationTab implements ConfigurationTabInterface
{
    /**
     * Module name
     * @var string
     */
    private $moduleName;

    /**
     * Url segment to invoke controller
     * @var string
     */
    private $url;

    /**
     * Title of the tab
     * @var string
     */
    private $title;

    /**
     * Create a new instance
     *
     * @param string|null $name
     * @param string|null $url
     * @param string|null $title
     */
    public function __construct($name = null, $url = null, $title = null)
    {
        if ($name !== null) {
            $this->setModuleName($name);

            if ($title === null) {
                $this->setTitle($name);
            }
        }

        if ($url !== null) {
            $this->setUrl($url);
        }

        if ($title !== null) {
            $this->setTitle($title);
        }
    }

    /**
     * Setter for title
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Getter for title
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Setter for url
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Getter for url
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Setter for module name
     * @param string $moduleName
     */
    public function setModuleName($moduleName)
    {
        $this->moduleName = $moduleName;
    }

    private function assertConfiguration()
    {
        if (!$this->moduleName) {
            throw new ProgrammingError('moduleName is missing');
        }

        if (!$this->getUrl()) {
            throw new ProgrammingError('url is missing');
        }

        if (!$this->getTitle()) {
            throw new ProgrammingError('title is missing');
        }
    }

    /**
     * Returns a tab configuration to build configuration links
     * @return array
     */
    public function getTab()
    {
        $this->assertConfiguration();

        return array(
            'title' => $this->getTitle(),
            'url' => $this->getUrl()
        );
    }

    /**
     * Return the tab key
     * @return string
     */
    public function getModuleName()
    {
        $this->assertConfiguration();
        return $this->moduleName;
    }
}
