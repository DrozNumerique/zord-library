<?php

class LibraryUser extends User {
    
    public $access = [];
    
    public function __construct($login = null, $session = null) {
        parent::__construct($login, $session);
        if (!empty($this->roles)) {
            $context = (new BookHasContextEntity())->retrieve();
            if ($context) {
                foreach ($context as $entry) {
                    foreach (Zord::getConfig('role') as $role) {
                        if ($this->hasRole($role, $entry->context)) {
                            $this->access[$entry->book][$role] = true;
                        }
                    }
                }
            }
        }
    }
    
    public function hasAccess($isbn, $role) {
        return isset($this->access[$isbn][$role]) && $this->access[$isbn][$role] === true;
    }
}

?>