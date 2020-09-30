<?php

class Book extends Module {
        
    private static $SOLR_ORDERS = [
        "ASC"  => SolrQuery::ORDER_ASC,
        "DESC" => SolrQuery::ORDER_DESC
    ];
    
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
    
    public function unapi() {
        if (isset($this->params['id'])) {
            $format = isset($this->params['format']) ? $this->params['format'] : 'rdf_bibliontology';
            $metadata = Library::data($this->params['id'], 'metadata.json', 'array');
            return $this->view('/xml/formats/'.$format, ['metadata' => $metadata], Zord::value('formats', [$format, 'type']), false);
        } else {
            return $this->view('/xml/formats', ['formats' => Zord::getConfig('formats')], 'application/xml', false);
        }
    }
    
    public function openurl() {
        if (isset($this->params['id'])) {
            $isbn = $this->params['id'];
            if (file_exists(Library::data($isbn))) {
                $access = [];
                $context = (new BookHasContextEntity())->retrieve([
                    'where' => ['book' => $isbn],
                    'many'   => true
                ]);
                if ($context) {
                    foreach ($context as $entry) {
                        $access['context'][$entry->context] = true;
                    }
                }
                if (isset($access['context'])) {
                    $context = array_keys($access['context']);
                    $name = null;
                    foreach ($context as $key) {
                        if (null !== Zord::value('context', [$key,'url'])) {
                            $name = $key;
                            break;
                        }
                    }
                    foreach ($context as $key) {
                        if ($this->user->hasRole('reader', $key) && null !== Zord::value('context', [$key,'url'])) {
                            $name = $key;
                            break;
                        }
                    }
                    if ($name) {
                        $this->params['isbn'] = $isbn;
                        $this->params['ctx'] = $name;
                        return $this->show();
                    }
                }
            }
        }
        if (isset($this->params['cover'])) {
            $cover = Store::resource('medias', $this->params['cover'], 'frontcover');
            if ($cover) {
                return $this->redirect(OPENURL.$cover);
            }
        }
        return $this->error(404);
    }
    
    public function search() {
        $facets = [];
        foreach ($this->facets() as $facet) {
            if (!empty($this->facets($facet))) {
                $facets[] = $facet;
            }
        }
        return $this->page('search', array_merge($this->classify($this->fetch()), ['pullout' => SEARCH_PULLOUT, 'facets' => $facets]));
    }
    
