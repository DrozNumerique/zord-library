<?php

class DefaultLiner extends Liner {
    
    public function getLocale() {
        return ['default' => Zord::value('context', [$this->context, 'title'])];
    }
    
    public function sortValue($shelf, $book) {
        return $shelf === 'new' ? -1 * $book['date'] : $book['isbn'];
    }
    
    public function store($book) {
        $this->shelves['default']['books'][] = $book;
        return true;
    }
}

?>