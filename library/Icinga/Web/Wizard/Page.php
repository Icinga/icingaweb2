<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Wizard;

use Icinga\Web\Form;

class Page extends Form
{
    /**
     * Whether a CSRF token should not be added to this wizard page
     *
     * @var bool
     */
    protected $tokenDisabled = true;

    /**
     * The title of this wizard page
     *
     * @var string
     */
    protected $title = '';

    /**
     * Overwrite this to initialize this wizard page
     */
    public function init()
    {

    }

    /**
     * Set the title for this wizard page
     *
     * @param   string  $title  The title to set
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Return the title of this wizard page
     *
     * @return  string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Return a config containing all values of this wizard page
     *
     * @return  Zend_Config
     */
    public function getConfig()
    {
        return $this->getConfiguration();
    }
}
