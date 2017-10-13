<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Data\Storage;

use Icinga\Exception\NotReadableError;
use Icinga\Exception\NotWritableError;

interface ItemInterface
{
    /**
     * Get the bucket containing this item
     *
     * @return BucketInterface
     */
    public function getContainer();

    /**
     * Load this item's data
     *
     * @return  string|null
     *
     * @throws  NotReadableError
     */
    public function readData();

    /**
     * Overwrite this item's data
     *
     * @param   string|null $data
     *
     * @return  $this
     *
     * @throws  NotWritableError
     */
    public function updateData($data);
}
