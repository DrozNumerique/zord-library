<?php

class CollectionLiner extends Liner {
    
    public function getLocale() {
        return [];
    }
    
    public function sortValue($shelf, $book) {
        $number = 0;
        $metadata = Library::data($book['isbn'], 'metadata.json', 'array');
        if (isset($metadata['collection_number'])) {
            $number = $metadata['collection_number'];
            if (!is_int($number)) {
                $number = Zord::roman2number($number);
            }
        }
        return $number;
    }
    
    public function store($book) {
        $stored = false;
        $metadata = Library::data($book['isbn'], 'metadata.json', 'array');
        if (isset($metadata['relation'])) {
            foreach(explode(',',$metadata['relation']) as $collection) {
                $stored = true;
                $this->locale[$collection] = $collection;
                $this->shelves[$collection]['books'][] = $book;
            }
        }
        return $stored;
    }
}

?>