<?php

class Library {
    
    public static function data($ean, $path = null, $format = 'path') {
        return Store::data(STORE_FOLDER.'library'.DS.$ean.DS.(isset($path) ? $path : ''), $format);
    }
    
    public static function books($context, $order = ['asc' => 'ean']) {
        $books = [];
        $entity = (new BookHasContextEntity())->retrieve([
            'many'  => true,
            "where" => ['context' => $context]
        ]);
        $status = [];
        foreach ($entity as $entry) {
            $status[$entry->book] = $entry->status;
        }
        $entity = (new BookEntity())->retrieve([
            "many"  => true,
            "order" => $order
        ]);
        foreach($entity as $book) {
            $books[] = [
                'isbn'   => $book->ean,
                'status' => isset($status[$book->ean]) ? $status[$book->ean] : 'no',
                'title'  => self::title($book->title, $book->subtitle),
                'first'  => $book->first_published ?? 'undefined'
            ];
        }
        return $books;
    }
    
    public static function title($title, $subtitle = null, $maxlength = null, $separator = '. ') {
        if (is_array($title)) {
            if (isset($title['subtitle'])) {
                $subtitle = $title['subtitle'];
            }
            if (isset($title['title'])) {
                $title = $title['title'];
            } else {
                $title = null;
            }
        }
        $title = isset($title) ? $title : '';
        if (isset($subtitle) && !empty($subtitle)) {
            $title .= (empty($title) ? '' : $separator).$subtitle;
        }
        if (is_int($maxlength)) {
            $title = Zord::trunc($title, $maxlength);
        }
        return $title;
    }
    
	public static function listActors($actors, $type, $max = 3, $glue = null, $reverse = false) {
	    if ($reverse) {
	        foreach($actors as $index => $actor) {
	            $tokens = explode(',', $actor);
	            if (count($tokens) === 2) {
	                $actors[$index] = trim($tokens[1]).' '.trim($tokens[0]);
	            }
	        }
	    }
	    $tooMany = count($actors) > $max;
	    $end = '';
	    switch ($type) {
	        case 'TEI': {
	            $glue = $glue ?? ';';
	            $end = ' <hi rend="italic">et al.</hi>';
	            break;
	        }
	        case 'HTML': {
	            $glue = $glue ?? ';<br/>';
	            $end = ' <i>et al.</i>';
	            break;
	        }
	    }
	    $result = implode($glue, $tooMany ? array_slice($actors, 0, $max) : $actors);
	    return $result.($tooMany ? $end : '');
	}
	
	public static function xmlspecialchars($val) {
	    return trim(str_replace(array('&','>','<','"'), array('&#38;','&#62;','&#60;','&#34;'), $val));
	}
	
	public static function iso639($lang, $from, $to) {
	    $config = Zord::getConfig('iso639');
	    switch ($from) {
	        case 'name': {
	            switch ($to) {
	                case 'code2': {
	                    return isset($config['name_code2'][$lang]) ? $config['name_code2'][$lang] : null;
	                    break;
	                }
	                case 'code3': {
	                    return isset($config['name_code3'][$lang]) ? $config['name_code3'][$lang] : null;
	                    break;
	                }
	            }
	            break;
	        }
	        case 'code2': {
	            switch ($to) {
	                case 'name': {
	                    return isset($config['code2_name'][$lang]) ? $config['code2_name'][$lang] : null;
	                    break;
	                }
	                case 'code3': {
	                    if (isset($config['code2_name'][$lang]) && $config['code2_name'][$lang]) {
	                        return isset($config['name_code3'][$config['code2_name'][$lang]]) ? $config['name_code3'][$config['code2_name'][$lang]] : null;
	                    } else {
	                       return null; 
	                    }
	                    break;
	                }
	            }
	            break;
	        }
	        case 'code3': {
	            switch ($to) {
	                case 'name': {
	                    return isset($config['code3_name'][$lang]) ? $config['code3_name'][$lang] : null;
	                    break;
	                }
	                case 'code2': {
	                    if (isset($config['code3_name'][$lang]) && $config['code3_name'][$lang]) {
	                        return isset($config['name_code2'][$config['code3_name'][$lang]]) ? $config['name_code2'][$config['code3_name'][$lang]] : null;
	                    } else {
	                        return null;
	                    }
	                    break;
	                }
	            }
	            break;
	        }
	    }
	}

	public static function year($date) {
	    return substr(explode('-', $date.'')[0], 0, 4);
	}
	
	public static function compact($title) {
	    return trim(
	        preg_replace("/\s\./", ".",
	            preg_replace("/\s+/", " ", $title)
	        )
	    );
	}
	
