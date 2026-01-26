<?php

class Book extends Module {
    
    public function models($models) {
        $models = parent::models($models);
        if (isset($models['book'])) {
            $book = $models['book'];
            $models['portal']['title'] = $book['TITLE'];
            $parts = Library::data($book['ISBN'], 'parts.json', 'array');
            if (count($book['PARTS']) > 1) {
                $index = 0;
                foreach($book['PARTS'] as $variant) {
                    foreach ($parts as $part) {
                        if ($part['name'] == $variant) {
                            $models['portal']['visavis'][] = [
                                'id'    => $part['id'],
                                'label' => $part['synch'],
                                'class' => $index < 2 ? 'on' : 'off'
                            ];
                            $index++;
                        }
                    }
                }
            }
            $models['portal']['ariadne'] = $this->ariadne($book['ISBN'], $book['PART']);
            if (isset($book['message'])) {
                $models['portal']['message'] = [
                    'class'   => $book['message']['class'],
                    'content' => $book['message']['content']
                ];
            }
        }
        return $models;
    }
    
    public function openurl() {
        if (isset($this->params['id'])) {
            $isbn = $this->params['id'];
            $name = Library::context($isbn, $this->user);
            if ($name) {
                $this->params['isbn'] = $isbn;
                $this->params['ctx'] = $name;
                return $this->show();
            }
        }
        if (isset($this->params['cover'])) {
            $cover = Store::resource('medias', $this->params['cover'], ['epub_cover','titlepage','frontcover']);
            if ($cover) {
                return $this->send(STORE_FOLDER.$cover);
            }
        }
        return $this->error(404);
    }
    
    public function context() {
        $isbn = $this->params['isbn'] ?? null;
        if (empty($isbn)) {
            return $this->error(404);
        }
        $name = Library::context($isbn, $this->user);
        if (empty($name)) {
            return $this->error(404);
        }
        return Zord::getContextURL($name);
    }
    
    public function search() {
        $results = $this->classify($this->fetch());
        $fetch = $this->params['fetch'] ?? false;
        if ($fetch === 'true') {
            return $this->view('/portal/widget/shelves', $results, 'text/html;charset=UTF-8', false, false);
        }
        $facets = [];
        foreach (Zord::value('search', 'facets') ?? [] as $type) {
            if (!empty(Library::facets($this->context, $type))) {
                $facets[] = $type;
            }
        }
        return $this->page('search', array_merge($results, ['pullout' => SEARCH_PULLOUT, 'facets' => $facets]));
    }
    
    public function style($scope, $obfuscator, $path) {
        return isset($obfuscator) ? '/build/'.$obfuscator->getCSS($scope) : $path.'/css/book/'.$scope.'.css';
    }
    
    public function styles($media, $obfuscator = null) {
        $styles = [];
        $scopes = array_merge(['common'], is_array($media) ? $media : [$media]);
        foreach ($scopes as $scope) {
            $styles[$scope] = $this->style($scope, $obfuscator, '/library');
        }
        foreach (Zord::getSkin($this->context)->book->styles ?? [] as $scope => $path) {
            $styles[$scope] = $this->style($scope, $obfuscator, $path);
        }
        return $styles;
    }
    
    public function include($part, $parts) {
        $_parts = [$part];
        foreach ($parts as $_part) {
            if ($_part['base'] === $part && ($_part['index'] ?? false)) {
                $_parts = array_merge($_parts, $this->include($_part['name'], $parts));
            }
        }
        return $_parts;
    }
    
    protected function readable($isbn) {
        return $this->user->hasAccess($isbn, 'read') && $this->access($isbn);
    }
    
