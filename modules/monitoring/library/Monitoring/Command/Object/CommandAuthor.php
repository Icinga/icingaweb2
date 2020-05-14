<?php

/* Icinga Web 2 | (c) 2020 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Monitoring\Command\Object;

trait CommandAuthor
{
    /**
     * Author of the command
     *
     * @var string
     */
    protected $author;

    /**
     * Set the author
     *
     * @param   string $author
     *
     * @return  $this
     */
    public function setAuthor($author)
    {
        $this->author = (string) $author;
        return $this;
    }

    /**
     * Get the author
     *
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }
}
