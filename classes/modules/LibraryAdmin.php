<?php

class LibraryAdmin extends StoreAdmin {
    
    public function publish() {
        if (isset($this->params['name']) &&
            isset($this->params['books'])) {
            $name = $this->params['name'];
            $books = Zord::objectToArray(json_decode($this->params['books']));
            (new BookHasContextEntity())->delete([
                'many' => true,
                'where' => ['context' => $name]
            ]);
            foreach ($books as $book) {
                if ($book['status'] !== 'del') {
                    (new BookHasContextEntity())->create([
                        'book'    => $book['isbn'],
                        'context' => $name,
                        'status'  => $book['status']
                    ]);
                } else if ($this->user->isManager()) {
                    (new BookEntity())->delete($book['isbn'], true);
                    foreach($this->deletePaths($book['isbn']) as $path) {
                        Zord::deleteRecursive(DATA_FOLDER.$path);
                    }
                }
            }
        }
        return $this->index('publish');
    }
    
    protected function deletePaths($isbn) {
        $metadata = Store::data($isbn, 'metadata.json', 'array');
        $epub = isset($metadata['epub']) ? $metadata['epub'] : $isbn;
        return [
            'books'.DS.$isbn.'.xml',
            'epub'.DS.$epub.'.epub',
            'medias'.DS.$isbn,
            'zoom'.DS.$isbn,
            'zord'.DS.$isbn,
        ];
    }
    
}

?>