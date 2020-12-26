<?php

class LibraryAdmin extends StoreAdmin {
    
    protected function prepareImport($folder) {
        $dirs = glob($folder.'*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            $files = glob($dir.DS.'*');
            $same = false;
            foreach ($files as $file) {
                $new = $folder.basename($file);
                if (is_dir($file) && file_exists($new)) {
                    $same = true;
                    foreach (glob($file.DS.'*') as $sub) {
                        rename($sub, $new.DS.basename($sub));
                    }
                    rmdir($file);
                } else {
                    rename($file, $new);
                }
            }
            if (!$same) {
                rmdir($dir);
            }
        }
        $publish = [];
        foreach (array_keys(Zord::getConfig('context')) as $name) {
            if (isset($this->params[$name]) && $this->params[$name] !== 'no') {
                $publish[$name] = $this->params[$name];
            }
        }
        if (!empty($publish)) {
            file_put_contents($folder.'publish.json', Zord::json_encode($publish));
        }
    }
    
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
                        Zord::deleteRecursive(STORE_FOLDER.$path);
                    }
                    Store::deindex($book['isbn']);
                }
            }
        }
        return $this->index('publish');
    }
    
    protected function deletePaths($isbn) {
        $metadata = Library::data($isbn, 'metadata.json', 'array');
        $epub = isset($metadata['epub']) ? $metadata['epub'] : $isbn;
        return [
            'books'.DS.$isbn.'.xml',
            'epub'.DS.$epub.'.epub',
            'medias'.DS.$isbn,
            'zoom'.DS.$isbn,
            'library'.DS.$isbn,
        ];
    }
    
}

?>