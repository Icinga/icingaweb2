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
     * The wizard this page is part of
     *
     * @var Wizard
     */
    protected $wizard;

    /**
     * The title of this wizard page
     *
     * @var string
     */
    protected $title = '';

    /**
     * Create a new wizard page
     *
     * @param   Wizard  $wizard     The wizard this page is part of
     * @param   mixed   $options    Zend_Form options
     */
    public function __construct(Wizard $wizard = null, $options = null)
    {
        parent::__construct($options);
        $this->wizard = $wizard;
    }

    /**
     * Overwrite this to initialize this wizard page
     */
    public function init()
    {

    }

    /**
     * Return whether this page needs to be shown to the user
     *
     * Overwrite this to add page specific handling
     *
     * @return  bool
     */
    public function isRequired()
    {
        return true;
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
     * Return a config containing all values provided by the user
     *
     * @return  Zend_Config
     */
    public function getConfig()
    {
        return $this->getConfiguration();
    }
}