    public function show() {
        $isbn = $this->either(null, 'isbn');
        if (isset($isbn)) {
            if (isset($this->params['ctx'])) {
                return $this->redirect(Zord::getContextURL($this->params['ctx'], 0, '/book/'.$isbn, $this->lang, $this->user->session), true);
            }
            if (isset($this->params['xhr']) && $this->params['xhr']) {
                $path = '/book/'.$isbn;
                if (isset($this->params['part'])) {
                    $path = $path.'/'.$this->params['part'];
                }
                if (isset($this->params['search']) && $this->params['search'] != 'none') {
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
                $readable = $this->user->hasAccess($isbn, 'reader') && $this->access($isbn);
                $part = ($defined && $readable) ? $this->params['part'] : 'home';
                $message = null;
                if ($readable) {
                    (new UserHasQueryEntity())->create([
                        'user'    => $this->user->login,
                        'context' => $this->context,
                        'book'    => $isbn,
                        'part'    => $part,
                        'type'    => '2'
                    ]);
                } else {
                    $message = ['class' => 'warning'];
                    if (!$this->user->hasAccess($isbn, 'reader')) {
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
                $obfuscate = !$this->user->hasAccess($isbn, 'admin') && OBFUSCATE_BOOK;
                $obfuscator = $obfuscate ? new Obfuscator() : null;
                $texts = [];
                foreach($parts as $item) {
                    $text = Library::data($isbn, $item.'.xhtml', 'content');
                    if ($item == $part && isset($this->params['match']) && isset($this->params['index'])) {
                        $match = $this->params['match'];
                        $index = $this->params['index'];
                        $count = 1;
                        $text = preg_replace_callback(
                            //'#>(!>)*\b('.preg_quote($match).')\b(!<)*#',
                            '#\b('.preg_quote($match).')\b#',
                            function($matches) use (&$count, $index) {
                                //$replace = $count == $index ? '>'.$matches[1].'<div class="highlight" id="search">'.$matches[2].'</div>'.(count($matches) > 3 ? $matches[3] : '') : '>'.$matches[1].$matches[2].(count($matches) > 3 ? $matches[3] : '');
                                $replace = $count == $index ? '<div class="highlight" id="search">'.$matches[1].'</div>' : $matches[1];
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
                foreach (['screen','print'] as $media) {
                    foreach (['common',$media] as $css) {
                        $this->addStyle($obfuscate ? '/build/'.$obfuscator->getCSS($css)   : '/library/css/book/'.$css.'.css', $media);
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
        
    public function records() {
        if (isset($this->params['books'])) {
            $format = $this->either('MODS', 'format');
            $books = Zord::objectToArray(json_decode($this->params['books']));
            $steps = Zord::value('records', $format);
            if ($steps) {
                if (!is_array($steps)) {
                    $steps = [$steps];
                }
                $content = (new View('/xml/'.$steps[0], ['books' => $books], $this->controler))->render();
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
                return isset($content) ? $this->download(
                    $this->context.'_'.$format.'_'.date("Y-m-d").'.xml',
                    null,
                    $content
                ) : $this->error(501);
            }
        } else {
            $entity = (new BookHasContextEntity())->retrieve([
                'many' => true,
                'where' => ['context' => $this->context],
                'order' => ['desc' => 'book']
            ]);
            $books = [];
            foreach($entity as $entry) {
                $isbn = $entry->book;
                $book = (new BookEntity())->retrieve($isbn);
                if ($book) {
                    $books[$entry->status == 'new' ? 'new' : 'other'][] = [
                        'isbn'    => $isbn,
                        'authors' => Zord::objectToArray(json_decode($book->creator)),
                        'title'   => $book->title,
                        'editors' => Zord::objectToArray(json_decode($book->editor)),
                        'date'    => $book->date     
                    ];
                }
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
        
    private function resource($type) {
        $isbn = $this->params['isbn'];
        $path = $this->params['path'];
        return $this->send(STORE_FOLDER.$type.DS.$isbn.DS.$path, 'reader');
    }
    
    public function epub() {
        $metadata = Library::data($this->params['isbn'], 'metadata.json', 'array');
        if (isset($metadata['epub'])) {
            $this->params['isbn'] = $metadata['epub'];
        }
        return $this->download(STORE_FOLDER.'epub'.DS.'${isbn}.epub', 'reader');
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
                return $this->view('/counter', ['type' => $this->params['type'], 'counter' => $_SESSION['__ZORD__']['__LIBRARY__']['__COUNTER__'][$this->params['id']]], 'application/vnd.ms-excel', false);
            } else {
                return $this->page('home');
            }
        }
        $user    = null;
        $context = null;
        if (isset($this->params['context'])) {
            $context = $this->params['context'];
        }
        if ($this->user->isManager()) {
            if (isset($this->params['user'])) {
                $user = new User($this->params['user']);
            }
        } else if ($this->user->isConnected()) {
            $user = $this->user;
        }
        if (!isset($user) && !isset($context) && !$this->user->isManager()) {
            return $this->page('home');
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
        $prefix = '';
        if ($user) {
            $prefix .= $user->login.'_';
        }
        if ($context) {
            $prefix .= $context.'_';
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
    
    public function field($key) {
        foreach (Zord::value('index', 'fields') as $field => $type) {
            if ($key == $field) {
                return $key.Zord::value('index', ['suffix',$type]);
            }
        }
        return false;
    }

    public function titles() {
        $titles = [];
        foreach ($this->inContext('BookEntity') as $book) {
            $titles[$book->ean] = Library::title($book->title, $book->subtitle);
        }
        Zord::sort($titles);
        return $titles;
    }
    
    public function facets($type = null) {
        if (isset($type)) {
            $facets = [];
            foreach ($this->inContext('BookHasFacetEntity', "BookHasFacetEntity.facet = '".$type."'") as $facet) {
                $facets[] = $facet->value;
            }
            return $facets;
        }
        if (isset($this->params['key'])) {
            $name = $this->params['key'];
            $facets = $this->facets($name);
            $keys   = [];
            $values = [];
            $locale = Zord::value($name, $this->context);
            foreach ($facets as $key) {
                if (isset($locale)) {
                    if (isset($locale[$key])) {
                        $keys[$key]   = $key;
                        $values[$key] = $locale[$key];
                    }
                } else {
                    $keys[$key]   = $key;
                    $values[$key] = $key;
                }
            }
            $facets = array_combine($keys, $values);
            Zord::sort($facets);
            return $facets;
        } else {
            return Zord::value('search', 'facets');
        }
    }

/*
    public function facets() {
        $field = $this->field($this->params['key']);
        $client = new SolrClient(Zord::value('connection', ['solr','zord']));
        $query = new SolrQuery();
        $query->setQuery('*.*');
        $query->setRows(0);
        $query->setFacet(true);
        $query->addFacetField($field);
        $query->addFilterQuery($this->inContextFilterQuery());
        $result = $client->query($query)->getResponse();
        $list = $result['facet_counts']['facet_fields'][$field];
        $facets = [];
        foreach ($list as $index => $entry) {
            if (($index + 1) % 2 && (!is_string($entry) || $entry !== "")) {
                $facets[$entry] = $list[$index + 1];
            }
        }
        ksort($facets);
        return array_keys($facets);
    }
*/
    
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
    
    private function inContext($type, $where = null) {
        return (new $type())->retrieve([
            'many'  => true,
            'join'  => 'BookHasContextEntity',
            'where' => [
                'raw'        => 'BookHasContextEntity.context = ?'.(isset($where) ? ' AND '.$where : ''),
                'parameters' => [$this->context]
            ]
        ]);
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
                return $this->field('ean').':('.implode(' ', $filters).')';
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
    
    private function fetch() {
        $criteria = [];
        $search   = [];
        $books    = [];
        $parts    = [];
        $id = null;
        if (isset($this->params['criteria'])) {
            $id = uniqid();
            $criteria = Zord::objectToArray(json_decode($this->params['criteria']));
        } else if (isset($this->params['id']) && isset($_SESSION['__ZORD__']['__SEARCH___'][$this->params['id']])) {
            $id = $this->params['id'];
            $criteria = $_SESSION['__ZORD__']['__SEARCH___'][$id];
        }
        if (isset($id) && isset($criteria['context']) && $criteria['context'] == $this->context) {
            $_SESSION['__ZORD__']['__SEARCH___'][$id] = $criteria;
            $client = new SolrClient(Zord::value('connection', ['solr','zord']));
            $query  = new SolrQuery();
            if (!isset($criteria['scope']) || empty($criteria['scope'])) {
                $criteria['scope'] = Zord::value('portal', ['default','search','scope']);
            }
            if (!isset($criteria['operator']) || empty($criteria['operator'])) {
                $criteria['operator'] = Zord::value('portal', ['default','search','operator']);
            }
            if ($criteria['scope'] == 'corpus' && is_array($criteria['filters']['ean']) && isset($this->user->login)) {
                foreach ($criteria['filters']['ean'] as $book) {
                    (new UserHasQueryEntity())->create([
                        'user'    => $this->user->login,
                        'context' => $this->context,
                        'book'    => $book,
                        'type'    => '5'
                    ]);
                }
            } else {
                $query->addFilterQuery($this->inContextFilterQuery());
            }
            if (isset($criteria['query']) && !empty($criteria['query'])) {
                $query->setQuery($criteria['query']);
            } else {
                $query->setQuery('*:*');
                $criteria['query'] = '';
                $criteria['rows'] = SEARCH_PAGE_MAX_SIZE;
            }
            $filters = [];
            foreach ($criteria['filters'] as $key => $value) {
                $field = $this->field($key);
                if (!$field) {
                    $filter = Zord::value('search', ['filters',$key]);
                    if ($filter) {
                        $filter = new $filter();
                        $filter->add($query, $key, $value);
                    }
                } else {
                    $filter = null;
                    if (!is_array($value)) {
                        $filter = $field.':"'.$value.'"';
                    } else if (count($value) > 0) {
                        $filter = $field.':('.implode(' ', array_map(function($val) use($field) {
                            return '"'.$val.'"';
                        }, $value)).')';
                    }
                    if ($filter) {
                        if (in_array($key, Zord::value('search', 'facets'))) {
                            $filters[] = $filter;
                        } else {
                            $query->addFilterQuery($filter);
                        }
                    }
                }
            }
            if (!empty($filters)) {
                $query->addFilterQuery('('.implode(' '.$criteria['operator'].' ', $filters).')');
            }
            $query->addField('id');
            foreach (Zord::value('search', ['fetch']) as $key) {
                $query->addField($this->field($key));
            }
            foreach (Zord::value('search', ['sort']) as $key => $order) {
                $query->addSortField($this->field($key), self::$SOLR_ORDERS[$order]);
            }
            if ($criteria['rows'] > SEARCH_PAGE_MAX_SIZE) {
                $criteria['rows'] = SEARCH_PAGE_MAX_SIZE;
            }
            $query->setStart($criteria['start']);
            $query->setRows($criteria['rows']);
            if (isset($criteria['query']) && !empty($criteria['query'])) {
                $query->addHighlightField('content');
                $query->setHighlightSimplePre('<b>');
                $query->setHighlightSimplePost('</b>');
                $query->setHighlightSnippets(100000);
                $query->setHighlightFragsize(200);
                $query->setHighlightMaxAnalyzedChars(-1);
                $query->setHighlightMergeContiguous(false);
                $query->setHighlight(true);
            }
            $result = $client->query($query);
            $result = Zord::objectToArray(json_decode($result->getRawResponse()));
            $found = $result['response']['numFound'];
            if (isset($result['response']['docs']) && !empty($result['response']['docs'])) {
                foreach ($result['response']['docs'] as $doc) {
                    $ean = $doc['ean_s'];
                    if (!in_array($ean, $books)) {
                        $books[] = $ean;
                    }
                    $part = substr($doc['id'], strpos($doc['id'], '_') + 1);
                    if (!isset($parts[$ean][$part])) {
                        $entity = (new BookHasPartEntity())->retrieve(['book' => $ean, 'part' => $part]);
                        if ($entity) {
                            $parts[$ean][$part] = Zord::objectToArray(json_decode($entity['data']));
                        }
                    }
                }
            }
            $end    = min([$criteria['start'] + $criteria['rows'], $found]);
            $search = [
                'criteria' => $criteria,
                'found'    => $found,
                'books'    => $books,
                'parts'    => $parts,
                'end'      => $end,
                'id'       => $id
            ];
            if (isset($result['highlighting']) && !empty($result['highlighting'])) {
                foreach ($result['highlighting'] as $id => $object) {
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
        }
        return !empty($books) ? $search : [];
    }
    
    public function classify($search = false) {
        $books = ($search !== false && isset($search['books'])) ? $search['books'] : null;
        $entities = (new BookHasContextEntity())->retrieve([
            'many'  => true,
            'where' => ['context' => $this->context]
        ]);
        $status = [];
        foreach ($entities as $entity) {
            $status[$entity->book] = $entity->status;
        }
        $entities = (new BookEntity())->retrieve([
            'many'  => true,
            'join'  => empty($books) ? 'BookHasContextEntity' : null,
            'where' => empty($books) ? ['raw' => 'BookHasContextEntity.context = ?', 'parameters' => $this->context] : ['BookEntity.ean' => ['in' => $books]],
            'order' => ['desc' => 'ean']
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
                'creator'  => Zord::objectToArray(json_decode($book->creator)),
                'title'    => $book->title,
                'subtitle' => $book->subtitle,
                'editor'   => Zord::objectToArray(json_decode($book->editor)),
                'date'     => $book->date,
                'category' => Zord::objectToArray(json_decode($book->category)),
                'number'   => $book->number,
                'readable' => $this->user->hasAccess($isbn, 'reader')
            ];
        }
        $liner = Zord::value('plugin', ['liner',$this->context]);
        if (!isset($liner)) {
            $liner = Zord::value('plugin', 'liner');
            if (!isset($liner) || !(is_string($liner))) {
                $liner = 'DefaultLiner';
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
        $order = $shelves;
        $keys = array_keys($labels);
        $keys[-1]   = 'new';
        $keys[9999] = 'other';
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
        return [
            'search'  => $search,
            'shelves' => $shelves,
            'labels'  => $labels
        ];
    }
}
