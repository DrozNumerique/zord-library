<?php

class Obfuscator {
        
    private static $CLEAR_IDS = null;
    private static $ALPHABET  = 'abcdefghijklmnopqrstuvwxyz';
    
    private $ids = null;
    private $prefix = null;
    private $elementMap = null;
    private $attributeMap = null;
    private $filename = null;
    
    public function __construct() {
        $folder = Zord::liveFolder('config'.DS.'obf');
        $files = glob($folder.'*.json');
        $TEI = [];
        foreach (COMPONENT_FOLDERS as $tier) {
            $TEI[] = $tier.'config'.DS.'TEI.json';
        }
        foreach($files as $file) {
            if (Zord::needsUpdate($file, $TEI)) {
                unlink($file);
                foreach(['screen','print'] as $media) {
                    unlink(BUILD_FOLDER.pathinfo($file, PATHINFO_FILENAME).'_'.$media.'.css');
                }
            }
        }
        if (count($files) < OBFUSCATION_MODELS_MAX) {
            $this->prefix = self::$ALPHABET[rand(0, 25)];
            $elements = Zord::value('TEI', 'elements');
            shuffle($elements);
            $this->elementMap = array();
            $index = 0;
            foreach ($elements as $element) {
                $this->elementMap[$element] = $this->num2alpha($index++);
            }
            $attributes = Zord::value('TEI', 'attributes');
            shuffle($attributes);
            $this->attributeMap = array();
            $index = 0;
            foreach ($attributes as $attribute) {
                if (strpos($attribute, ':') === false) {
                    $this->attributeMap[$attribute] = $this->num2alpha($index++);
                }
            }
            $this->ids = self::buildIDS(
                $this->prefix,
                $this->elementMap,
                $this->attributeMap
            );
            $content = [];
            $content['ids'] = $this->ids;
            $content['prefix'] = $this->prefix;
            $content['elements'] = $this->elementMap;
            $content['attributes'] = $this->attributeMap;
            $this->filename = md5(json_encode($this->elementMap).json_encode($this->attributeMap));
            file_put_contents($folder.$this->filename.'.json', Zord::json_encode($content));
        } else {
            $this->filename = pathinfo($files[rand(0, count($files) - 1)], PATHINFO_FILENAME);
            $obfuscator = Zord::arrayFromJSONFile($folder.$this->filename.'.json');
            $this->ids = $obfuscator['ids'];
            $this->prefix = $obfuscator['prefix'];
            $this->elementMap = $obfuscator['elements'];
            $this->attributeMap = $obfuscator['attributes'];
        }
    }
    
    public function getIDS() {
        return $this->ids;
    }
    
    public function getXML($xml) {
        $xml = preg_replace_callback(
            '#class="(\w+)"#si',
            function($matches) {
                if (isset($this->elementMap[$matches[1]])) {
                    return 'class="'.$this->elementMap[$matches[1]].'"';
                } else {
                    return $matches[0];
                }
            },
            $xml
        );
        $xml = preg_replace_callback(
            '#data-(\w+)#si',
            function($matches) {
                if (isset($this->attributeMap[$matches[1]])) {
                    return 'data-'.$this->attributeMap[$matches[1]];
                } else {
                    return $matches[0];
                }
            },
            $xml
        );
        return $xml;
    }
    
    public function getCSS($media = 'screen') {
        $file = BUILD_FOLDER.$this->filename.'_'.$media.'.css';
        if (!file_exists($file)) {
            $CSS = file_get_contents(Zord::getComponentPath('web'.DS.'css'.DS.'book'.DS.$media.'.css'));
            $CSS = preg_replace_callback(
                '#div\.(\w+)#si',
                function($matches) {
                    if (isset($this->elementMap[$matches[1]])) {
                        return 'div.'.$this->elementMap[$matches[1]];
                    } else {
                        return $matches[0];
                    }
                },
                $CSS
            );
            $CSS = preg_replace_callback(
                '#data-(\w+)#si',
                function($matches) {
                    if (isset($this->attributeMap[$matches[1]])) {
                        return 'data-'.$this->attributeMap[$matches[1]];
                    } else {
                        return $matches[0];
                    }
                },
                $CSS
            );
            file_put_contents($file, $CSS);
        }
        return pathinfo($file, PATHINFO_BASENAME);
    }
    
    public static function buildIDS($prefix, $elementMap, $attributeMap) {
        $els = array('nspace' => $prefix);
        foreach (Zord::value('TEI', 'obfuscated') as $tag => $attributes) {
            $els[$tag] = array('elm' => $elementMap[$tag]);
            foreach ($attributes as $attribute) {
                $els[$tag][$attribute] = $attributeMap[$attribute];
            }
        }
        return bin2hex(substr(json_encode($els), strlen('{"nspace":"'), -strlen('"}}')));
    }
    
