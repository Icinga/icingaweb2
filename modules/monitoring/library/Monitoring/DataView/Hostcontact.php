<?php
/* Icinga Web 2 | (c) 2018 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

class Hostcontact extends Contact
{
    public function getColumns()
    {
        return [
            'contact_name',
            'contact_alias',
            'contact_email',
            'contact_pager'
        ];
    }
}
