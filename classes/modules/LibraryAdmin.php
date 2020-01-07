<?php

class LibraryAdmin extends StoreAdmin {
    
    protected function deletePaths($isbn) {
        $metadata = Store::data($isbn, 'meta', 'array');
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