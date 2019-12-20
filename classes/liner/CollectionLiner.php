<?php

class CollectionLiner extends Liner {
    
    public function getLocale() {
        return [];
    }
    
    public function sortValue($shelf, $book) {
        $number = 0;
        if (isset($book['metadata']['collection_number'])) {
            $number = $book['metadata']['collection_number'];
            if (!is_int($number)) {
                $number = Library::roman2number($number);
            }
        }
        return $number;
    }
    
    public function store($book) {
        $stored = false;
        if (isset($book['metadata']['relation'])) {
            foreach(explode(',',$book['metadata']['relation']) as $collection) {
                $stored = true;
                $this->locale[$collection] = $collection;
                $this->shelves[$collection]['books'][] = $book;
            }
        }
        return $stored;
    }
}

?>