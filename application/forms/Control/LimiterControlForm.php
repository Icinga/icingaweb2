<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Control;

use Icinga\Web\Form;

/**
 * Limiter control form
 */
class LimiterControlForm extends Form
{
    /**
     * CSS class for the limiter control
     *
     * @var string
     */
    const CSS_CLASS_LIMITER = 'limiter-control';

    /**
     * Default limit
     *
     * @var int
     */
    const DEFAULT_LIMIT = 50;

    /**
     * Selectable default limits
     *
     * @var int[]
     */
    public static $limits = array(
        10  => '10',
        25  => '25',
        50  => '50',
        100 => '100',
        500 => '500'
    );

    /**
     * Default limit for this instance
     *
     * @var int|null
     */
    protected $defaultLimit;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->setAttrib('class', static::CSS_CLASS_LIMITER);
    }

    /**
     * Get the default limit
     *
     * @return int
     */
    public function getDefaultLimit()
    {
        return $this->defaultLimit !== null ? $this->defaultLimit : static::DEFAULT_LIMIT;
    }

    /**
     * Set the default limit
     *
     * @param   int $defaultLimit
     *
     * @return  $this
     */
    public function setDefaultLimit($defaultLimit)
    {
        $defaultLimit = (int) $defaultLimit;

        if (! isset(static::$limits[$defaultLimit])) {
            static::$limits[$defaultLimit] = $defaultLimit;
        }

        $this->defaultLimit = $defaultLimit;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRedirectUrl()
    {
        return $this->getRequest()->getUrl()
            ->setParam('limit', $this->getElement('limit')->getValue())
            ->without('page');
    }

    /**
     * {@inheritdoc}
     */
    public function createElements(array $formData)
    {
        $options = static::$limits;
        $pageSize = (int) $this->getRequest()->getUrl()->getParam('limit', $this->getDefaultLimit());

        if (! isset($options[$pageSize])) {
            $options[$pageSize] = $pageSize;
        }

        $this->addElement(
            'select',
            'limit',
            array(
                'autosubmit'    => true,
                'escape'        => false,
                'label'         => '#',
                'multiOptions'  => $options,
                'value'         => $pageSize
            )
        );
    }

    /**
     * Limiter control is always successful
     *
     * @return bool
     */
    public function onSuccess()
    {
        return true;
    }
}
