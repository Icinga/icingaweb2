<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form;

/**
*   Class that helps building and validating forms and offers a rudimentary
*   data-binding mechanismn. 
* 
*   The underlying form can be accessed by expicitly calling $builder->getForm() or
*   by directly calling the forms method (which is, in case of populate() the preferred way)
*   like: $builder->getElements()
*
**/
class Builder
{
    const CSRF_ID = "icinga_csrf_id";
    
    private $form;
    private $boundModel = null;
    private $disableCSRF = false;

    /**
    *   Constructrs a new Formbuilder, containing an empty form if no 
    *   $form parameter is given or the Zend form from the $form parameter.
    *   
    *   @param  \Zend_Form      The form to use with this Builder
    *   @param  Array           an optional array of Options:
    *               - CSRFProtection    true to add a crsf token to the
    *                                   form (default), false to remove it
    *               - model             An referenced array or object to use 
    *                                   for value binding 
    **/
    public function __construct(\Zend_Form $form = null, array $options = array())
    {
        if ($form === null) {
            $form = new \Zend_Form();
        }
            
        $this->form = $form;
        if (isset($options["CSRFProtection"])) {
            $this->disableCSRF  = !$options["CSRFProtection"];
        }
        if (isset($options["model"])) {
            $this->boundModel = &$options["model"];
        }
    }
    
    public function setZendForm(\Zend_Form $form)
    {
        $this->form = $form;
    }

    public function getForm()
    {
        if (!$this->disableCSRF) {
            $this->addCSRFFieldToForm();
        }
        if (!$this->form) {
            return new \Zend_Form();
        }
        return $this->form;
    }
 
    public function addElementsFromConfig(array $elements)
    {
        foreach ($elements as $key => $values) {
            $this->addElement($values[0], $key, $values[1]);
        }
    }

    public static function fromArray(array $elements, $options = array())
    {
        $builder = new Builder(null, $options);
        $builder->addElementsFromConfig($elements);
        return $builder;
    }

    public function isValid(array $data = null)
    {
        if ($data === null) {
            $data = $_POST;
        }
        return $this->hasValidToken() && $this->form->isValid($data);
    }

    public function isSubmitted($btnName = 'submit')
    {
        $btn = $this->getElement($btnName);
        if (!$btn || !isset($_POST[$btnName])) {
            return false;
        }
        return $_POST[$btnName] === $btn->getLabel();
    }

    public function render()
    {
        return $this->getForm()->render();
    }

    public function __toString()
    {
        return $this->getForm()->__toString();
    }
    
    public function enableCSRF()
    {
        $this->disableCSRF = false;
    }

    public function disableCSRF()
    {
        $this->disableCSRF = true;
        $this->form->removeElement(self::CSRF_ID);
        $this->form->removeElement(self::CSRF_ID."_seed");
    }

    private function addCSRFFieldToForm()
    {
        if (!$this->form || $this->disableCSRF || $this->form->getElement(self::CSRF_ID)) {
            return;
        }
        list($seed, $token) = $this->getSeedTokenPair();
        
        $this->form->addElement("hidden", self::CSRF_ID);
        $this->form->getElement(self::CSRF_ID)
            ->setValue($token)
            ->setDecorators(array('ViewHelper'));
        
        $this->form->addElement("hidden", self::CSRF_ID."_seed");
        $this->form->getElement(self::CSRF_ID."_seed")
            ->setValue($seed)
            ->setDecorators(array('ViewHelper'));

    }

    public function bindToModel(&$model)
    {
        $this->boundModel = &$model;
    }

    public function repopulate()
    {
        if (!empty($_POST)) {
            $this->populate($_POST);
        }
    }

    public function populate($data, $ignoreModel = false)
    {
        if (is_array($data)) {
            $this->form->populate($data);
        } elseif (is_object($data)) {
            $this->populateFromObject($data);
        } else {
            throw new InvalidArgumentException("Builder::populate() expects and object or array, $data given");
        }
        if ($this->boundModel === null || $ignoreModel) {
            return;
        }
        $this->updateModel();
        
    }

    private function populateFromObject($data)
    {
        foreach ($this->form->getElements() as $name => $element) {
            if (isset($data->$name)) {
                $element->setValue($data->$name);
                
            } else {
                $getter = "get".ucfirst($name);
                if (method_exists($data, $getter)) {
                    $element->setValue($data->$getter());
                }
            }
        }
    }

    public function updateModel()
    {
        if (is_array($this->boundModel)) {
            $this->updateArrayModel();
        } elseif (is_object($this->boundModel)) {
            $this->updateObjectModel();
        }
    }

    private function updateObjectModel()
    {
        foreach ($this->form->getElements() as $name => $element) {
            if (isset($this->boundModel->$name)) {
                $this->boundModel->$name = $element->getValue();
            } else {
                $setter = "set".ucfirst($name);
                if (method_exists($this->boundModel, $setter)) {
                    $this->boundModel->$setter($element->getValue());
                }

            }
        }
    }

    private function updateArrayModel()
    {
        foreach ($this->form->getElements() as $name => $element) {
            if (isset($this->boundModel[$name])) {
                $this->boundModel[$name] = $element->getValue();
            }
        }
    }

    public function syncWithModel()
    {
        $this->populate($this->boundModel, true);
    }
    
    public function __call($fn, array $args)
    {
        if (method_exists($this->form, $fn)) {
            return call_user_func_array(array($this->form, $fn), $args);
        } else {
            throw new \BadMethodCallException(
                "Method $fn does not exist either ".
                "in \Icinga\Form\Builder nor in Zend_Form"
            );
        }
    }


    /**
     * Whether the token parameter is valid
     *
     * @param int    $maxAge    Max allowed token age
     * @param string $sessionId A specific session id (useful for tests?)
     *
     * return bool
     */
    public function hasValidToken($maxAge = 600, $sessionId = null)
    {
        if ($this->disableCSRF) {
            return true;
        }
        
        if ($this->form->getElement(self::CSRF_ID) == null) {
            return false;
        }

        $sessionId = $sessionId ? $sessionId : session_id();
        $seed = $this->form->getElement(self::CSRF_ID.'_seed')->getValue();
        if (! is_numeric($seed)) {
            return false;
        }

        // Remove quantitized timestamp portion so maxAge applies
        $seed -= (intval(time() / $maxAge) * $maxAge);
        $token = $this->getElement(self::CSRF_ID)->getValue();
        return $token === hash('sha256', $sessionId . $seed);
    }

    /**
     * Get a new seed/token pair
     *
     * @param int    $maxAge    Max allowed token age
     * @param string $sessionId A specific session id (useful for tests?)
     *
     * return array
     */
    public function getSeedTokenPair($maxAge = 600, $sessionId = null)
    {
        $sessionId = $sessionId ? $sessionId : session_id();
        $seed = mt_rand();
        $hash = hash('sha256', $sessionId . $seed);

        // Add quantitized timestamp portion to apply maxAge
        $seed += (intval(time() / $maxAge) * $maxAge);
        return array($seed, $hash);
    }
}
