<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * Icinga 2 Web - Head for multiple monitoring frontends
 * Copyright (C) 2013 Icinga Development Team
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * 
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @author Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form;

/**
 * Class that helps building and validating forms and offers a rudimentary
 * data-binding mechanismn.
 *
 * The underlying form can be accessed by expicitly calling $builder->getForm() or
 * by directly calling the forms method (which is, in case of populate() the preferred way)
 * like: $builder->getElements()
 *
 * @method \Zend_Form_Element getElement(string $name)
 * @method \Zend_Form addElement(\string $element, string $name = null, array $options = null)
 * @method \Zend_Form setView(\Zend_View $view)
 * @package Icinga\Form
 */
class Builder
{
    const CSRF_ID = "icinga_csrf_id";

    /**
     * @var \Zend_Form
     */
    private $form;

    /**
     * @var null
     */
    private $boundModel = null;

    /**
     * @var bool
     */
    private $disableCSRF = false;

    /**
    *   Constructrs a new Formbuilder, containing an empty form if no 
    *   $form parameter is given or the Zend form from the $form parameter.
    *   
    *   @param  \Zend_Form  $form       The form to use with this Builder
    *   @param  Array  $options         an optional array of Options:
    *               - CSRFProtection    true to add a crsf token to the
    *                                   form (default), false to remove it
    *               - model             An referenced array or object to use 
    *                                   for value binding 
    **/
    public function __construct(\Zend_Form $form = null, array $options = array())
    {
        if ($form === null) {
            $myModel = array(
                "username" => "",
                "password" => ""
            );

            $form = new \Zend_Form();
        }
            
        $this->setZendForm($form);

        if (isset($options["CSRFProtection"])) {
            $this->disableCSRF  = !$options["CSRFProtection"];
        }
        if (isset($options["model"])) {
            $this->bindToModel($options["model"]);
        }
    }

    /**
     * Setter for Zend_Form
     * @param \Zend_Form $form
     */
    public function setZendForm(\Zend_Form $form)
    {
        $this->form = $form;
    }

    /**
     * Getter for Zent_Form
     * @return \Zend_Form
     */
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

    /**
     * Add elements to form
     * @param array $elements
     */
    public function addElementsFromConfig(array $elements)
    {
        foreach ($elements as $key => $values) {
            $this->addElement($values[0], $key, $values[1]);
        }
    }

    /**
     * Quick add elements to a new builder instance
     * @param array $elements
     * @param array $options
     * @return Builder
     */
    public static function fromArray(array $elements, $options = array())
    {
        $builder = new Builder(null, $options);
        $builder->addElementsFromConfig($elements);
        return $builder;
    }

    /**
     * Test that the form is valid
     *
     * @param array $data
     * @return bool
     */
    public function isValid(array $data = null)
    {
        if ($data === null) {
            $data = $_POST;
        }
        return $this->hasValidToken() && $this->form->isValid($data);
    }

    /**
     * Test if the form was submitted
     * @param string $btnName
     * @return bool
     */
    public function isSubmitted($btnName = 'submit')
    {
        $btn = $this->getElement($btnName);
        if (!$btn || !isset($_POST[$btnName])) {
            return false;
        }
        return $_POST[$btnName] === $btn->getLabel();
    }

    /**
     * Render the form
     * @return string
     */
    public function render()
    {
        return $this->getForm()->render();
    }

    public function __toString()
    {
        return $this->getForm()->__toString();
    }

    /**
     * Enable CSRF token field
     */
    public function enableCSRF()
    {
        $this->disableCSRF = false;
    }

    /**
     * Disable CSRF token field
     */
    public function disableCSRF()
    {
        $this->disableCSRF = true;
        $this->form->removeElement(self::CSRF_ID);
        $this->form->removeElement(self::CSRF_ID."_seed");
    }

    /**
     * Add CSRF field to form
     */
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

    /**
     * Bind model to a form
     * @param $model
     */
    public function bindToModel(&$model)
    {
        $this->boundModel = &$model;
    }

    /**
     * Repopulate
     */
    public function repopulate()
    {
        if (!empty($_POST)) {
            $this->populate($_POST);
        }
    }

    /**
     * Populate form
     * @param $data
     * @param bool $ignoreModel
     * @throws \InvalidArgumentException
     */
    public function populate($data, $ignoreModel = false)
    {
        if (is_array($data)) {
            $this->form->populate($data);
        } elseif (is_object($data)) {
            $this->populateFromObject($data);
        } else {
            throw new \InvalidArgumentException("Builder::populate() expects and object or array, $data given");
        }
        if ($this->boundModel === null || $ignoreModel) {
            return;
        }
        $this->updateModel();
        
    }

    /**
     * Populate form object
     * @param $data
     */
    private function populateFromObject($data)
    {
        /** @var \Zend_Form_Element $element */

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

    /**
     * Update model instance
     */
    public function updateModel()
    {
        if (is_array($this->boundModel)) {
            $this->updateArrayModel();
        } elseif (is_object($this->boundModel)) {
            $this->updateObjectModel();
        }
    }

    /**
     * Updater for objects
     */
    private function updateObjectModel()
    {
        /** @var \Zend_Form_Element $element */

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

    /**
     * Updater for arrays
     */
    private function updateArrayModel()
    {
        /** @var \Zend_Form_Element $element */

        foreach ($this->form->getElements() as $name => $element) {
            if (isset($this->boundModel[$name])) {
                $this->boundModel[$name] = $element->getValue();
            }
        }
    }

    /**
     * Synchronize model with form
     */
    public function syncWithModel()
    {
        $this->populate($this->boundModel, true);
    }

    /**
     * Magic caller, pass through method calls to form
     * @param $fn
     * @param array $args
     * @return mixed
     * @throws \BadMethodCallException
     */
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
     * @return bool
     */
    public function hasValidToken($maxAge = 600, $sessionId = null)
    {
        if ($this->disableCSRF) {
            return true;
        }
        
        if ($this->getForm()->getElement(self::CSRF_ID) == null) {
            return false;
        }

        $sessionId = $sessionId ? $sessionId : session_id();
        $seed = $this->getForm()->getElement(self::CSRF_ID.'_seed')->getValue();

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
     * @return array
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
