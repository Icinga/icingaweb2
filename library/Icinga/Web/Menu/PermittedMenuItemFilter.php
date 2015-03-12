<?php

namespace Icinga\Web\Menu;

use RecursiveFilterIterator;
use Icinga\Authentication\Manager;
use Icinga\Web\Menu;

class PermittedMenuItemFilter extends RecursiveFilterIterator
{
    /**
     * Accept menu items that are permitted to the user
     *
     * @return bool Whether the user has the required permission granted to display the menu item
     */
    public function accept()
    {
        $item = $this->current();
        /** @var Menu $item */
        if (($permission = $item->getPermission()) !== null) {
            $auth = Manager::getInstance();
            if (! $auth->isAuthenticated()) {
                // Don't accept menu item because user is not authenticated and the menu item requires a permission
                return false;
            }
            if (! $auth->getUser()->can($permission)) {
                return false;
            }
        }
        // Accept menu item if it does not require a permission
        return true;
    }
}
