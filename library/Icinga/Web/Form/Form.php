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

use Icinga\Exception\ProgrammingError;

abstract class Form extends \Zend_Form
{
    /**
     * The form's request object
     * @var null
     */
    private $request = null;

    /**
     * The underlying model for this form
     * @var null
     */
    private $boundModel = null;

    /**
     * Whether this form should NOT add random generated "challenge" tokens that are associated
     * with the user's current session in order to prevent Cross-Site Request Forgery (CSRF).
     * It is the form's responsibility to verify the existence and correctness of this token
     * @var bool
     */
    private $tokenDisabled = false;

    /**
     * Name of the CSRF token element (used to create non-colliding hashes)
     * @var string
     */
    private $tokenElementName = 'CSRFToken';

    /**
     * Time to live for the CRSF token
     * @var int
     */
    private $tokenTimeout = 300;

    /**
     * @see Zend_Form::init
     */
    public function init()
    {
        if (!$this->tokenDisabled) {
            $this->initCsrfToken();
        }
        $this->create();
    }

    /**
     * Add elements to this form (used by extending classes)
     */
    abstract public function create();

    /**
     * Apply a request object wherewith the form can work
     *
     * @param $request The request object of a session
     * @return $this
     */
    public function setRequest($request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Check if the form's values are valid
     *
     * If $data is not given, $_POST is used.
     *
     * @param array $data
     * @return bool
     */
    /* @TODO: isValid cannot be overwritten this way. The CSRF functionality needs to
     *        be implemented as separate Zend_Form_Element with its own validator.
     *
    public function isValid(array $data = null)
    {
        if ($data === null) {
            $data = $_POST;
        }
        return $this->hasValidCsrfToken($this->tokenTimeout) && parent::isValid($data);
    }
    */

    /**
     * Check if the form was submitted
     *
     * @param string $elementName
     * @return bool
     */
    public function isSubmitted($elementName)
    {
        $element = $this->getElement($elementName);
        if ($element) {
            if ($this->request->isGet()) {
                return $this->request->getQuery($elementName) === $element->getLabel();
            } elseif ($this->request->isPost()) {
                return $this->request->getPost($elementName) === $element->getLabel();
            }
        }
        return false;
    }

    /**
     * Populate form with the given information
     *
     * $model might be an array or object where each key/property represents the name
     * of an element. In case its a object it might also have getter for each element.
     * (Naming: getExample) If $updateModel is false the underlying model, if any,
     * will not be updated.
     *
     * @param array|object $model
     * @param bool         $updateModel
     * @throws \InvalidArgumentException
     */
    public function populate($model, $updateModel = true)
    {
        if (is_array($model)) {
            parent::populate($model);
        } elseif (is_object($model)) {
            $this->populateFromObject($model);
        } else {
            throw new \InvalidArgumentException("Expected array or object. $model given");
        }
        if ($updateModel && $this->boundModel !== null) {
            $this->updateModel();
        }
    }

    /**
     * Populate form with the given object
     *
     * @param object $model
     */
    private function populateFromObject($model)
    {
        foreach ($this->getElements() as $name => $element) {
            if (isset($model->$name)) {
                $element->setValue($model->$name);
            } else {
                $getter = 'get' . ucfirst($name);
                if (method_exists($model, $getter)) {
                    $element->setValue($model->$getter());
                }
            }
        }
    }

    /**
     * Repopulate form with the current request
     */
    public function repopulate()
    {
        if ($this->request->isPost()) {
            $this->populate($this->request->getPost());
        } elseif ($this->request->isGet()) {
            $this->populate($this->request->getQuery());
        }
    }

    /**
     * Bind a model to this form
     *
     * $model might be an array or object where each key/property represents
     * the name of an element. In case its a object it might also have getter
     * and setter for each element. (Naming: getExample, setExample)
     *
     * @param array|object $model
     */
    public function bindToModel(&$model)
    {
        $this->boundModel = &$model;
    }

    /**
     * Synchronize form with model
     *
     * Feed the form's elements with default values by using the current model.
     *
     * @throws ProgrammingError
     */
    public function syncWithModel()
    {
        if ($this->boundModel === null) {
            throw new ProgrammingError('You need to bind a model to this form first');
        }
        $this->populate($this->boundModel, true);
    }

    /**
     * Update model with the form's values
     *
     * This is the inverse of syncWithModel(). Feed the
     * model with values from the form's elements.
     *
     * @throws ProgrammingError
     */
    public function updateModel()
    {
        if (is_array($this->boundModel)) {
            $this->updateArrayModel();
        } elseif (is_object($this->boundModel)) {
            $this->updateObjectModel();
        } else {
            throw new ProgrammingError('You need to bind a model to this form first');
        }
    }

    /**
     * Update model of type object
     */
    private function updateObjectModel()
    {
        foreach ($this->getElements() as $name => $element) {
            if (isset($this->boundModel->$name)) {
                $this->boundModel->$name = $element->getValue();
            } else {
                $setter = 'set' . ucfirst($name);
                if (method_exists($this->boundModel, $setter)) {
                    $this->boundModel->$setter($element->getValue());
                }
            }
        }
    }

    /**
     * Update model of type array
     */
    private function updateArrayModel()
    {
        foreach ($this->getElements() as $name => $element) {
            if (isset($this->boundModel[$name])) {
                $this->boundModel[$name] = $element->getValue();
            }
        }
    }

    /**
     * Enable CSRF counter measure
     */
    final public function enableCsrfToken()
    {
        $this->tokenDisabled = false;
    }

    /**
     * Disable CSRF counter measure and remove its field if already added
     */
    final public function disableCsrfToken()
    {
        $this->tokenDisabled = true;
        $this->removeElement($this->tokenElementName);
    }

    /**
     * Add CSRF counter measure field to form
     */
    final public function initCsrfToken()
    {
        if ($this->tokenDisabled || $this->getElement($this->tokenElementName)) {
            return;
        }
        list($seed, $token) = $this->generateCsrfToken($this->tokenTimeout);

        $this->addElement('hidden', $this->tokenElementName, array(
            'value'      => sprintf('%s\|/%s', $seed, $token),
            'decorators' => array('ViewHelper')
            )
        );
    }

    /**
     * Check whether the form's CSRF token-field has a valid value
     *
     * @param int    $maxAge    Max allowed token age
     * @param string $sessionId A specific session id
     *
     * @return bool
     */
    final private function hasValidCsrfToken($maxAge, $sessionId = null)
    {
        if ($this->tokenDisabled) {
            return true;
        }

        if ($this->getElement($this->tokenElementName) === null) {
            return false;
        }

        $elementValue = $this->getElement($this->tokenElementName)->getValue();
        list($seed, $token) = explode($elementValue, '\|/');

        if (!is_numeric($seed)) {
            return false;
        }

        $seed -= intval(time() / $maxAge) * $maxAge;
        $sessionId = $sessionId ? $sessionId : session_id();
        return $token === hash('sha256', $sessionId . $seed);
    }

    /**
     * Generate a new (seed, token) pair
     *
     * @param int    $maxAge    Max allowed token age
     * @param string $sessionId A specific session id
     *
     * @return array
     */
    final private function generateCsrfToken($maxAge, $sessionId = null)
    {
        $sessionId = $sessionId ? $sessionId : session_id();
        $seed = mt_rand();
        $hash = hash('sha256', $sessionId . $seed);
        $seed += intval(time() / $maxAge) * $maxAge;
        return array($seed, $hash);
    }
}