    public function show() {
        $isbn = $this->either(null, 'isbn');
        if (isset($isbn)) {
            if (isset($this->params['ctx'])) {
                return $this->redirect(Zord::getContextURL($this->params['ctx'], 0, '/book/'.$isbn, $this->lang, $this->user->session), true);
            }
            if ($this->context === 'root') {
                $this->params['id'] = $isbn;
                return $this->openurl();
            }
            if (isset($this->params['xhr']) && $this->params['xhr']) {
                $path = '/book/'.$isbn;
                if (isset($this->params['part'])) {
                    $path = $path.'/'.$this->params['part'];
                }
                if (isset($this->params['search']) && !empty($this->params['search'])) {
                    $path = $path.'?search='.$this->params['search'];
                    if (isset($this->params['match']) && isset($this->params['index'])) {
                        $path = $path.'&match='.$this->params['match'].'&index='.$this->params['index']."#search";
                    }
                }
                return $this->redirect(Zord::getContextURL($this->context, $this->indexURL, $path), true);
            }
            if (file_exists(Library::data($isbn))) {
                $metadata = Library::data($isbn, 'metadata.json', 'array');
                $defined = isset($this->params['part']) && !empty($this->params['part']);
                $readable = $this->readable($isbn);
                $part = ($defined && $readable) ? $this->params['part'] : 'home';
                $message = null;
                if ($readable) {
                    if (isset($this->user->login)) {
                        (new UserHasQueryEntity())->create([
                            'user'    => $this->user->login,
                            'context' => $this->context,
                            'book'    => $isbn,
                            'part'    => $part,
                            'type'    => '2'
                        ]);
                    }
                } else {
                    $message = ['class' => 'warning'];
                    if (!$this->user->hasAccess($isbn, 'read')) {
                        $message['content'] = $this->locale->message->noreader->anyContext;
                    } else if (!$this->access($isbn, $this->context)) {
                        $message['content'] = $this->locale->message->noreader->thisContext;
                    } else {
                        $message['content'] = $this->locale->message->noreader->anyBook;
                    }
                }
                $title = Library::title($metadata, null, 40);
                $this->addMeta('generator', 'ZORD');
                $this->addMeta('DC.type', 'Book');
                $this->addMeta('DC.format', 'text/html');
                $this->addMeta('DC.source', OPENURL.'?id='.$isbn);
                $this->addMeta('DC.identifier', substr($isbn, 0, 3).'-'.substr($isbn, 3, 1).'-'.substr($isbn, 4, 3).'-'.substr($isbn, 7, 5).'-'.substr($isbn, 12, 1), 'ISBN');
                foreach(Zord::getConfig('meta') as $key => $datas) {
                    if (isset($metadata[$key])) {
                        foreach($datas as $data) {
                            $values = [];
                            if (is_array($metadata[$key])) {
                                $values = $metadata[$key];
                            } else {
                                $values = [$metadata[$key]];
                            }
                            foreach($values as $lang => $value) {
                                $this->addMeta(
                                    $data['name'],
                                    htmlspecialchars($value, ENT_QUOTES, 'UTF-8'),
                                    isset($data['scheme']) ? $data['scheme'] : null,
                                    is_int($lang) ? null : $lang
                                );
                            }
                        }
                    }
                }
                $included = isset($this->params['included']);
                $complete = isset($this->params['complete']);
                if ($complete && $this->user->hasRole('admin', $this->context)) {
                    $included = true;
                    $parts = [];
                    foreach (Library::data($isbn, 'parts.json', 'array') as $part) {
                        $parts[] = $part['name'];
                    }
                } else if ($included) {
                    $parts = $this->include($part, Library::data($isbn, 'parts.json', 'array'));
                } else {
                    $parts = [$part];
                    $visavis = Library::data($isbn, 'visavis.json', 'array');
                    if (isset($visavis)) {
                        foreach ($visavis as $group) {
                            if (in_array($part, $group)) {
                                $parts = $group;
                                break;
                            }
                        }
                    }
                }
                $obfuscate = !$this->user->hasAccess($isbn, 'inspect') && OBFUSCATE_BOOK;
                $obfuscator = $obfuscate ? new Obfuscator() : null;
                $texts = [];
                foreach($parts as $item) {
                    $text = Library::data($isbn, $item.'.xhtml', 'content');
                    if (isset($this->params['print'])) {
                        $_document = new DOMDocument();
                        $_document->loadXML($text);
                        $_xpath = new DOMXPath($_document);
                        $_loadings = $_xpath->query('//div[@class="graphic"]/div[@class="loading"]');
                        foreach ($_loadings as $_loading) {
                            $_graphic = $_loading->parentNode;
                            $_image = $_document->createElement('img');
                            $_image->setAttribute('src', '/medias/'.$isbn.'/'.$_graphic->getAttribute('data-url'));
                            $_graphic->replaceChild($_image, $_loading);
                            $text = $_document->saveXML($_document->documentElement);
                        }
                    }
                    if ($item == $part && isset($this->params['match']) && isset($this->params['index'])) {
                        $match = $this->params['match'];
                        $index = $this->params['index'];
                        $count = 1;
                        $text = preg_replace_callback(
                            //'#>(!>)*\b('.preg_quote($match).')\b(!<)*#',
                            '#(?<=\W)('.preg_quote($match).')(?=[^A-Za-z0-9À-ÖØ-öø-ÿ_])#',
                            function($matches) use (&$count, $index) {
                                //$replace = $count == $index ? '>'.$matches[1].'<div class="highlight" id="search">'.$matches[2].'</div>'.(count($matches) > 3 ? $matches[3] : '') : '>'.$matches[1].$matches[2].(count($matches) > 3 ? $matches[3] : '');
                                $replace = '<div class="highlight"'.($count == $index ? ' id="search"' : '').'>'.$matches[1].'</div>';
                                $count++;
                                return $replace;
                            },
                            $text
                        );
                    }
                    if ($obfuscate) {
                        $text = $obfuscator->getXML($text);
                    }
                    $texts[] = $text;
                }
                if ($included) {
                    $parts = [$part];
                    $document = new DOMDocument();
                    $document->loadXML('<div class="tei"><div class="text"></div><div class="footnotes"></div></div>');
                    $xpath = new DOMXPath($document);
                    $_text = $xpath->query('//div[@class="text"]')[0];
                    $_note = $xpath->query('//div[@class="footnotes"]')[0];
                    foreach ($texts as $text) {
                        $_document = new DOMDocument();
                        $_document->loadXML($text);
                        $_xpath = new DOMXPath($_document);
                        $__text = $_xpath->query('//div[@class="text"]')[0];
                        foreach ($__text->childNodes as $node) {
                            $_node = $document->importNode($node, true);
                            $_text->appendChild($_node);
                        }
                        $__note = $_xpath->query('//div[@class="footnotes"]')[0];
                        foreach ($__note->childNodes as $node) {
                            $_node = $document->importNode($node, true);
                            $_note->appendChild($_node);
                        }
                    }
                    $texts = [$document->saveXML($document->documentElement, LIBXML_NOEMPTYTAG)];
                }
                foreach (['screen','print'] as $media) {
                    foreach ($this->styles($media, $obfuscator) as $href) {
                        $this->addStyle($href, $media);
                    }
                }
                $zoom = Library::data($isbn, 'zoom.json', 'array');
                if (!empty($zoom)) {
                    $this->addScript('/library/js/openseadragon.js');
                }
                $this->addUpdatedScript(
                    'ariadne_'.$isbn.'.js',
                    '/portal/page/book/script/ariadne',
                    Library::data($isbn, 'ariadne.json'),
                    ['portal' => ['ariadne' => $this->ariadne($isbn)]]
                );
                return $this->page('book', ['book' => [
                    'TITLE'    => $title,
                    'ISBN'     => $isbn,
                    'PART'     => $part,
                    'PARTS'    => $parts,
                    'IDS'      => $obfuscate ? $obfuscator->getIDS() : Obfuscator::clearIDS(),
                    'parts'    => $texts,
                    'toc'      => Library::data($isbn, 'toc.xhtml', 'content'),
                    'metadata' => $metadata,
                    'message'  => $message,
                    'search'   => isset($this->params['search']) ? $this->params['search'] : null
                ]]);
            }
        }
        return $this->error(404, Zord::substitute($this->locale->message->unknown, ['isbn' => $isbn]));
    }
    