    public static function clearIDS() {
        if (!self::$CLEAR_IDS) {
            $elements = Zord::value('TEI', 'elements');
            $attributes = Zord::value('TEI', 'attributes');
            self::$CLEAR_IDS = self::buildIDS(
                'tei',
                array_combine($elements, $elements),
                array_combine($attributes, $attributes)
            );
        }
        return self::$CLEAR_IDS;
    }
    
    private function num2alpha($index) {
        $n = $index;
        for ($r = ""; $n >= 0; $n = intval($n / 26) - 1) {
            $r = chr($n%26 + 0x41) . $r;
        }
        if ($r == 'ID') {
            $index++;
            $r = $this->num2alpha($index);
        }
        return strtolower($r);
    }
}

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
            $metadata = Library::data($book['ISBN'], 'meta', 'array');
            $parts  = $metadata['parts'];
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
            $metadata = Library::data($this->params['id'], 'meta', 'array');
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
            $cover = Library::media($this->params['cover'], 'frontcover');
            if ($cover) {
                return $this->redirect(OPENURL.$cover);
            }
        }
        return $this->error(404);
    }
    
    public function home() {
        return $this->page('home', $this->classify());
    }
    
    public function search() {
        $facets = [];
        foreach ($this->facets() as $facet) {
            if (!empty($this->facets($facet))) {
                $facets[] = $facet;
            }
        }
        return $this->page('search', array_merge($this->classify($this->fetch()), ['slide' => SEARCH_SLIDE, 'facets' => $facets]));
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
                $metadata = Library::data($isbn, 'meta', 'array');
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
                $title = Library::title(isset($metadata['title']) ? $metadata['title'] : '', isset($metadata['subtitle']) ? $metadata['subtitle'] : '', 40);
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
                if (isset($metadata['visavis'])) {
                    foreach ($metadata['visavis'] as $group) {
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
                    $text = Library::data($isbn, $item, 'content');
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
                if (!empty($metadata['zoom'])) {
                    $this->addScript('/library/js/openseadragon.js');
                }
                $this->addUpdatedScript(
                    'ariadne_'.$isbn.'.js',
                    '/portal/page/book/script/ariadne',
                    Library::data($isbn, 'meta'),
                    ['portal' => ['ariadne' => $this->ariadne($isbn)]]
                );
                return $this->page('book', ['book' => [
                    'TITLE'    => $title,
                    'ISBN'     => $isbn,
                    'PART'     => $part,
                    'PARTS'    => $parts,
                    'IDS'      => $obfuscate ? $obfuscator->getIDS() : Obfuscator::clearIDS(),
                    'parts'    => $texts,
                    'toc'      => Library::data($isbn, 'toc', 'content'),
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
        return $this->send(Library::data($this->params['isbn'], 'meta'), 'admin');
    }
    
    public function header() {
        return $this->send(Library::data($this->params['isbn'], 'head'), 'admin');
    }
    
    public function epub() {
        $metadata = Library::data($this->params['isbn'], 'meta', 'array');
        if (isset($metadata['epub'])) {
            $this->params['isbn'] = $metadata['epub'];
        }
        return $this->download(DATA_FOLDER.'epub'.DS.'${isbn}.epub', 'reader');
    }
    
    public function quotes() {
        if (!isset($this->params['markers'])) {
            return $this->page('quotes');
        }
        return $this->download(
            $this->context.'_quotes_'.date("Y-m-d").'.doc',
            null,
            (new View('/markers', [
                'title'   => Library::portalTitle($this->context, $this->lang),
                'markers' => $this->params['markers']
            ], $this->controler))->render()
        );
    }
    
    public function reference() {
        return Library::reference($this->params['isbn'], isset($this->params['page']) ? $this->params['page'] : '');
    }
    
    public function counter() {
        $user = null;
        $context = null;
        if ($this->user->isConnected() || $this->user->isManager()) {
            $user = $this->user;
            $list = $user->getContext('counter', false);
            if (count($list) == 1) {
                $context = $list[0];
            }
        }
        if ($this->user->isManager()) {
            if (isset($this->params['user'])) {
                $user = new User($this->params['user']);
            } else if (isset($this->params['context'])) {
                $context = $this->params['context'];
            }
        }
        if (!isset($user)) {
            return $this->page('home');
        }
        if (isset($this->params['id']) && isset($this->params['type'])) {
            if (isset($_SESSION['__ZORD__']['__LIBRARY__']['__COUNTER__'][$this->params['id']])) {
                return $this->view('/counter', ['type' => $this->params['type'], 'counter' => $_SESSION['__ZORD__']['__LIBRARY__']['__COUNTER__'][$this->params['id']]], 'application/vnd.ms-excel', false);
            } else {
                return $this->page('home');
            }
        }
        $users = [];
        if (!isset($context)) {
            $result = ['user' => [
                'login' => $user->login,
                'name'  => $user->name
            ]];
            $users = [$user->login];
        } else {
            $result = ['context' => [
                'id'    => $context,
                'label' => Zord::getLocaleValue('title', Zord::value('context',$context), $this->lang)
            ]];
            $entity = (new UserHasRoleEntity())->retrieve([
                'where' => [
                    'role'    => ['in' => ['*','reader']],
                    'context' => $context
                ],
                'many'   => true
            ]);
            if ($entity) {
                foreach ($entity as $entry) {
                    $users[] = $entry->user;
                }
            }
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
            $list = '(';
            $index = 0;
            foreach ($users as $entry) {
                $list = $list."`user` = '".$entry."'";
                if ($index < count($users) - 1) {
                    $list = $list." OR ";
                }
                $index++;
            }
            $list = $list.')';
            foreach (Zord::value('report', 'types') as $type => $title) {
                $result['reports'][$type]['title'] = $title;
                $result['reports'][$type]['books'] = [];
                $hits = (new UserHasQueryEntity())->retrieve([
                    'where' => [
                        'raw' => $list." AND `type` = ? AND `when` BETWEEN ? AND ? ORDER BY `when` ASC",
                        'parameters' => [$type, $start->format('Y-m-d'), $end->format('Y-m-d')]
                    ],
                    'many'   => true
                ]);
                if ($hits) {
                    foreach ($hits as $hit) {
                        $month   = (new DateTime($hit->when))->format('Y-m');
                        $book    = $hit->book;
                        $context = $hit->context;
                        $metadata = Library::data($book, 'meta', 'array');
                        if ($metadata) {
                            $result['books'][$book] = $metadata['title'];
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
        }
        $id = uniqid();
        $result['id'] = $id;
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
        $metadata = Library::data($isbn, 'meta', 'array');
        $parts = $metadata['parts'];
        $sequence = [];
        $ariadne = [];
        if (isset($metadata['ariadne'])) {
            $sequence = $metadata['ariadne'];
        } else {
            $toc = Library::data($isbn, 'toc', 'document');
            $tocXPath = new DOMXPath($toc);
            foreach($parts as $index => $_part) {
                $li = $tocXPath->query('//li[@data-id="'.$_part['id'].'"]/span');
                if ($index == 0 || $li->length == 1) {
                    $sequence[] = $_part;
                }
            }
            $metadata['ariadne'] = $sequence;
            file_put_contents(Library::data($isbn, 'meta'), Zord::json_encode($metadata));
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
                $criteria['scope'] = DEFAULT_SEARCH_SCOPE;
            }
            if (!isset($criteria['operator']) || empty($criteria['operator'])) {
                $criteria['operator'] = DEFAULT_SEARCH_OPERATOR;
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
                $query->setHighlightFragsize(50);
                $query->setHighlightMaxAnalyzedChars(-1);
                $query->setHighlightMergeContiguous(false);
                $query->setHighlight(true);
            }
            $result = $client->query($query);
            $result = $result->getResponse();
            $found = $result['response']['numFound'];
            if (isset($result['response']['docs']) && !empty($result['response']['docs'])) {
                foreach ($result['response']['docs'] as $doc) {
                    $ean = $doc->ean_s;
                    if (!in_array($ean, $books)) {
                        $books[] = $ean;
                    }
                    $part = substr($doc->id, strpos($doc->id, '_') + 1);
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
                        $matches = [];
                        preg_match('#(.*)<b>(.*)</b>(.*)#', $content, $matches);
                        $match = [
                            'left'    => $matches[1],
                            'keyword' => $matches[2],
                            'right'   => $matches[3]
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
        return !empty($books) ? $search : [];
    }
    
    private function classify($search = false) {
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
        $locale  = [];
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
        $apart = $search === false;
        $class = $search !== false ? 'search' : null;
        $result = $liner->line($books, $apart, $class);
        $shelves = $result['shelves'];
        $locale = $result['locale'];
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
        $keys = array_keys($locale);
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
            'locale'  => $locale
        ];
    }
}