<?php

class CategoryLiner extends Liner {
    
    public function getLocale() {
        return Zord::value('category', $this->context);
    }
    
    public function sortValue($shelf, $book) {
        return $book['number'];
    }
    
    public function store($book) {
        $stored = false;
        if (isset($book['category']) && !empty($book['category'])) {
            foreach($book['category'] as $category) {
                if (isset($this->locale[$category])) {
                    $this->shelves[$category]['books'][] = $book;
                    $stored = true;
                    break;
                }
            }
        }
        return $stored;
    }
}

?>