    protected function recordsMetadata($format, $isbn) {
        $metadata = Library::data($isbn, 'metadata.json', 'array');
        if (RECORDS_FIELDS[$format] ?? false) {
            $_metadata = [];
            foreach (RECORDS_FIELDS[$format] as $field) {
                $_metadata[$field] = $this->recordsField($format, $field, $metadata);
            }
            return $_metadata;
        }
        return $metadata;
    }
    
    protected function recordsField($format, $field, $metadata) {
        switch ($format) {
            case 'KBART': {
                switch ($field) {
                    case "publication_title": return '"'.str_replace('"', '""', $metadata['title']).'"';
                    case "print_identifier": return Store::isbn($metadata['ean']);
                    case "oclc_collection_name": return '"'.str_replace('"', '""', $metadata['collection'] ?? '').'"';
                    case "title_url": return $this->baseURL.'/book/'.$metadata['ean'];
                    case "first_author": return explode(',', $metadata['creator'][0] ?? '')[0];
                    case "title_id": return $metadata['ean'];
                    case "coverage_depth": return "fulltext";
                    case "publisher_name": return $metadata['publisher'];
                    case "publication_type": return "monograph";
                    case "date_monograph_published_print": return $metadata['date'];
                    case "date_monograph_published_online": return $metadata['publication'];
                    case "first_editor": return explode(',', $metadata['editor'][0] ?? '')[0];
                    case "access_type": return "P";
                }
            }
            default: return $metadata[$field] ?? "";
        }
    }
    
    protected function recordsContent($books, $format) {
        $steps = Zord::value('records', $format);
        $content = null;
        $ext = null;
        if ($steps) {
            if (!is_array($steps)) {
                $steps = [$steps];
            }
            $first = explode(':',$steps[0]);
            $metadata = [];
            foreach ($books as $isbn) {
                $metadata[] = $this->recordsMetadata($format, $isbn);
            }
            $view = new View('/'.$first[0].'/'.$first[1], ['books' => $metadata], $this->controler);
            $view->setMark(false);
            $content = $view->render();
            $ext = $first[0];
            switch ($ext) {
                case 'xml': {
                    for ($index = 1 ; $index < count($steps) ; $index++) {
                        $document = new DOMDocument();
                        $document->preserveWhiteSpace = false;
                        $document->formatOutput = true;
                        $document->loadXML($content);
                        $processor = Zord::getProcessor($steps[$index]);
                        if (isset($processor)) {
                            $content = $processor->transformToXML($document);
                        } else {
                            $content = null;
                            break;
                        }
                    }
                }
            }
        }
        return (isset($content) && isset($ext)) ? $this->download(
            $this->context.'_'.$format.'_'.date("Y-m-d").'.'.$ext,
            null,
            $content
        ) : $this->error(501);
    }
    
    protected function recordsList($context = null) {
        $criteria = [
            'many' => true,
            'order' => ['desc' => 'book']
        ];
        if (!empty($context)) {
            $criteria['where'] = ['context' => $context];
        }
        $entity = (new BookHasContextEntity())->retrieve($criteria);
        $books = [];
        foreach($entity as $entry) {
            $book = (new BookEntity())->retrieve($entry->book);
            if ($book) {
                $books[$entry->book] = [
                    'isbn'    => $entry->book,
                    'authors' => Zord::objectToArray($book->creator),
                    'title'   => $book->title,
                    'editors' => Zord::objectToArray($book->editor),
                    'date'    => $book->date
                ];
            }
        }
        return $books;
    }
    
