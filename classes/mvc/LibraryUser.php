<?php

class LibraryUser extends User {
    
    public $access = [];
    
    public function __construct($login = null, $session = null, $date = null) {
        parent::__construct($login, $session, $date = null);
        $context = (new BookHasContextEntity())->retrieve();
        if ($context) {
            foreach ($context as $entry) {
                foreach (Zord::getConfig('role') as $role => $privileges) {
                    if ($this->hasRole($role, $entry->context)) {
                        foreach ($privileges as $privilege) {
                            $this->access[$entry->book][$privilege] = true;
                        }
                    }
                }
            }
        }
    }
    
    public function hasAccess($isbn, $privilege) {
        return isset($this->access[$isbn][$privilege]) && $this->access[$isbn][$privilege] === true;
    }
}

?>