<?php

class LibraryPortal extends Portal {
    
    public function home() {
        return $this->page('home', (new Book($this->controler))->classify($this->params['year'] ?? false));
    }
}

?>