    public function records() {
        if (isset($this->params['books'])) {
            $format = $this->params['format'] ?? 'MODS';
            $books = $this->params['books'] ?? '[]';
            if ($books === 'any') {
                $books = array_keys($this->recordsList());
            } else if ($books === 'all') {
                $books = array_keys($this->recordsList($this->context));
            } else {
                $books = Zord::objectToArray(json_decode($books));
            }
            return $this->recordsContent($books, $format);
        } else {
            $books = $this->recordsList($this->context);
            foreach($books as $data) {
                $books[$data['status'] == 'new' ? 'new' : 'other'][] = $data;
            }
            return $this->page('records', ['books' => $books]);
        }
    }
    
    public function metadata() {
        return $this->send(Library::data($this->params['isbn'], 'metadata.json'), 'admin');
    }
    
    public function parts() {
        return $this->send(Library::data($this->params['isbn'], 'parts.json'), 'admin');
    }
    
    public function header() {
        return $this->send(Library::data($this->params['isbn'], 'header.xml'), 'admin');
    }
    
    public function zoom() {
        return $this->resource('zoom');
    }
    
    public function medias() {
        return $this->resource('medias');
    }
        
    protected function resource($type) {
        $isbn = $this->params['isbn'] ?? null;
        $path = $this->params['path'] ?? null;
        if (empty($isbn) || empty($path)) {
            return $this->error(404);
        }
        if (in_array(pathinfo($path, PATHINFO_FILENAME), Zord::getSkin($this->context)->book->medias->public ?? [])) {
            $file = STORE_FOLDER.'public'.DS.$path;
            if (file_exists($file)) {
                return $this->send($file);
            }
        }
        return $this->readable($isbn) ? $this->send(STORE_FOLDER.$type.DS.$isbn.DS.$path) : $this->error($this->user->isKnown() ? 403 : 401);
    }
    
    public function xml() {
        return $this->download(STORE_FOLDER.'library'.DS.'${isbn}'.DS.'book.xml', 'admin', null, $this->params['isbn'].'.xml');
    }
    
    public function epub() {
        $metadata = Library::data($this->params['isbn'], 'metadata.json', 'array');
        if (isset($metadata['epub'])) {
            $this->params['isbn'] = $metadata['epub'];
        }
        return $this->download(STORE_FOLDER.'epub'.DS.'${isbn}.epub', 'reader');
    }
    
    public function pdf() {
        $metadata = Library::data($this->params['isbn'], 'metadata.json', 'array');
        if (isset($metadata['pdf'])) {
            $this->params['isbn'] = $metadata['pdf'];
        }
        return $this->download(STORE_FOLDER.'pdf'.DS.'${isbn}.pdf', 'reader');
    }
    
    public function quotes() {
        if (!isset($this->params['markers'])) {
            return $this->page('quotes');
        }
        return $this->download(
            $this->context.'_quotes_'.date("Y-m-d").'.doc',
            null,
            (new View('/markers', [
                'title'   => Zord::portalTitle($this->context, $this->lang),
                'markers' => $this->params['markers']
            ], $this->controler))->render()
        );
    }
    
    public function reference() {
        $reference = Library::reference($this->params['isbn'], isset($this->params['page']) ? $this->params['page'] : '');
        $reference['baseURL'] = $this->baseURL;
        return $reference;
    }
    
