<?php

class LibraryUser extends User {
    
    public $access = [];
    
    public function __construct($login = null, $session = null, $date = null) {
        parent::__construct($login, $session, $date = null);
        if (!empty($this->roles)) {
            $context = (new BookHasContextEntity())->retrieve();
            if ($context) {
                foreach ($context as $entry) {
                    foreach (array_keys(Zord::getConfig('role')) as $role) {
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