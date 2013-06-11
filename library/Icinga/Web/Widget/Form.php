<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Widget;

/**
 * A form loader...
 *
 * @copyright  Copyright (c) 2013 Icinga-Web Team <info@icinga.org>
 * @author     Icinga-Web Team <info@icinga.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @deprecated Because of HTML creation of PHP<
 */
class Form extends AbstractWidget
{
    protected $form;
    protected $properties = array(
        'name' => null
    );

    public function __call($func, $args)
    {
        return call_user_func_array(array($this->form, $func), $args);
    }

    protected function init()
    {
        // Load form by name given in props?
        $class = 'Icinga\\Web\\Form\\' . ucfirst($this->name) . 'Form';
        $file = ICINGA_APPDIR
              . '/forms/authentication/'
              . ucfirst($this->name)
              . 'Form.php';
        require_once($file);
        $this->form = new $class;
    }

    public function renderAsHtml()
    {
        return (string) $this->form;
    }
}