    public function counter() {
        if (isset($this->params['id']) && isset($this->params['type'])) {
            if (isset($_SESSION['__ZORD__']['__LIBRARY__']['__COUNTER__'][$this->params['id']])) {
                return $this->view('/counter', ['type' => $this->params['type'], 'counter' => $_SESSION['__ZORD__']['__LIBRARY__']['__COUNTER__'][$this->params['id']]], 'application/vnd.ms-excel', false, false);
            } else {
                return $this->page('home');
            }
        }
        $user    = null;
        $context = $this->params['context'] ?? null;
        $readers = [];
        $manager   = $this->user->isManager();
        $admin     = $this->user->hasRole('admin', $context ?? $this->context);
        $counter   = Library::isCounter($this->user, $context ?? $this->context);
        if (!$manager && !$admin && !$counter) {
            return $this->error(403);
        }
        if (!$manager && !$admin) {
            $user = $this->user;
        } else if (isset($this->params['user'])) {
            $user = new User($this->params['user']);
        }
        if ($manager) {
            if (isset($this->params['readers']) && Zord::value('context', $this->params['readers'])) {
                $entities = (new UserHasRoleEntity())->retrieve([
                    'many'  => true,
                    'where' => [
                        'role'    => ['in' => ['*','admin','reader']],
                        'context' => ['in' => ['*', $this->params['readers']]]
                    ]
                ]);
                foreach ($entities as $entity) {
                    $readers[] = $entity->user;
                }
            }
        }
        if (!$manager && !isset($user) && !isset($context) && empty($readers)) {
            return $this->error(406);
        }
        if (isset($user)) {
            $result['user'] = [
                'login' => $user->login,
                'name'  => $user->name
            ];
        }
        if (isset($context)) {
            $result['context'] = [
                'name'  => $context,
                'label' => Zord::getLocaleValue('title', Zord::value('context',$context), $this->lang)
            ];
        }
        if (!empty($readers)) {
            $result['readers'] = [
                'name'  => $this->params['readers'],
                'label' => Zord::getLocaleValue('title', Zord::value('context',$this->params['readers']), $this->lang)
            ];
        }
        $prefix = '';
        if ($user) {
            $prefix .= $user->login.'_';
            $result['scope'] = $result['user']['name'];
        }
        if ($readers) {
            $prefix .= $this->params['readers'].'_';
            $result['scope'] = $result['readers']['label'];
        }
        if ($context) {
            $prefix .= $context.'_';
            $result['scope'] = (($result['scope'] ?? false) ? $result['scope'].' / ' : '').$result['context']['label'];
        }
        $year = date("Y");
        $start = isset($this->params['start']) ? $this->params['start'] : $year.'-01-01';
        $end   = isset($this->params['end']) ? $this->params['end'] : $year.'-12-31';
        if ($end >= $start) {
            $result['range']['start'] = $start;
            $result['range']['end']   = $end;
            $timezone = new DateTimeZone(DEFAULT_TIMEZONE);
            $start    = new DateTime($start, $timezone);
            $end      = new DateTime($end,   $timezone);
            $first    = (clone $start)->modify('first day of this month');
            $last     = (clone $end)->modify('last day of this month');
            $result['months'] = [];
            for ($month = $first ; $month < $last ; $month->modify('+1 month')) {
                $result['months'][] = $month->format('Y-m');
            }
            $result['books'] = [];
            $select = [];
            if (isset($user)) {
                $select['user'] = $user->login;
            }
            if (isset($context)) {
                $select['context'] = $context;
            }
            if (!empty($readers)) {
                $select['user'] = $readers;
                $_context = Zord::value('context', [$this->params['readers'], 'from']);
                if ($_context && !isset($context)) {
                    $select['context'] = $_context;
                }
            }
            $query = '';
            foreach ($select as $key => $value) {
                $query .= "`".$key."` ";
                if (is_array($value)) {
                    foreach ($value as $index => $item) {
                        $value[$index] = "'".$item."'";
                    }
                    $query .= "IN (".implode(',', $value).")";
                } else {
                    $query .= "= '".$value."'";
                }
                $query .= " AND ";
            }
            $query .= "`type` = ? AND `when` BETWEEN ? AND ? ORDER BY `when` ASC";
            foreach (Zord::value('report', 'types') as $type => $title) {
                $result['reports'][$type]['title'] = $title;
                $result['reports'][$type]['books'] = [];
                $hits = (new UserHasQueryEntity())->retrieve([
                    'many'  => true,
                    'where' => [
                        'raw'        => $query,
                        'parameters' => [$type, $start->format('Y-m-d'), $end->format('Y-m-d')]
                    ]
                ]);
                foreach ($hits as $hit) {
                    $month   = (new DateTime($hit->when))->format('Y-m');
                    $book    = $hit->book;
                    $context = $hit->context;
                    $metadata = Library::data($book, 'metadata.json', 'array');
                    if ($metadata) {
                        $result['books'][$book] = Library::title($metadata);
                    }
                    if (!isset($result['reports'][$type]['books'][$book])) {
                        $result['reports'][$type]['books'][$book] = [];
                    }
                    if (!isset($result['reports'][$type]['context'])) {
                        $result['reports'][$type]['context'] = [];
                    }
                    if (!isset($result['reports'][$type]['counts']['months'][$month]['books'][$book])) {
                        $result['reports'][$type]['counts']['months'][$month]['books'][$book] = 0;
                    }
                    if (!isset($result['reports'][$type]['counts']['months'][$month]['total'])) {
                        $result['reports'][$type]['counts']['months'][$month]['total'] = 0;
                    }
                    if (!isset($result['reports'][$type]['counts']['books'][$book])) {
                        $result['reports'][$type]['counts']['books'][$book] = 0;
                    }
                    if (!isset($result['reports'][$type]['counts']['total'])) {
                        $result['reports'][$type]['counts']['total'] = 0;
                    }
                    if (!in_array($context, $result['reports'][$type]['books'][$book])) {
                        $result['reports'][$type]['books'][$book][] = $context;
                    }
                    if (!in_array($context, $result['reports'][$type]['context'])) {
                        $result['reports'][$type]['context'][] = $context;
                    }
                    $result['reports'][$type]['counts']['months'][$month]['books'][$book]++;
                    $result['reports'][$type]['counts']['months'][$month]['total']++;
                    $result['reports'][$type]['counts']['books'][$book]++;
                    $result['reports'][$type]['counts']['total']++;;
                }
            }
        }
        $id = uniqid();
        $result['id'] = $id;
        $result['prefix'] = empty($prefix) ? 'ALL_' : $prefix;
        $_SESSION['__ZORD__']['__LIBRARY__']['__COUNTER__'][$id] = $result;
        return $this->page('counter', ['counter' => $result]);
    }
    
