<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup;

use LogicException;

abstract class Requirement
{
    /**
     * The state of this requirement
     *
     * @var bool
     */
    protected $state;

    /**
     * A descriptive text representing the current state of this requirement
     *
     * @var string
     */
    protected $stateText;

    /**
     * The descriptions of this requirement
     *
     * @var array
     */
    protected $descriptions;

    /**
     * The title of this requirement
     *
     * @var string
     */
    protected $title;

    /**
     * The condition of this requirement
     *
     * @var mixed
     */
    protected $condition;

    /**
     * Whether this requirement is optional
     *
     * @var bool
     */
    protected $optional;

    /**
     * The alias to display the condition with in a human readable way
     *
     * @var string
     */
    protected $alias;

    /**
     * The text to display if the given requirement is fulfilled
     *
     * @var string
     */
    protected $textAvailable;

    /**
     * The text to display if the given requirement is not fulfilled
     *
     * @var string
     */
    protected $textMissing;

    /**
     * Create a new requirement
     *
     * @param   array   $options
     *
     * @throws  LogicException  In case there exists no setter for an option's key
     */
    public function __construct(array $options = array())
    {
        $this->optional = false;
        $this->descriptions = array();

        foreach ($options as $key => $value) {
            $setMethod = 'set' . ucfirst($key);
            $addMethod = 'add' . ucfirst($key);
            if (method_exists($this, $setMethod)) {
                $this->$setMethod($value);
            } elseif (method_exists($this, $addMethod)) {
                $this->$addMethod($value);
            } else {
                throw LogicException('No setter found for option key: ' . $key);
            }
        }
    }

    /**
     * Set the state of this requirement
     *
     * @param   bool    $state
     *
     * @return  Requirement
     */
    public function setState($state)
    {
        $this->state = (bool) $state;
        return $this;
    }

    /**
     * Return the state of this requirement
     *
     * Evaluates the requirement in case there is no state set yet.
     *
     * @return  int
     */
    public function getState()
    {
        if ($this->state === null) {
            $this->state = $this->evaluate();
        }

        return $this->state;
    }

    /**
     * Set a descriptive text for this requirement's current state
     *
     * @param   string  $text
     *
     * @return  Requirement
     */
    public function setStateText($text)
    {
        $this->stateText = $text;
        return $this;
    }

    /**
     * Return a descriptive text for this requirement's current state
     *
     * @return  string
     */
    public function getStateText()
    {
        $state = $this->getState();
        if ($this->stateText === null) {
            return $state ? $this->getTextAvailable() : $this->getTextMissing();
        }
        return $this->stateText;
    }

    /**
     * Add a description for this requirement
     *
     * @param   string  $description
     *
     * @return  Requirement
     */
    public function addDescription($description)
    {
        $this->descriptions[] = $description;
        return $this;
    }

    /**
     * Return the descriptions of this wizard
     *
     * @return  array
     */
    public function getDescriptions()
    {
        return $this->descriptions;
    }

    /**
     * Set the title for this requirement
     *
     * @param   string  $title
     *
     * @return  Requirement
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Return the title of this requirement
     *
     * In case there is no title set the alias is returned instead.
     *
     * @return  string
     */
    public function getTitle()
    {
        if ($this->title === null) {
            return $this->getAlias();
        }

        return $this->title;
    }

    /**
     * Set the condition for this requirement
     *
     * @param   mixed   $condition
     *
     * @return  Requirement
     */
    public function setCondition($condition)
    {
        $this->condition = $condition;
        return $this;
    }

    /**
     * Return the condition of this requirement
     *
     * @return  mixed
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * Set whether this requirement is optional
     *
     * @param   bool    $state
     *
     * @return  Requirement
     */
    public function setOptional($state = true)
    {
        $this->optional = (bool) $state;
        return $this;
    }

    /**
     * Return whether this requirement is optional
     *
     * @return  bool
     */
    public function isOptional()
    {
        return $this->optional;
    }

    /**
     * Set the alias to display the condition with in a human readable way
     *
     * @param   string  $alias
     *
     * @return  Requirement
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
        return $this;
    }

    /**
     * Return the alias to display the condition with in a human readable way
     *
     * @return  string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Set the text to display if the given requirement is fulfilled
     *
     * @param   string  $textAvailable
     *
     * @return  Requirement
     */
    public function setTextAvailable($textAvailable) {
        $this->textAvailable = $textAvailable;
        return $this;
    }

    /**
     * Get the text to display if the given requirement is fulfilled
     *
     * @return  string
     */
    public function getTextAvailable() {
        return $this->textAvailable;
    }

    /**
     * Set the text to display if the given requirement is not fulfilled
     *
     * @param   string  $textMissing
     *
     * @return  Requirement
     */
    public function setTextMissing($textMissing) {
        $this->textMissing = $textMissing;
        return $this;
    }

    /**
     * Get the text to display if the given requirement is not fulfilled
     *
     * @return  string
     */
    public function getTextMissing() {
        return $this->textMissing;
    }

    /**
     * Evaluate this requirement and return whether it is fulfilled
     *
     * @return  bool
     */
    abstract protected function evaluate();

    /**
     * Return whether the given requirement equals this one
     *
     * @param   Requirement     $requirement
     *
     * @return  bool
     */
    public function equals(Requirement $requirement)
    {
        if ($requirement instanceof static) {
            return $this->getCondition() === $requirement->getCondition();
        }

        return false;
    }
}
