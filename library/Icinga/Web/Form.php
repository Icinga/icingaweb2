<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web;

use Zend_Form;
use Zend_Controller_Front as Front; // TODO: Get from App
use Zend_Controller_Action_HelperBroker as ZfActionHelper;

/**
 * Class Form
 * @package Icinga\Web
 */
class Form extends Zend_Form
{
    protected $request;

    /**
     * @param array $options[optional]
     * @internal param \Icinga\Web\Zend_Controller_Request_Abstract $request
     */
    public function __construct($options = null)
    {
        /*
        if (isset($options['prefill'])) {
            $this->_prefill = $options['prefill'];
            unset($options['prefill']);
        }
        */
        $this->request = Front::getInstance()->getRequest();
        // $this->handleRequest();
        foreach ($this->elements() as $key => $values) {
            $this->addElement($values[0], $key, $values[1]); // do it better!
        }

        // Should be replaced with button check:
        $this->addElement('hidden', '__submitted');
        $this->setDefaults(array('__submitted' => 'true'));

        parent::__construct($options);
        if ($this->getAttrib('action') === null) {
            $this->setAction($this->request->getRequestUri());
        }
        if ($this->getAttrib('method') === null) {
            $this->setMethod('post');
        }
        if ($this->hasBeenSubmitted()) {
            $this->handleRequest();
        }
    }

    public function redirectNow($url)
    {
        ZfActionHelper::getStaticHelper('redirector')
            ->gotoUrlAndExit($url);
    }

    public function handleRequest()
    {
        if ($this->isValid($this->request->getPost())) {
            $this->onSuccess();
        } else {
            $this->onFailure();
        }
    }

    public function onSuccess()
    {
    }

    public function onFailure()
    {
    }

    public function hasBeenSubmitted()
    {
        return $this->request->getPost('__submitted', 'false') === 'true';
    }

    public function elements()
    {
        return array();
    }
}