    public function criteria() {
        $criteria = json_decode($this->params['criteria']);
        $locale = Zord::getLocale('search', $this->lang);
        $result = [];
        if (isset($criteria->query) && !empty($criteria->query)) {
            $result['query'] = $locale->query.' : '.$criteria->query;
            $result['results'] = $locale->results.' '.$locale->from.' '.($criteria->start + 1).' '.$locale->to.' '.($criteria->start + $criteria->rows);
        }
        if (isset($criteria->filters) && !empty($criteria->filters)) {
            $facets = [];
            foreach ($criteria->filters as $name => $filter) {
                if ($name == 'contentType' && is_array($criteria->filters->contentType)) {
                    $result['include'] = $locale->include_index;
                } else if ($name == 'source' && is_object($criteria->filters->source)) {
                    if (isset($criteria->filters->source->from) && !empty($criteria->filters->source->from) &&
                        isset($criteria->filters->source->to) && !empty($criteria->filters->source->to)) {
                            $result['source'] = $locale->source_date.' '.$locale->from.' '.$criteria->filters->source->from.' '.$locale->to.' '.$criteria->filters->source->to;
                        } else if (isset($criteria->filters->source->from) && !empty($criteria->filters->source->from)) {
                            $result['source'] = $locale->source_date.' '.$locale->from.' '.$criteria->filters->source->from;
                        } else if (isset($criteria->filters->source->to) && !empty($criteria->filters->source->to)) {
                            $result['source'] = $locale->source_date.' '.$locale->to.' '.$criteria->filters->source->to;
                        }
                } else if ($name == 'ean' && !empty($filter)) {
                    $result['books'][] = $locale->books;
                    foreach ($filter as $value) {
                        $result['books'][] = $value;
                    }
                } else if (!empty($filter)) {
                    $facets[] = $filter;
                    $result['facets'][$name][] = $locale->facets->$name;
                    foreach ($filter as $value) {
                        $label = Zord::value($name, [$this->context, $value]);
                        $result['facets'][$name][] = (isset($label) ? $label : $value);
                    }
                }
            }
            if (count($facets) > 1) {
                $operator = $criteria->operator;
                $result['operator'] = $locale->operators->$operator;
            }
        }
        return $result;
    }
    
    public function notify() {
        $send = false;
        if (isset($this->params['bug'])) {
            $bug = Zord::objectToArray(json_decode($this->params['bug']));
            $send = $this->sendMail([
                'category'   => 'bug'.DS.$bug['book'],
                'principal'  => ['email' => EDITOR_MAIL_ADDRESS, 'name' => EDITOR_MAIL_NAME],
                'subject'    => $this->locale->notify_bug,
                'text'       => $this->locale->click_here.' : '.$this->baseURL.$bug['zord_path'],
                'content'    => '/mail/bug',
                'models'     => [
                    'path'  => $bug['zord_path'],
                    'quote' => $bug['zord_citation'],
                    'note'  => $bug['zord_note']
                ]
            ]);
        }
        return ['send' => $send];
    }
    
    public function word() {
        $book = $this->params['book'] ?? null;
        $layout = $this->params['layout'] ?? "default";
        $format = $this->params['format'] ?? WORD_WRITER_FORMAT;
        if (empty($book) || empty($layout) || empty($format)) {
            return $this->page('home');
        }
        return $this->send(Zord::getInstance('WordBuilder', $book, $layout, $format)->process(), 'admin');
    }
    
    private function inContextFilterQuery() {
        $books = (new BookHasContextEntity())->retrieve([
            'many'  => true,
            'where' => ['context' => $this->context],
            'order' => ['desc' => 'book']
        ]);
        if ($books) {
            $filters = [];
            foreach ($books as $book) {
                $filters[] = $book->book;
            }
            if (!empty($filters)) {
                return Store::field('ean').':('.implode(' ', $filters).')';
            }
        }
    }
    
    private function access($isbn, $context = null) {
        $where = ['book' => $isbn];
        if (isset($context)) {
            $where['context'] = $context;
        }
        $entries = (new BookHasContextEntity())->retrieve([
            'where' => $where,
            'many'  => true
        ]);
        if ($entries) {
            foreach ($entries as $entry) {
                if ($entry->context == $this->context) {
                    return true;
                }
            }
        }
        return false;
    }
    
    private function ariadne($isbn, $part = null) {
        $parts = Library::data($isbn, 'parts.json', 'array');
        $ariadne = Library::data($isbn, 'ariadne.json', 'array');
        $sequence = [];
        if (isset($ariadne)) {
            $sequence = $ariadne;
        } else {
            $toc = Library::data($isbn, 'toc.xhtml', 'document');
            $tocXPath = new DOMXPath($toc);
            foreach($parts as $index => $_part) {
                $li = $tocXPath->query('//li[@data-id="'.$_part['id'].'"]/span');
                if ($index == 0 || $li->length == 1) {
                    $sequence[] = $_part;
                }
            }
            $ariadne = $sequence;
            file_put_contents(Library::data($isbn, 'ariadne.json'), Zord::json_encode($ariadne));
        }
        if ($part) {
            foreach ($parts as $_part) {
                if ($_part['name'] == $part) {
                    $ariadne['current'] = $_part;
                    foreach ($sequence as $index => $step) {
                        if ($step['id'] == $_part['id']) {
                            if ($index > 0) {
                                $ariadne['previous'] = $sequence[$index - 1];
                            }
                            if ($index < count($sequence) - 1) {
                                $ariadne['next'] = $sequence[$index + 1];
                            }
                            break;
                        }
                    }
                    break;
                }
            }
        } else {
            $ariadne['sequence'] = $sequence;
        }
        return $ariadne;
    }
    
