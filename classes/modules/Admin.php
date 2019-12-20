<?php

class Admin extends Module {
        
    public function index($current = null, $models = array()) {
        if (!$this->user->hasRole('admin', $this->context)) {
            return $this->redirect($this->baseURL, true);
        }
        $tabs = Zord::value('admin', 'tabs');
        $context = [
            'zord'    => '*',
            'context' => $this->context
        ];
        foreach ($tabs as $name => $level) {
            if (!$this->user->hasRole('admin', $context[$level])) {
                unset($tabs[$name]);
            }
        }
        if (isset($this->params['tab'])) {
            $current = $this->params['tab'];
        }
        $tabs = array_keys($tabs);
        if (!isset($current)) {
            $current = $tabs[0];
        }
        $this->addScript('/library/js/admin/'.$current.'.js');
        return $this->page('admin', array_merge($models, [
            'tabs'    => $tabs,
            'current' => $current
        ]));
    }
    
    public function import() {
        $this->prepare();
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
    
    public function account() {
        $result = [];
        if (isset($this->params['operation']) && 
            isset($this->params['login']) &&
            isset($this->params['name']) && 
            isset($this->params['email'])) {
            $operation = $this->params['operation'];
            $login = $this->params['login'];
            $name = $this->params['name'];
            $email = $this->params['email'];
            $entity = new UserEntity();
            if ($login && $operation) {
                switch ($operation) {
                    case 'create': {
                        $code = User::crypt($login.microtime());
                        $entity->create([
                            'login' => $login,
                            'activate' => $code,
                            'name' => $name,
                            'email' => $email
                        ]);
                        $result['mail'] = $this->sendActivation($email, $name, $code);
                        break;
                    }
                    case 'update': {
                        $entity->update($login, [
                            'name' => $name,
                            'email' => $email
                        ]);
                        break;
                    }
                    case 'delete': {
                        $entity->delete($login);
                        $criteria = [
                            'where' => array('user' => $login),
                            'many' => true
                        ];
                        foreach (['Role','Address','Query','Session'] as $relation) {
                            $class = 'UserHas'.$relation.'Entity';
                            $entity = new $class();
                            $entity->delete($criteria);
                        }
                        break;
                    }
                    case 'profile': {
                        $result = $this->user($login);
                        break;
                    }
                }
            }
        }
        return $this->index('users', $result);
    }
    
    public function profile() {
        $result = [];
        if (isset($this->params['user']) &&
            isset($this->params['roles']) &&
            isset($this->params['ips'])) {
            $login = $this->params['user'];
            $criteria = [
                'where' => ['user' => $login],
                'many' => true
            ];
            (new UserHasRoleEntity())->delete($criteria);
            (new UserHasAddressEntity())->delete($criteria);
            $roles = Zord::objectToArray(json_decode($this->params['roles']));
            foreach ($roles as $role) {
                (new UserHasRoleEntity())->create($role);
            }
            $ips = Zord::objectToArray(json_decode($this->params['ips']));
            $user_ips = array();
            foreach ($ips as $entry) {
                $entryOK = true;
                foreach (Zord::explodeIP($entry['ip']) as $ip) {
                    $other = UserHasAddressEntity::find($ip);
                    if ($entry['include'] && $other) {
                        $entryOK = false;
                        $result['others'][] = [((new UserEntity())->retrieve($other->user)->name).' ('.$other->user.')', $ip];
                        break;
                    }
                }
                if ($entryOK) {
                    foreach (Zord::explodeIP($entry['ip']) as $ip) {
                        (new UserHasAddressEntity())->create([
                            'user'    => $login,
                            'ip'      => $ip,
                            'mask'    => (!empty($entry['mask']) || $entry['mask'] == 0) ? $entry['mask'] : 32,
                            'include' => $entry['include'] ? 1 : $entry['include']
                        ]);
                    }
                    $user_ips[] = ($entry['include'] ? '' : '~').$entry['ip'].((!empty($entry['mask']) || $entry['mask'] == 0) ? '/'.$entry['mask'] : '');
                }
            }
            (new UserEntity())->update($login, ['ips' => implode(',', $user_ips)]);
            $result = array_merge($result, $this->user($login));
        }
        return $this->index('users', $result);
    }
    
    public function context() {
        $result = [];
        if (isset($this->params['operation']) &&
            isset($this->params['name']) &&
            isset($this->params['title'])) {
            $operation = $this->params['operation'];
            $name = $this->params['name'];
            $title = $this->params['title'];
            $context = Zord::getConfig('context');
            switch ($operation) {
                case 'create': {
                    if (!isset($context[$name])) {
                        $context[$name]['title'][$this->lang] = $title;
                    } else {
                        $result['message'][] = 'context existant';
                    }
                    break;
                }
                case 'update': {
                    if (isset($context[$name])) {
                        $context[$name]['title'][$this->lang] = $title;
                    } else {
                        $result['message'][] = 'context inexistant';
                    }
                    break;
                }
                case 'delete': {
                    if (isset($context[$name])) {
                        unset($context[$name]);
                    } else {
                        $result['message'][] = 'context inexistant';
                    }
                    break;
                }
                case 'publish': {
                    $result = $this->config($name);
                    break;
                }
            }
            Zord::saveConfig('context', $context);
        }
        return $this->index('publish', $result);
    }
    
    public function publish() {
        $result = [];
        if (isset($this->params['name']) &&
            isset($this->params['urls']) &&
            isset($this->params['books'])) {
            $name = $this->params['name'];
            $urls = Zord::objectToArray(json_decode($this->params['urls']));
            $file = Zord::getComponentPath('config'.DS.'context.json');
            $context = Zord::arrayFromJSONFile($file);
            $context[$name]['url'] = [];
            foreach ($urls as $url) {
                $context[$name]['url'][] = $url;
            }
            file_put_contents($file, Zord::json_encode($context));
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
                    foreach (['Part','Context','Facet'] as $relation) {
                        $entity = 'BookHas'.$relation.'Entity';
                        (new $entity())->delete([
                            'many'   => true,
                            'where' => ['book' => $book['isbn']]
                        ]);
                    }
                    $metadata = Library::data($book['isbn'], 'meta', 'array');
                    $eanEPUB = isset($metadata['epub']) ? $metadata['epub'] : $book['isbn'];
                    $paths = [
                        'books'.DS.$book['isbn'].'.xml',
                        'epub'.DS.$eanEPUB.'.epub',
                        'medias'.DS.$book['isbn'],
                        'zoom'.DS.$book['isbn'],
                        'zord'.DS.$book['isbn'],
                    ];
                    foreach($paths as $path) {
                        Zord::deleteRecursive(DATA_FOLDER.$path);
                    }
                }
            }
            $result = $this->config($name);
        }
        return $this->index('publish', $result);
    }
    
    private function user($login) {
        $result = [];
        $result['user'] = new User($login);
        $result['roles'] = array_merge(Zord::getConfig('role'), ['*']);
        $result['context'] = array_merge(array_keys(Zord::getConfig('context')), ['*']);
        return $result;
    }
    
    private function config($name) {
        $result = [];
        $result['context'] = $name;
        $result['title']   = Zord::value('context', [$name,'title',$this->lang]);
        $urls = Zord::value('context', [$name,'url']);
        $result['urls'] = isset($urls) ? $urls : [];
        $entity = (new BookHasContextEntity())->retrieve([
            'many'  => true,
            "where" => ['context' => $name]
        ]);
        $status = [];
        foreach ($entity as $entry) {
            $status[$entry->book] = $entry->status;
        }
        $books = (new BookEntity())->retrieve();
        foreach($books as $book) {
            $result['books'][] = [
                'isbn'     => $book->ean,
                'status'   => isset($status[$book->ean]) ? $status[$book->ean] : 'no',
                'title'    => Library::title($book->title, $book->subtitle)
            ];
        }
        return $result;
    }
    
    private function prepare() {
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
    }
}

?>