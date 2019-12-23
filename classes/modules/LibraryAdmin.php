<?php

class LibraryAdmin extends Admin {
    
    public function import() {
        $tmp = $_FILES['file']['tmp_name'];
        $file = $_FILES['file']['name'];
        $type = pathinfo($file, PATHINFO_EXTENSION);
        $name = basename($file);
        $folder = Library::getImportFolder();
        Zord::resetFolder($folder);
        move_uploaded_file($tmp, $folder.$name);
        if ($type == 'zip') {
            $zip = new ZipArchive();
            if ($zip->open($folder.$name) === true) {
                $zip->extractTo($folder);
                $zip->close();
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
            }
        }
        $publish = [];
        foreach (array_keys(Zord::getConfig('context')) as $name) {
            if (isset($this->params[$name]) && $this->params[$name] !== 'no') {
                $publish[$name] = $this->params[$name];
            }
        }
        if (!empty($publish)) {
            file_put_contents(Library::getImportFolder().'publish.json', Zord::json_encode($publish));
        }
        $parameters = Zord::objectToArray(json_decode($this->params['parameters']));
        return ['pid' => ProcessExecutor::start(Zord::getClassName('Import'), $this->user, $this->lang, $parameters)];
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
                    $metadata = Library::data($book['isbn'], 'meta', 'array');
                    $epub = isset($metadata['epub']) ? $metadata['epub'] : $book['isbn'];
                    $paths = [
                        'books'.DS.$book['isbn'].'.xml',
                        'epub'.DS.$epub.'.epub',
                        'medias'.DS.$book['isbn'],
                        'zoom'.DS.$book['isbn'],
                        'zord'.DS.$book['isbn'],
                    ];
                    foreach($paths as $path) {
                        Zord::deleteRecursive(DATA_FOLDER.$path);
                    }
                }
            }
        }
        return $this->index('publish');
    }
    
    public static function books($context) {
        $books = [];
        $entity = (new BookHasContextEntity())->retrieve([
            'many'  => true,
            "where" => ['context' => $context]
        ]);
        $status = [];
        foreach ($entity as $entry) {
            $status[$entry->book] = $entry->status;
        }
        $entity = (new BookEntity())->retrieve();
        foreach($entity as $book) {
            $books[] = [
                'isbn'     => $book->ean,
                'status'   => isset($status[$book->ean]) ? $status[$book->ean] : 'no',
                'title'    => Library::title($book->title, $book->subtitle)
            ];
        }
        return $books;
    }
}

?>