    public function fetch($criteria = null) {
        $search   = [];
        $books    = [];
        $parts    = [];
        $id       = isset($criteria) ? uniqid() : null;
        if (!isset($criteria)) {
            if (isset($this->params['criteria'])) {
                $id = uniqid();
                $criteria = Zord::objectToArray(json_decode($this->params['criteria']));
            } else if (isset($this->params['id']) && isset($_SESSION['__ZORD__']['__SEARCH___'][$this->params['id']])) {
                $id = $this->params['id'];
                $criteria = $_SESSION['__ZORD__']['__SEARCH___'][$id];
            }
        }
        $criteria['context'] = $criteria['context'] ?? $this->context;
        if (isset($id) && $criteria['context'] == $this->context) {
            $_SESSION['__ZORD__']['__SEARCH___'][$id] = $criteria;
            $query = Store::query($criteria);
            if (!isset($criteria['scope']) || empty($criteria['scope'])) {
                $criteria['scope'] = Zord::value('portal', ['default','search','scope']);
            }
            if ($criteria['scope'] == 'corpus' && is_array($criteria['filters']['ean']) && count($criteria['filters']['ean']) > 0) {
                $query->addFilterQuery(Store::field('ean').':('.implode(' ', $criteria['filters']['ean']).')');
                if (isset($this->user->login)) {
                    foreach ($criteria['filters']['ean'] as $book) {
                        (new UserHasQueryEntity())->create([
                            'user'    => $this->user->login,
                            'context' => $this->context,
                            'book'    => $book,
                            'type'    => '5'
                        ]);
                    }
                }
            } else {
                $query->addFilterQuery($this->inContextFilterQuery());
            }
            if (isset($criteria['query']) && !empty($criteria['query'])) {
                $query->addHighlightField('content');
                $query->setHighlightSimplePre('<b>');
                $query->setHighlightSimplePost('</b>');
                $query->setHighlightSnippets(100000);
                $query->setHighlightFragsize(200);
                $query->setHighlightMaxAnalyzedChars(-1);
                $query->setHighlightMergeContiguous(false);
                $query->setHighlight(true);
            } else {
                $criteria['rows'] = SEARCH_PAGE_MAX_SIZE;
            }
            list($found, $documents, $highlighting) = Store::search($query);
            foreach ($documents as $document) {
                $ean = $document['ean_s'];
                if (!in_array($ean, $books)) {
                    $books[] = $ean;
                }
                $part = substr($document['id'], strpos($document['id'], '_') + 1);
                if (!isset($parts[$ean][$part])) {
                    $entity = (new BookHasPartEntity())->retrieve(['book' => $ean, 'part' => $part]);
                    if ($entity) {
                        $parts[$ean][$part] = Zord::objectToArray($entity['data']);
                    }
                }
            }
            $end = min([$criteria['start'] + $criteria['rows'], $found]);
            $search = [
                'criteria' => $criteria,
                'found'    => $found,
                'pages'    => ceil($found / $criteria['rows']),
                'books'    => $books,
                'parts'    => $parts,
                'end'      => $end,
                'id'       => $id
            ];
            foreach ($highlighting as $id => $object) {
                $indexes = [];
                $tokens = explode('_', $id);
                $isbn = $tokens[0];
                $part = $tokens[1];
                foreach ($object['content'] as $content) {
                    $first = strpos($content, '<b>');
                    $last = strrpos($content, '</b>');
                    if ($first && $last) {
                        $match = [
                            'left'    => substr($content, 0, $first),
                            'keyword' => strip_tags(substr($content, $first, $last -$first + strlen('<b>') + 1)),
                            'right'   => substr($content, $last + strlen('</b>'))
                        ];
                        if (isset($indexes[$match['keyword']])) {
                            $index = $indexes[$match['keyword']];
                        } else {
                            $index = 0;
                        }
                        $indexes[$match['keyword']] = $index + 1;
                        $match['index'] = $indexes[$match['keyword']];
                        $search['matches'][$isbn][$part][] = $match;
                    }
                }
            }
        }
        return $search;
    }
    
    protected function statusInContext($entity) {
        return $entity->status;
    }
    
