<?php

class LibraryAdmin extends StoreAdmin {
    
    protected function prepareIndex($current, $models) {
        $models = parent::prepareIndex($current, $models);
        if ($current == 'publish') {
            $models = Zord::array_merge($models, $this->dataPublish());
        }
        return $models;
    }
    
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
    
    protected function books() {
        return $this->view('/portal/page/admin/publish/books', $this->cursor($this->dataPublish()), 'text/html;charset=UTF-8', false, false, 'admin');
    }
    
    protected function dataPublish() {
        $limit = Zord::value('admin', ['publish','list','limit']);
        $title = $this->params['title'] ?? null;
        $context = $this->params['ctx'] ?? $this->context;
        $only = $this->params['only'] ?? 'false';
        $new = $this->params['new'] ?? 'false';
        $offset = $this->params['offset'] ?? 0;
        $order = $this->params['order'] ?? 'ean';
        $direction = $this->params['direction'] ?? 'asc';
        $books = Library::books($context, [$direction => $order]);
        if (!empty($title)) {
            $books = array_filter($books, function($book) use ($title) {return strpos(strtolower($book['title']), strtolower($title)) !== false;});
        }
        if ($only == 'true') {
            $books = array_filter($books, function($book) {return $book['status'] !== 'no';});
        }
        if ($new == 'true') {
            $books = array_filter($books, function($book) {return $book['status'] !== 'yes';});
        }
        if ($order == 'title') {
            Zord::sort($books, true, function($comparable) {
                return Zord::collapse($comparable['title']);
            });
            if ($direction == 'desc') {
                $books = array_reverse($books);    
            }
        }
        $count = count($books);
        $index = [];
        foreach ($books as $book) {
            $index[] = $book[$order == 'ean' ? 'isbn' : $order];
        }
        $books = array_slice($books, $offset, $limit);
        return [
            'list'      => 'books',
            'count'     => $count,
            'order'     => $order,
            'direction' => $direction,
            'limit'     => $limit,
            'offset'    => $offset,
            'index'     => $index,
            'data'      => $books
        ];
    }
    
    public function publish() {
        if (isset($this->params['name']) &&
            isset($this->params['book']) &&
            isset($this->params['status'])) {
            $name = $this->params['name'];
            $book = $this->params['book'];
            $status = $this->params['status'];
            $data = [
                'context' => $name,
                'book'    => $book
            ];
            $entity = (new BookHasContextEntity())->retrieve(['where' => $data]);
            $change = false;
            switch ($status) {
                case 'yes': 
                case 'new': {
                    if ($entity == false) {
                        (new BookHasContextEntity())->create(array_merge($data,['status' => $status]));
                        $change = true;
                    } else if ($entity->status !== $status) {
                        (new BookHasContextEntity())->update(["many" => true, "where" => $data], ['status' => $status]);
                        $change = true;
                    }
                    break;
                }
                case 'no': {
                    if ($entity !== false) {
                        (new BookHasContextEntity())->delete(["many" => true, "where" => $data]);
                        $change = true;
                    }
                    break;
                }
                case 'del': {
                    if ($this->user->isManager()) {
                        Library::delete($book, $this->deletePaths($book));
                        $change = true;
                    }
                    break;
                }
            }
        }
        return ['change' => $change];
    }
    
    protected function deletePaths($book) {
        return Library::deletePaths($book);
    }
    
}

?>