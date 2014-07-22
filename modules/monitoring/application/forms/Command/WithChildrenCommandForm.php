<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Command;

/**
 * Base class for command forms which allow to propagate the command to child objects too
 */
abstract class WithChildrenCommandForm extends CommandForm
{
    /**
     * Whether to include all objects beyond as well
     * @var bool
     */
    private $withChildren = false;

    /**
     * Setter for withChildren
     *
     * @param bool $flag
     */
    public function setWithChildren($flag = true)
    {
        $this->withChildren = $flag;
    }

    /**
     * Getter for withChildren
     *
     * @return bool
     */
    public function getWithChildren()
    {
        return $this->withChildren;
    }
}