    public function classify($search = false) {
        $books    = ($search !== false && isset($search['books'])) ? $search['books'] : null;
        $year     = ($search !== false && ctype_digit($search) && strlen($search) == 4 && in_array(substr($search, 0, 2), ['18','19','20'])) ? $search : null;
        $category = ($search !== false && is_string($search) && null !== Zord::value('category', [$this->context,$search])) ? $search : null;
        $entities = (new BookHasContextEntity())->retrieve([
            'many'  => true,
            'where' => ['context' => $this->context]
        ]);
        $status = [];
        foreach ($entities as $entity) {
            $status[$entity->book] = $this->statusInContext($entity);
        }
        $raw = 'BookHasContextEntity.context = ?';
        $parameters = $this->context;
        if (!empty($books)) {
            $raw .= ' AND BookEntity.ean in ('.implode(',', array_map(function($ean) {return "'".$ean."'";}, $books)).')';
        }
        if (isset($year)) {
            $raw .= ' AND BookEntity.date = ?';
            $parameters = [$parameters, $year];
        }
        if (isset($category)) {
            $raw .= ' AND BookEntity.category = ?';
            $parameters = [$parameters, '["'.$category.'"]'];
        }
        $entities = (new BookEntity())->retrieve([
            'many'  => true,
            'join'  => 'BookHasContextEntity',
            'where' => ['raw' => $raw, 'parameters' => $parameters],
            'order' => Zord::value('portal', ['default','classify','order'])
        ]);
        $shelves = [];
        $labels  = [];
        $books = [];
        foreach($entities as $book) {
            $isbn = $book->ean;
            $books[$isbn] = [
                'isbn'     => $isbn,
                'status'   => $status[$book->ean],
                'source'   => ((empty($book->s_from) && empty($book->s_to)) || (!empty($book->s_from) && !empty($book->s_to) && $book->s_from == $book->s_to)) ? 2 : 1,
                'from'     => $book->s_from,
                'to'       => $book->s_to,
                'creator'  => Zord::objectToArray($book->creator),
                'title'    => $book->title,
                'subtitle' => $book->subtitle,
                'editor'   => Zord::objectToArray($book->editor),
                'date'     => $book->date,
                'category' => Zord::objectToArray($book->category),
                'number'   => $book->number,
                'readable' => $this->user->hasAccess($isbn, 'read'),
                'since'    => $book->first_published ?? '',
                'metadata' => Library::data($isbn, 'metadata.json', 'array')
            ];
        }
        $liner = ($search !== false && empty($search['liner'])) ? (Zord::value('plugin', ['liner','search']) ?? 'SearchLiner') : Zord::value('plugin', ['liner',$this->context]);
        if (!isset($liner)) {
            $liner = Zord::value('plugin', 'liner');
            if (!isset($liner) || !(is_string($liner))) {
                $liner = Zord::value('plugin', ['liner','default']);
                if (!isset($liner) || !(is_string($liner))) {
                    $liner = 'DefaultLiner';
                }
            }
        }
        $liner = new $liner($this->context, $this->lang);
        $apart = ($search === false);
        $class = ($search !== false ? 'search' : null);
        $result = $liner->line($books, $apart, $class);
        $shelves = $result['shelves'];
        $labels = $result['labels'];
        foreach($shelves as $name => $shelf) {
            foreach($shelf['books'] as $index => $book) {
                $shelves[$name]['books'][$index]['matches'] = isset($search['matches'][$book['isbn']]) ? $search['matches'][$book['isbn']] : [];
                $shelves[$name]['books'][$index]['parts'] = isset($search['parts'][$book['isbn']]) ? $search['parts'][$book['isbn']] : [];
            }
        }
        foreach($shelves as $name => $shelf) {
            $count = 0;
            foreach($shelf['books'] as $book) {
                $count2 = 0;
                foreach ($book['matches'] as $matches) {
                    $count2 += count($matches);
                }
                $shelves[$name]['instances'][$book['isbn']] = $count2;
                $count += $count2;
            }
            $shelves[$name]['instances']['total'] = $count;
        }
        $shelves = $this->order($shelves, $labels);
        return [
            'search'  => $search,
            'shelves' => $shelves,
            'labels'  => $labels
        ];
    }
    
    public function order($shelves, $labels) {
        $order = $shelves;
        $keys = array_keys($labels ?? []);
        $keys[-1]   = 'new';
        $keys[9998] = 'other';
        $keys[9999] = 'others';
        usort($order, function($first, $second) use ($keys) {
            $x = (int) array_search($first['name'], $keys);
            $y = (int) array_search($second['name'], $keys);
            if ($x == $y) {
                return 0;
            } else {
                return $x < $y ? -1 : 1;
            }
        });
        $shelves = [];
        foreach($order as $shelf) {
            $shelves[$shelf['name']] = $shelf;
        }
        return $shelves;
    }
    
    public function match($term = null, $rows = 10, $fields = null, $exact = false) {
        $term = $term ?? ($this->params['term'] ?? null);
        if (empty($term)) {
            return $this->error(400);
        }
        $results = [];
        $term = str_replace('"', '', preg_replace_callback("/(&#[0-9]+;)/", function($m) {
            return mb_convert_encoding($m[1], "UTF-8", "HTML-ENTITIES");
        }, $term));
        $matches = Store::match($term, null, $rows, $fields, $exact);
        foreach ($matches as $ean) {
            $book = (new BookEntity())->retrieve($ean);
            if ($book !== false) {
                $context = (new BookHasContextEntity())->retrieve([
                    'book'    => $book->ean,
                    'context' => $this->context
                ]);
                if ($context !== false) {
                    $results[] = [
                        'value' => $ean,
                        'label' => Library::title($book->title, $book->subtitle, 60)
                    ];
                }
            }
        }
        return $results;
    }
}