	public static function reference($isbn, $page = '') {
	    $metadata = self::data($isbn, 'metadata.json', 'array');
	    $reference = [
	        'type' => 'book',
	        'id'   => uniqid('Zref_'),
	        'ean'  => $isbn
	    ];
	    if (isset($metadata['title'])) {
	        $reference['title'] = $metadata['title'];
	        if (isset($metadata['subtitle'])) {
	            $reference['title'] .= '. '.$metadata['subtitle'];
	        }
	    }
	    if (isset($metadata['publisher'])) {
	        $reference['publisher'] = $metadata['publisher'];
	    }
	    if (isset($metadata['date'])) {
	        $reference['issued'] = ["date-parts" => [[$metadata['date']]]];
	    }
	    if (isset($metadata['language'])) {
	        if (strlen($metadata['language']) > 2) {
	            $reference['language'] = self::iso639($metadata['language'], 'code3', 'code2');
	        } else {
	            $reference['language'] = $metadata['language'];
	        }
	    }
	    if (isset($metadata['isbn'])) {
	        $reference['ISBN'] = '"'.$metadata['isbn'].'"';
	    }
	    if (isset($metadata['uri'])) {
	        $reference['zord_URL'] = $metadata['uri'];
	    }
	    if (isset($metadata['pubplace'])) {
	        $reference['publisher-place'] = $metadata['pubplace'];
	    }
	    foreach(['creator' => 'author', 'editor' => 'editor'] as $meta => $ref) {
	        if (isset($metadata[$meta])) {
	            $reference[$ref] = [];
	            foreach ($metadata[$meta] as $actor){
	                $actor = explode(',', $actor);
	                $person = ['family' => trim($actor[0])];
	                if (isset($actor[1])) {
	                    $person['given'] = trim($actor[1]);
	                }
	                $reference[$ref][] = $person;
	            }
	        }
	    }
	    $reference['page'] = $page;
	    return $reference;
	}
	
	public static function categories($context, $list) {
	    $categories = [];
	    foreach ($list as $category) {
	        $categories[] = Zord::value('category', [$context,$category]);
	    }
	    return implode(',', $categories);
	}
	
	public static function facets($context, $type) {
	    $facets = [];
	    foreach (self::inContext($context, 'BookHasFacetEntity', "BookHasFacetEntity.facet = '".$type."'") as $facet) {
	        $facets[] = $facet->value;
	    }
	    return $facets;
	}
	
	public static function inContext($context, $type, $where = null) {
	    return (new $type())->retrieve([
	        'many'  => true,
	        'join'  => 'BookHasContextEntity',
	        'where' => [
	            'raw'        => 'BookHasContextEntity.context = ?'.(isset($where) ? ' AND '.$where : ''),
	            'parameters' => [$context]
	        ]
	    ]);
	}
	
	public static function delete($book, $paths = null) {
	    (new BookHasContextEntity())->delete(["many" => true, "where" => ['book' => $book]]);
	    (new BookEntity())->delete($book, true);
	    foreach($paths ?? self::deletePaths($book) as $path) {
	        Zord::deleteRecursive(STORE_FOLDER.$path);
	    }
	    Store::deindex($book);
	}
	
	public static function deletePaths($book) {
	    $metadata = Library::data($book, 'metadata.json', 'array');
	    $epub = isset($metadata['epub']) ? $metadata['epub'] : $book;
	    return [
	        'books'.DS.$book.'.xml',
	        'epub'.DS.$epub.'.epub',
	        'medias'.DS.$book,
	        'zoom'.DS.$book,
	        'library'.DS.$book,
	    ];
	}
	
	public static function isCounter($user, $context) {
	    return $user->isConnected() && self::isReader($user, $context);
	}
	
	public static function isReader($user, $context) {
	    $entities = (new UserHasRoleEntity())->retrieve([
	        'many'  => true,
	        'where' => [
	            'user' => $user->login,
	            'role' => ['in' => ['*','reader','researcher']]
	        ]
	    ]);
	    foreach ($entities as $entity) {
	        foreach (Zord::value('context', [$entity->context, 'from']) ?? [] as $_context) {
	            if ($_context === $context) {
	                return true;
	            }
	        }
	    }
	    return $user->hasRole('reader', $context) || $user->hasRole('researcher', $context);
	}
	
	public static function postPublish($book) {
	    $_book = (new BookEntity())->retrieveOne($book);
	    if ($_book !== false && empty($_book->first_published)) {
	        (new BookEntity())->update($book, [
	            'first_published' => date('Y-m-d')
	        ]);
	        return $_book;
	    }
	    return false;
	}
}
