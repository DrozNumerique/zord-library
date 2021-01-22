<?php

class SearchLiner extends Liner {
    
    public function getLocale() {
        $locale = [];
        foreach (Zord::value('portal', 'lang') as $lang) {
            $locale[$lang] = Zord::getLocale('search', $lang)->results;
        }
        return ['search' => $locale];
    }
    
    public function sortValue($shelf, $book) {
        return $book['isbn'];
    }
    
    public function store($book) {
        $this->shelves['search']['books'][] = $book;
        return true;
    }
}

?>