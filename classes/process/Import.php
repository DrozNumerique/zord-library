<?php

class LibraryImport extends Import {

    protected $adjust     = NS_ADJUST;
    protected $prefix     = NS_PREFIX;
    protected $namespace  = NS_URL;
    protected $xmlns      = ' xmlns:'.NS_PREFIX.'="'.NS_URL.'"';
    protected $publish    = null;
    protected $base       = [];
    
    protected $size     = 0;
    protected $total    = 0;
    protected $facets   = [];
    
    protected $xml      = null;
    protected $document = null;
    protected $adjusted = null;
    protected $metadata = null;
    protected $idCount  = 1;
    protected $dones    = [];
    protected $xpath    = null;
    protected $ajdXpath = null;
    protected $toc      = null;
    protected $tocXPath = null;
    protected $page     = null;
    
    protected function book($isbn, $metadata) {
        return [
            "ean"      => $isbn,
            "title"    => isset($metadata['title']) ? $metadata['title'] : '',
            "subtitle" => isset($metadata['subtitle']) ? $metadata['subtitle'] : '',
            "creator"  => isset($metadata['creator']) ? $metadata['creator'] : [],
            "editor"   => isset($metadata['editor']) ? $metadata['editor'] : [],
            "category" => isset($metadata['category']) ? $metadata['category'] : [],
            "number"   => isset($metadata['category_number']) ? $metadata['category_number'] : '',
            "date"     => isset($metadata['date']) ? $metadata['date'] : '',
            "s_from"   => isset($metadata['creation_date_from']) ? $metadata['creation_date_from'] : '',
            "s_to"     => isset($metadata['creation_date_to']) ? $metadata['creation_date_to'] : ''
        ];
    }
    
    protected function parts($isbn, $metadata) {
        $parts = [];
        if (isset($metadata['parts'])) {
            foreach ($metadata['parts'] as &$part) {
                if ($part['index']) {
                    $part['content'] = explode("\n", wordwrap(trim(preg_replace(
                        '#\s+#s', ' ', html_entity_decode(strip_tags(str_replace(
                            '<br/>', ' ',
                            Store::data($isbn, $part['name'], 'content')
                        )), ENT_QUOTES | ENT_XML1, 'UTF-8')
                    )), INDEX_MAX_CONTENT_LENGTH));
                    $parts[] = $part;
                }
            }
        }
        return $parts;
    }
    
    protected function configure($parameters = []) {
        parent::configure($parameters);
        if (isset($parameters['publish'])) {
            $this->publish = $parameters['publish'];
        }
        if (isset($parameters['adjust'])) {
            $this->adjust = $parameters['adjust'];
        }
        if (isset($parameters['prefix'])) {
            $this->adjust = $parameters['prefix'];
        }
        if (!isset($this->publish) && file_exists($this->folder.'publish.json')) {
            $this->publish = Zord::arrayFromJSONFile($this->folder.'publish.json');
        }
        if (!isset($this->books)) {
            $set = $this->folder.'*';
            $xmls = glob($set.'.xml');
            $medias = glob($set, GLOB_ONLYDIR);
            $this->books = [];
            foreach ([$xmls, $medias] as $items) {
                foreach ($items as $item) {
                    $book = pathinfo($item, PATHINFO_FILENAME);
                    if (!in_array($book, $this->books)) {
                        $this->books[] = $book;
                    }
                }
            }
        }
    }
    
    protected function preBooks() {
        if (in_array('slice', $this->steps)) {
            foreach ($this->books as $isbn) {
                $text = $this->folder.$isbn.'.xml';
                if (file_exists($text)) {
                    $this->total = $this->total + filesize($text);
                }
            }
        }
    }
    
    protected function resetBook($isbn) {
        parent::resetBook($isbn);
        $text = $this->folder.$isbn.'.xml';
        if ($this->total > 0 && file_exists($text)) {
            $this->size += filesize($text);
            $this->progress = $this->size / $this->total;
        }
        $this->xml = $this->folder.$isbn.'.xml';
        $this->document = null;
        $this->adjusted = null;
        $this->metadata = null;
        $this->xpath = null;
        $this->adjXPath = null;
        $this->toc = null;
        $this->tocXPath = null;
        $this->idCount = 1;
        $this->page = null;
        $this->dones = [];
    }
    
    protected function grant($isbn) {
        $entity = (new BookHasContextEntity())->retrieve([
            'where' => ['book' => $isbn],
            'many'   => true
        ]);
        if ($entity) {
            $context = [];
            foreach($entity as $entry) {
                if (!$this->user->hasRole('admin', $entry->context)) {
                    $context[] = $entry->context;
                }
            }
            if (!empty($context)) {
                foreach($context as $name) {
                    $this->logError('grant', Zord::substitute($this->locale->noadmin, [
                        'context' => $name,
                        'name'    => Zord::getLocaleValue('title', Zord::value('context', $name), $this->lang)
                    ]));
                }
                return false;
            }
        }
        return true;
    }
    
    protected function load($isbn) {
        if (file_exists($this->xml)) {
            $this->document = new DOMDocument();
            $this->loadXML($this->document, $this->xml);
            $this->xpath = new DOMXpath($this->document);
            $this->xpath->registerNamespace($this->prefix, $this->namespace);
        } else {
            $this->warn(2, $this->locale->messages->load->warning->missing);
        }
        return true;
    }
    
    protected function metadata($isbn) {
        if ($header = $this->header($isbn)) {
            file_put_contents(Store::data($isbn, 'head'), $header);
            $header = simplexml_load_string($header);
            $uuid = md5($header);
            $metadata = [
                'ean'    => $isbn,
                'format' => 'text/html',
                'uuid'   => substr($uuid, 0, 8).'-'.substr($uuid, 8, 4).'-'.substr($uuid, 12, 4).'-'.substr($uuid, 16, 4).'-'.substr($uuid, 20, 12),
                'uri'    => OPENURL.'?id='.$isbn,
            ];
            if (isset($header->fileDesc->titleStmt->title)) {
                foreach ($header->fileDesc->titleStmt->title as $title) {
                    $metadata[isset($title['type']) && $title['type'] == 'sub' ? 'subtitle' : 'title'] = Library::compact($title);
                }
            }
            if (isset($header->fileDesc->titleStmt->author)) {
                $metadata['creator'] = [];
                foreach ($header->fileDesc->titleStmt->author as $author) {
                    if (!in_array($author['key'].'', $metadata['creator'])) {
                        $metadata['creator'][] = $author['key'].'';
                    }
                }
            }
            if (isset($header->fileDesc->titleStmt->editor)) {
                $metadata['editor'] = [];
                foreach ($header->fileDesc->titleStmt->editor as $editor) {
                    if (!in_array($editor['key'].'', $metadata['editor'])) {
                        $metadata['editor'][] = $editor['key'].'';
                    }
                }
            }
            if (isset($header->fileDesc->seriesStmt) && null !== $header->fileDesc->seriesStmt->attributes('xml', true) && isset($header->fileDesc->seriesStmt->attributes('xml', true)['id'])) {
                $metadata['category'] = [$header->fileDesc->seriesStmt->attributes('xml', true)['id'].''];
            }
            if (isset($header->fileDesc->seriesStmt['n'])) {
                $metadata['category_number'] = $header->fileDesc->seriesStmt['n'].'';
            }
            if (isset($header->fileDesc->extent->measure)) {
                foreach ($header->fileDesc->extent->measure as $measure) {
                    $metadata['page'.($measure['unit'].'' != 'pages' ? '_'.$measure['unit'].'' : '')]  = $measure['quantity'].'';
                }
            }
            if (isset($header->profileDesc->abstract)) {
                foreach ($header->profileDesc->abstract as $abstract) {
                    if (null !== $abstract->attributes('xml', true) && isset($abstract->attributes('xml', true)['lang'])) {
                        $metadata['description'][$abstract->attributes('xml', true)['lang'].''] = $abstract->p.'';
                    }
                }
            }
            if (isset($header->fileDesc->sourceDesc->biblFull->publicationStmt->publisher)) {
                $metadata['publisher'] = $header->fileDesc->sourceDesc->biblFull->publicationStmt->publisher.'';
            }
            if (isset($header->fileDesc->sourceDesc->biblFull->publicationStmt->pubPlace) && !empty($header->fileDesc->sourceDesc->biblFull->publicationStmt->pubPlace.'')) {
                $metadata['pubplace'] = $header->fileDesc->sourceDesc->biblFull->publicationStmt->pubPlace.'';
            }
            if (isset($header->fileDesc->sourceDesc->biblFull->publicationStmt->publisher)) {
                $metadata['rights'] = '© '.$header->fileDesc->sourceDesc->biblFull->publicationStmt->publisher;
            }
            if (isset($header->fileDesc->sourceDesc->biblFull->seriesStmt->title)) {
                $relation = [];
                foreach ($header->fileDesc->sourceDesc->biblFull->seriesStmt->title as $line) {
                    $type = isset($line['type']) ? $line['type'].'' : '';
                    if ($type == 'num') {
                        $metadata['collection_number'] = $line.'';
                    } else if ($type == 'main') {
                        $relation[] = $line.'';
                    }
                }
                $metadata['relation'] = implode(", ", $relation);
            }
            if (isset($header->fileDesc->sourceDesc->biblFull->publicationStmt->date)) {
                $metadata['date'] = Library::year($header->fileDesc->sourceDesc->biblFull->publicationStmt->date['when']);
            }
            if (isset($header->fileDesc->notesStmt->note)) {
                foreach ($header->fileDesc->notesStmt->note as $note) {
                    $type = $note['type'].'';
                    if ($type == "ref") {
                        $metadata['ref_n'] = $note->ref['n'].'';
                        $metadata['ref_url'] = $note->ref['target'].'';
                    }
                    if ($type == "image") {
                        $metadata['ref_cover'] = $note->graphic['url'].'';
                    }
                }
            }
            if (isset($header->profileDesc->creation->date)) {
                if (isset($header->profileDesc->creation->date['when']) && !empty($header->profileDesc->creation->date['when'].'')) {
                    $metadata['creation_date_from'] = Library::year($header->profileDesc->creation->date['when']);
                    $metadata['creation_date_to'] = Library::year($header->profileDesc->creation->date['when']);
                }
                if (isset($header->profileDesc->creation->date['notBefore']) && !empty($header->profileDesc->creation->date['notBefore'].'')) {
                    $metadata['creation_date_from'] = Library::year($header->profileDesc->creation->date['notBefore']);
                }
                if (isset($header->profileDesc->creation->date['notAfter']) && !empty($header->profileDesc->creation->date['notAfter'].'')) {
                    $metadata['creation_date_to'] = Library::year($header->profileDesc->creation->date['notAfter']);
                }
                if (isset($header->profileDesc->creation->date['from']) && !empty($header->profileDesc->creation->date['from'].'')) {
                    $metadata['creation_date_from'] = Library::year($header->profileDesc->creation->date['from']);
                }
                if (isset($header->profileDesc->creation->date['to']) && !empty($header->profileDesc->creation->date['to'].'')) {
                    $metadata['creation_date_to'] = Library::year($header->profileDesc->creation->date['to']);
                }
            }
            if (isset($header->profileDesc->langUsage->language)) {
                $metadata['language'] = $header->profileDesc->langUsage->language['ident'].'';
            } else {
                $metadata['language'] = explode('-', $this->lang)[0];
            }
            if (isset($header->profileDesc->textClass)) {
                if (isset($header->profileDesc->textClass->keywords)) {
                    foreach ($header->profileDesc->textClass->keywords as $keywords) {
                        $scheme = $keywords['scheme'].'';
                        $subjectCodes = [];
                        $scheme = mb_strtoupper($scheme);
                        $lang = (null !== $keywords->attributes('xml', true) && isset($keywords->attributes('xml', true)['lang'])) ? '_'.mb_strtoupper($keywords->attributes('xml', true)['lang'].'') : '';
                        foreach ($keywords->term as $term){
                            $subjectCodes[] = mb_strtoupper(trim($term.''));
                        }
                        $metadata['subjectCodes'][$scheme.$lang] = $subjectCodes;
                    }
                }
            }
            $publications = [];
            if (isset($header->fileDesc->sourceDesc->biblFull)) {
                $publications[] = $header->fileDesc->sourceDesc->biblFull;
            }
            if (isset($header->fileDesc)) {
                $publications[] = $header->fileDesc;
            }
            foreach($publications as $publication) {
                if (isset($publication->publicationStmt->idno)) {
                    foreach($publication->publicationStmt->idno as $idno) {
                        $type = ''.$idno['type'];
                        $value = ''.$idno;
                        switch($type) {
                            case 'EAN_ePUB': {
                                $metadata['epub'] = $value;
                                break;
                            }
                            case 'EAN_pdf': {
                                $metadata['pdf'] = $value;
                                break;
                            }
                        }
                    }
                }
            }
            $metaFile = Store::data($isbn, 'meta');
            $previous = Zord::arrayFromJSONFile($metaFile);
            foreach (['medias','zoom','parts','refs','visavis','ariadne'] as $keep) {
                if (isset($previous[$keep])) {
                    $metadata[$keep] = $previous[$keep];
                }
            }
            file_put_contents($metaFile, Zord::json_encode($metadata));
            return parent::metadata($isbn);
        } else {
            $this->logError('metadata', $this->locale->messages->metadata->error->missing);
            return false;
        }
    }
    
    protected function validate($isbn) {
        $result = true;
        if (file_exists($this->xml) && isset($this->document)) {
            $errors = shell_exec(Zord::substitute(RELAXNG_COMMAND, [
                'RNG' => Zord::getComponentPath('xml'.DS.'tei.rng'),
                'XML' => $this->xml
            ]));
            $errors = str_replace($this->xml.':', '@ ', $errors);
            $errors = str_replace(': error: ', ' --> ', $errors);
            if (!empty($errors)) {
                foreach(explode("\n", $errors) as $error) {
                    $mark = 'not allowed here;';
                    $pos = strpos($error, $mark);
                    if ($pos > 0) {
                        $error = substr($error, 0, $pos + strlen($mark));
                    }
                    $this->logError('validate', $error);
                }
                $result = false;
            }
            $this->metadata = Store::data($isbn, 'meta', 'array');
            $this->metadata['medias'] = [];
            $this->metadata['zoom'] = [];
            $page = null;
            $paths = [
                '*[@xml:id]',
                'div',
                'ref[@target]',
                'graphic[@url]',
                ['pb','note'],
                'ref//ref'
            ];
            foreach ($paths as $num => $path) {
                if (!is_array($path)) {
                    $path = [$path];
                }
                foreach (array_keys($path) as $index) {
                    $path[$index] = '//'.$this->prefix.':text//'.implode('/', array_map(function($token) {
                        if (!empty($token) && substr($token, 0, 1) !== '*') {
                            return $this->prefix.':'.$token;
                        } else {
                            return $token;
                        }
                    }, explode('/', $path[$index])));
                }
                $path = implode(' | ', $path);
                $elements = $this->xpath->query($path);
                foreach ($elements as $element) {
                    switch ($num) {
                        case 0: {
                            $id = $element->getAttribute('xml:id');
                            foreach ([' ',"\n","\t"] as $forbidden) {
                                if (strpos($id, $forbidden) !== false) {
                                    $this->xmlError('validate', $element, $this->locale->messages->validate->error->id, ['id' => $id]);
                                    $result = false;
                                    break;
                                }
                            }
                            break;
                        }
                        case 1: {
                            if (!$element->getAttribute('type')) {
                                $this->xmlError('validate', $element, $this->locale->messages->validate->error->type, ['type' => '(null)']);
                                $result = false;
                            } else {
                                $type = $element->getAttribute('type');
                                $valid = false;
                                foreach (Zord::value('import', 'types') as $types) {
                                    if (in_array($type, $types)) {
                                        $valid = true;
                                        break;
                                    }
                                }
                                if (!$valid) {
                                    $this->xmlError('validate', $element, $this->locale->messages->validate->error->type, ['type' => $type]);
                                    $result = false;
                                }
                            }
                            break;
                        }
                        case 2: {
                            if (!$element->hasChildNodes()) {
                                $this->xmlError('validate', $element, $this->locale->messages->validate->error->ref, ['target' => $element->getAttribute('target')]);
                                $result = false;
                            }
                            break;
                        }
                        case 3: {
                            $url = $element->getAttribute('url');
                            if (substr($url, 0, 4) !== 'http') {
                                $imgFile = DATA_FOLDER.str_replace('/', DS, $this->url($isbn, $element, 'url', true));
                                $this->metadata['medias'][$url] = $imgFile;
                                if (!file_exists($imgFile)) {
                                    $this->xmlError('validate', $element, $this->locale->messages->validate->error->missing.$url);
                                    $result = false;
                                }
                                if ($element->parentNode->nodeName == 'figure' &&
                                    $element->parentNode->hasAttribute('rend') &&
                                    in_array($element->parentNode->getAttribute('rend'), ['zoom','facsimile'])) {
                                    $this->metadata['zoom'][] = $url;
                                }
                            }
                            break;
                        }
                        case 4: {
                            $id = $element->getAttribute('xml:id');
                            $n = $element->getAttribute('n');
                            $info = '';
                            $info .= ((isset($n) && (!empty($n) || $n === "0")) ? ' (n="'.$n.'")' : '');
                            $info .= ((isset($id) && !empty($id)) ? ' (xml:id="'.$id.'")' : '');
                            if ($element->nodeName == 'pb') {
                                if ($element->hasAttribute('ed') && $element->getAttribute('ed') == 'temoin') {
                                    $this->xmlError('validate', $element, $this->locale->messages->validate->error->page->temoin.$info);
                                    $result = false;
                                }
                                if (!isset($n) || empty($n)) {
                                    $this->xmlError('validate', $element, $this->locale->messages->validate->error->page->number.$info);
                                    $result = false;
                                }
                                if (!$element->hasAttribute('rend') || $element->getAttribute('rend') !== 'temoin') {
                                    if (!isset($id) || empty($id)) {
                                        $this->xmlError('validate', $element, $this->locale->messages->validate->error->page->id.$info);
                                        $result = false;
                                    }
                                    $page = $n;
                                }
                            } else if ($element->nodeName == 'note') {
                                if (!isset($page)) {
                                    $this->xmlError('validate', $element, $this->locale->messages->validate->error->note->page.$info);
                                    $result = false;
                                }
                                $info = (isset($page) && !empty($page)) ? ' p. ['.$page.']'.$info : $info;
                                if (!isset($id) || empty($id)) {
                                    $this->xmlError('validate', $element,$this->locale->messages->validate->error->note->id.$info);
                                    $result = false;
                                }
                                if (!isset($n) || (empty($n) && $n !== "0")) {
                                    $this->xmlError('validate', $element, $this->locale->messages->validate->error->note->name.$info);
                                    $result = false;
                                }
                            }
                            break;
                        }
                        case 5: {
                            $parent = $element->parentNode;
                            while ($parent->nodeName !== 'ref') {
                                $parent = $parent->parentNode;
                            }
                            $this->xmlError('validate', $element, $this->locale->messages->validate->error->nested, [
                                'nested' => $element->getAttribute('target'),
                                'parent' => $parent->getAttribute('target')
                            ]);
                            break;
                        }
                    }
                }
            }
            file_put_contents(Store::data($isbn, 'meta'), Zord::json_encode($this->metadata));
        } else {
            $this->logError('validate', $this->locale->messages->validate->error->nodata);
            $result = false;
        }
        return $result;
    }
    
    protected function adjust($isbn) {
        if (!isset($this->document)) {
            $this->logError('adjust', $this->locale->messages->adjust->error->nodata);
            return false;
        }
        if (isset($this->adjust)) {
            $processor = Zord::getProcessor($this->adjust);
            if (isset($processor)) {
                $this->adjusted = new DOMDocument();
                $this->loadXML($this->adjusted, $processor->transformToXML($this->document));
                $this->adjXPath = new DOMXpath($this->adjusted);
            } else {
                $this->logError('adjust', $this->locale->messages->adjust->error->noxsl.$this->adjust);
                return false;
            }
        } else {
            $this->adjusted = $this->document;
            $this->adjXPath = $this->xpath;
        }
        return true;
    }
            
    protected function slice($isbn) {
        $result = true;
        $this->metadata = Store::data($isbn, 'meta', 'array');
        foreach(['parts','refs','ariadne','visavis'] as $name) {
            $this->metadata[$name] = [];
        }
        if (file_exists($this->xml) && isset($this->adjusted) && isset($this->adjXPath) && isset($this->metadata)) {
            
            $this->toc = new DOMDocument();
            $this->toc->preserveWhiteSpace = false;
            $this->toc->formatOutput = true;
            $this->toc->loadXML(Zord::substitute(file_get_contents(Zord::getComponentPath('xml'.DS.'toc.xhtml')), ['isbn' => $isbn]));
            $this->tocXPath = new DOMXPath($this->toc);
                        
            $titlePage = $this->adjXPath->query('//'.$this->prefix.':front/'.$this->prefix.':titlePage')[0];
            $titlePage->setAttribute('id', PART_ID_PREFIX.$this->idCount);
            $title = isset($this->metadata['title']) && !empty($this->metadata['title']) ? $this->metadata['title'] : $this->locale->titlePage;
            $this->addPart('home', 'title', $title, 'front', 'front', 1, $titlePage);            
            foreach (['front','body','back'] as $tag) {
                $this->scan($tag, $tag, 1);
            }
            
            $folder    = Store::data($isbn);
            $tmpFolder = Store::data($isbn, 'temp');
            Zord::resetFolder($tmpFolder);
            
            foreach ($this->metadata['parts'] as &$part) {
                $result &= $this->handlePart($part, $isbn, $tmpFolder);
                foreach (['node','parent','part','content'] as $useless) {
                    unset($part[$useless]);
                }
            }
            $book = DATA_FOLDER.'books'.DS.$isbn.'.xml';
            if ($this->xml !== $book) {
                copy($this->xml, $book);
            }
            $book = new DOMDocument();
            $book->load($this->xml);
            if (file_exists($folder.'header.xml')) {
                copy($folder.'header.xml', $tmpFolder.'header.xml');
                $headerElements = $book->getElementsByTagName('teiHeader');
                $oldHeader = null;
                if ($headerElements->length == 1) {
                    $oldHeader = $headerElements->item(0);
                }
                $newHeader = new DOMDocument();
                $newHeader->load($folder.'header.xml');
                if (isset($newHeader->documentElement)) {
                    $newHeader = $book->importNode($newHeader->documentElement, true);
                    if ($newHeader) {
                        if ($oldHeader) {
                            $oldHeader->parentNode->replaceChild($newHeader, $oldHeader);
                        } else {
                            $book->documentElement->insertBefore($newHeader, $book->documentElement->firstChild);
                        }
                    }
                }
            }
            file_put_contents($tmpFolder.'metadata.json', Zord::json_encode($this->metadata));
            file_put_contents($tmpFolder.'toc.xhtml',     $this->toc->saveXML($this->toc->documentElement));
            
            Zord::deleteRecursive($folder);
            rename($tmpFolder, $folder);
            file_put_contents(Store::data($isbn, 'book'),  $book->saveXML());
            
            (new BookHasPartEntity())->delete([
                'where' => ['book' => $isbn],
                'many'   => true
            ]);
            foreach ($this->metadata['parts'] as &$part) {
                (new BookHasPartEntity())->create([
                    'book'  => $isbn,
                    'part'  => $part['name'],
                    'count' => $part['count'],
                    'data'  => $part
                ]);
                $ref = Store::data($isbn, $part['ref']);
                if (!file_exists($ref) || strpos(file_get_contents($ref), 'id="'.$part['id'].'"') === false) {
                    $this->xmlError('slice', $part['line'], $this->locale->messages->slice->error->embed, [
                        'type'  => $part['type'],
                        'title' => $part['title']
                    ]);
                    $result = false;
                }
            }
        } else {
            $this->logError('slice', $this->locale->messages->slice->error->nodata);
            $result = false;
        }
        return $result;
    }
    
    protected function zoom($isbn) {
        $metadata = Store::data($isbn, 'meta', 'array');
        $zoom = isset($metadata['zoom']) ? $metadata['zoom'] : [];
        if (!empty($zoom)) {
            $deepzoom = new Deepzoom(Zord::getConfig('zoom'));
            $this->info(2, $this->locale->messages->zoom->info->processor.$deepzoom->processor);
            if ($deepzoom->processor == Deepzoom::$DEFAULT_PROCESSOR) {
                $this->info(2, $this->locale->messages->zoom->info->convert.$deepzoom->convert);
            }
            $folder = DATA_FOLDER.'zoom'.DS.$isbn.'.tmp';
            Zord::deleteRecursive($folder);
            $count = 1;
            foreach($zoom as $url) {
                $file = $metadata['medias'][$url];
                $this->info(2, $count.' / '.count($zoom).' : '.$file);
                $deepzoom->process($file, $folder);
                $count++;
            }
            $folder = DATA_FOLDER.'zoom'.DS.$isbn;
            Zord::deleteRecursive($folder);
            rename($folder.'.tmp', $folder);
        } else {
            $this->info(2, $this->locale->messages->zoom->info->none);
        }
        return true;
    }
        
    protected function index($isbn) {
        $result = true;
        $metadata = Store::data($isbn, 'meta', 'array');
        $parts = isset($metadata['parts']) ? $metadata['parts'] : [];
        if (isset($metadata) && isset($parts)) {
            $index = new SolrClient(Zord::value('connection', ['solr','zord']));
            $delete = $index->deleteByQuery('ean_s:'.$isbn);
            $response = $delete->getResponse();
            if ($response) {
                foreach ($parts as &$part) {
                    if ($part['index']) {
                        $part['content'] = explode("\n", wordwrap(trim(preg_replace(
                            '#\s+#s', ' ', html_entity_decode(strip_tags(str_replace(
                                '<br/>', ' ',
                                Store::data($isbn, $part['name'], 'content')
                            )), ENT_QUOTES | ENT_XML1, 'UTF-8')
                        )), INDEX_MAX_CONTENT_LENGTH));
                        $document = new SolrInputDocument();
                        $document->addField('id', $isbn.'_'.$part['name']);
                        foreach (Zord::value('index', 'fields') as $key => $type) {
                            $default = Zord::value('index', ['default',$key]);
                            $value = isset($part[$key]) ? $part[$key] : (isset($metadata[$key]) ? $metadata[$key] : (isset($default) ? $default : null));
                            if (isset($value)) {
                                $field = $key.Zord::value('index', ['suffix',$type]);
                                if (!is_array($value)) {
                                    $value = [$value];
                                }
                                foreach ($value as $item) {
                                    $document->addField($field, $item);
                                }
                            }
                        }
                        $update = $index->addDocument($document);
                        $response = $update->getResponse();
                        if (!$response) {
                            $this->logError('index', Zord::substitute($this->locale->messages->index->error->add), [
                                'part' => $part['name']
                            ]);
                            $result = false;
                        }
                    }
                }
                $index->commit();
            } else {
                $this->logError('index', $this->locale->messages->index->error->delete);
                $result = false;
            }
        } else {
            $this->logError('index', $this->locale->messages->index->error->nodata);
            $result = false;
        }
        return $result;
    }
    
    protected function publish($isbn) {
        if ($this->publish) {
            foreach ($this->publish as $context => $status) {
                $status = $this->status($context, $isbn);
                $context.' ('.$status.')';
                $bookInContext = (new BookHasContextEntity())->retrieve([
                    'where' => [
                        'book' => $isbn,
                        'context' => $context
                    ]
                ]);
                if ($bookInContext) {
                    if ($status !== 'no') {
                        (new BookHasContextEntity())->update(
                            ['where' => [
                                'book' => $isbn,
                                'context' => $context
                            ], 'many' => true],
                            ['status' => $status]
                        );
                    } else {
                        (new BookHasContextEntity())->delete(
                            ['where' => [
                                'book' => $isbn,
                                'context' => $context
                            ], 'many' => true]
                        );
                    }
                } else {
                    if ($status !== 'no') {
                        (new BookHasContextEntity())->create([
                            'book' => $isbn,
                            'context' => $context,
                            'status' => $status
                        ]);
                    }
                }
                $this->info(2, $context.' => '.$this->locale->messages->publish->info->status->$status);
            }
        } else {
            $this->info(2, $this->locale->messages->publish->info->none);
        }
        return true;
    }
    
    protected function facets($isbn) {
        (new BookHasFacetEntity())->delete([
            'many'  => true,
            'where' => ['book' => $isbn]
        ]);
        $metadata = Store::data($isbn, 'meta', 'array');
        $this->createSearchFacets($isbn, $metadata);
        if (isset($metadata['parts'])) {
            foreach ($metadata['parts'] as $part) {
                $this->createSearchFacets($isbn, $part);
            }
        }
        return true;
    }
    
    protected function epub($isbn) {
        $result = true;
        $metadata = Store::data($isbn, 'meta', 'array');
        $parts = isset($metadata['parts']) ? $metadata['parts'] : [];
        $medias = isset($metadata['medias']) ? $metadata['medias'] : [];
        if (isset($metadata['epub']) && !empty($metadata['epub'])) {
            $ean = $metadata['epub'];
            $this->info(2, 'ISBN : '.$ean);
            $cover = Store::media($isbn, ['epub_cover','titlepage','frontcover']);
            $cover = DATA_FOLDER.($cover === false ? 'epub'.DS.'cover.jpg' : $cover);
            if (!file_exists($cover)) {
                $this->logError('epub', $this->locale->messages->epub->error->cover->missing);
                $result = false;
            } else {
                $this->info(2, $this->locale->messages->epub->info->cover.$cover);
                list($width, $height) = getimagesize($cover);
                $errors = [];
                $values = [
                    'MIN' => ['WIDTH' => '*','HEIGHT' => '*'],
                    'MAX' => ['WIDTH' => '*','HEIGHT' => '*']
                ];
                foreach (['MIN','MAX'] as $limit) {
                    foreach (['WIDTH' => $width,'HEIGHT' => $height] as $dimension => $value) {
                        if (defined('EPUB_COVER_'.$limit.'_'.$dimension)) {
                            $values[$limit][$dimension] = constant('EPUB_COVER_'.$limit.'_'.$dimension);
                        }
                        if (is_int($values[$limit][$dimension])) {
                            if (($limit == 'MIN' && $value < $values[$limit][$dimension]) || ($limit == 'MAX' && $value > $values[$limit][$dimension])) {
                                $errors[] = $this->locale->messages->epub->error->cover->$limit->$dimension.' '.$dimension.' = '.$value.' ('.$limit.' = '.$values[$limit][$dimension].')';
                            }
                        }
                    }
                }
                if (!empty($errors)) {
                    foreach ($errors as $message) {
                        $this->logError('epub', $message);
                    }
                    $result = false;
                }
            }
            $tmpFile = DATA_FOLDER.'epub'.DS.$ean.'.tmp.epub';
            copy(Zord::getComponentPath('templates'.DS.'mimetype.epub'), $tmpFile);
            $epub = new ZipArchive(); 
            if ($epub->open($tmpFile)) {
                if (file_exists($cover)) {
                    $epub->addFile($cover, 'OPF/medias/cover.jpg');
                }
                Zord::addRecursive($epub, Zord::getComponentPath('templates'.DS.'epub'));
                $obfuscator = new Obfuscator();
                foreach (['common','screen','epub'] as $css) {
                    $file = Zord::getComponentPath('web'.DS.'css'.DS.'book'.DS.$css.'.css');
                    if (OBFUSCATE_BOOK) {
                        $file = BUILD_FOLDER.$obfuscator->getCSS($css);
                    }
                    $epub->addFromString('OPF/css/'.$css.'.css', file_get_contents($file));
                }
                $id = 1;
                $items = [];
                $navbar = [];
                foreach ($parts as &$part) {
                    if ($part['epub']) {
                        $partFile = $part['name'].'.xhtml';
                        $part['text'] = Store::data($isbn, $part['name'], 'content');
                        $partDoc = new DOMDocument();
                        $partDoc->loadXML($part['text']);
                        $partXPath = new DOMXPath($partDoc);
                        $graphics = $partXPath->query('//div[@class="graphic"][@data-url]');
                        foreach ($graphics as $graphic) {
                            $img = $partDoc->createElement('img');
                            if ($graphic->hasAttribute('data-zoom')) {
                                $graphic->insertBefore($img, $graphic->firstChild);
                                $graphic->removeAttribute('data-zoom');
                                if ($graphic->parentNode->hasAttribute('data-rend') && $graphic->parentNode->getAttribute('data-rend') == 'zoom') {
                                    $graphic->parentNode->removeAttribute('data-rend');
                                }
                            } else {
                                for ($index = 0 ; $index < $graphic->childNodes->length ; $index++) {
                                    $child = $graphic->childNodes->item($index);
                                    if ($child->nodeName == 'div' && $child->hasAttribute('class') && $child->getAttribute('class') == 'loading') {
                                        $graphic->replaceChild($img, $child);
                                        break;
                                    }
                                }
                            }
                            $img->setAttribute('src', $this->url($isbn, $graphic, 'data-url', true));
                        }
                        $anchors = $partXPath->query('//a[@href]');
                        foreach ($anchors as $anchor) {
                            $tokens = explode('#', $anchor->getAttribute('href'));
                            if (count($tokens) == 2 && isset($metadata['refs']['#'.$tokens[1]]) && $metadata['refs']['#'.$tokens[1]] == $tokens[0]) {
                                $anchor->setAttribute('href', $tokens[0].'.xhtml#'.$tokens[1]);
                            }
                        }
                        $notes = $partXPath->query('//div[@class="note"][@id]');
                        foreach ($notes as $note) {
                            $anchor = $partDoc->createElement('a');
                            $anchor->setAttribute('href', '#footref_'.$note->getAttribute('id'));
                            $anchor->appendChild($note->cloneNode(true));
                            $note->parentNode->replaceChild($anchor, $note);
                        }
                        $counters = $partXPath->query('//div[@class="footnote-counter"][@data-id]');
                        foreach ($counters as $counter) {
                            $anchor = $partDoc->createElement('a');
                            $anchor->setAttribute('href', '#'.$counter->getAttribute('data-id'));
                            $anchor->appendChild($counter->cloneNode(true));
                            $counter->parentNode->replaceChild($anchor, $counter);
                        }
                        $part['text'] = $partDoc->saveXML($partDoc->documentElement);
                        if (OBFUSCATE_BOOK) {
                            $part['text'] = $obfuscator->getXML($part['text']);
                        }
                        $epub->addFromString('OPF/'.$partFile, (new View('/epub/OPF/part.xhtml', $part))->render());
                        $items[$partFile]['id'] = 'zord-page-'.$id++;
                        $items[$partFile]['title'] = $part['title'];
                    }
                }
                foreach ($parts as &$part) {
                    if ($part['level'] <= MAX_TOC_DEPTH) {
                        $navbar[] = [
                            'id'    => $part['id'],
                            'part'  => $part['ref'],
                            'level' => $part['level'],
                            'text'  => $part['title']
                        ];
                    }
                }
                $epub->addEmptyDir('OPF/medias/'.$isbn);
                $width = Zord::parameter($this->parameters, 'EPUB_IMAGE_MAX_WIDTH', '');
                $height = Zord::parameter($this->parameters, 'EPUB_IMAGE_MAX_HEIGHT', '');
                foreach ($medias as $file) {
                    if (file_exists($file)) {
                        $path = substr($file, strlen(DATA_FOLDER));
                        if (defined("RESIZE_COMMAND")) {
                            $resized = DATA_FOLDER.'epub'.DS.$path;
                            $dir = dirname($resized);
                            if (!file_exists($dir)) {
                                mkdir($dir, 0777, true);
                            }
                            copy($file, $resized);
                            shell_exec(Zord::substitute(RESIZE_COMMAND, [
                                'FILE'   => $resized,
                                'WIDTH'  => $width,
                                'HEIGHT' => $height
                            ]));
                            $file = $resized;
                        }
                        $epub->addFile($file, 'OPF/'.$path);
                        $items[$path]['id'] = 'zord-media-'.$id++;
                    }
                }
                $models = [
                    'metadata' => $metadata,
                    'navbar'   => $navbar,
                    'items'    => $items
                ];
                foreach (['navigation.xhtml','navigation.ncx','package.opf'] as $metaFile) {
                    $epub->addFromString('OPF/'.$metaFile, (new View('/epub/OPF/'.$metaFile, $models))->render());
                }
                if ($epub->close()) {
                    $command = Zord::substitute(EPUBCHECK_COMMAND, [
                        'LANG' => $this->lang,
                        'EPUB' => $tmpFile
                    ]);
                    $errors = shell_exec($command);
                    if (substr($errors, 0, strlen($this->locale->messages->epub->info->ok)) == $this->locale->messages->epub->info->ok) {
                        $epubFile = DATA_FOLDER.'epub'.DS.$ean.'.epub';
                        rename($tmpFile, $epubFile);
                    } else {
                        foreach (explode("\n", $errors) as $error) {
                            $this->logError('epub', str_replace($tmpFile.DS, '', $error));
                        }
                        $result = false;
                    }
                } else {
                    $this->logError('epub', Zord::substitute($this->locale->messages->epub->error->close, [
                        'file' => $tmpFile
                    ]));
                    $result = false;
                }
                Zord::resetFolder(DATA_FOLDER.'epub'.DS.'medias', false);
            } else {
                $this->logError('epub', Zord::substitute($this->locale->messages->epub->error->open, [
                    'file' => $tmpFile
                ]));
                $result = false;
            }
        } else {
            $this->info(2, $this->locale->messages->epub->info->noepub);
        }
        return $result;
    }
    
    protected function header($isbn) {
        $this->info(2, $this->locale->messages->metadata->info->from->header);
        if (isset($this->document)) {
            $headerElements = $this->document->getElementsByTagName('teiHeader');
            if ($headerElements->length == 1) {
                $header = str_replace(' xmlns="'.NS_URL.'"', '', $headerElements->item(0)->C14N());
                return $header;
            }
        }
        $this->warn(3, $this->locale->messages->metadata->warning->from->nodata);
        return false;
    }
    
    protected function extraPart($part) {
        return false;
    }
    
    protected function handlePart(&$part, $isbn, $folder) {
        $result = true;
        if (empty($part['title'])) {
            $this->xmlError('slice', $part['line'], $this->locale->messages->slice->error->notitle);
            $result = false;
        } else {
            if ($part['name'] == 'home') {
                $part['link'] = $isbn.'/home';
                $this->metadata['ariadne'][] = [
                    'id'    => $part['node']->getAttribute('id'),
                    'link'  => $isbn,
                    'title' => $part['title']
                ];
            } else {
                $element = null;
                if ($part['level'] == 1) {
                    $element = $this->tocXPath->query('//ul[@id="tocTEI_' . $part['part'] . '"]')[0];
                } else if ($part['level'] <= MAX_TOC_DEPTH) {
                    $parent = $this->tocXPath->query('//li[@data-id="' . $part['parent']->getAttribute('id') . '"]');
                    if ($parent->length == 1) {
                        $container = $this->tocXPath->query('//li[@data-id="' . $part['parent']->getAttribute('id') . '"]/ul');
                        if ($container->length == 1) {
                            $element = $container[0];
                        } else {
                            $element = $this->toc->createElement('ul');
                            $parent[0]->appendChild($element);
                        }
                    }
                }
                if ($element != null) {
                    $partTitles = [$part['title']];
                    if ($this->is_fragment($part)) {
                        $part['link'] = $isbn.'/'.$part['name'];
                    } else {
                        $part['link'] = $element->parentNode->getAttribute('data-part');
                    }
                    $toDo = !in_array($part['name'], $this->dones);
                    if ($toDo) {
                        if (isset($this->metadata['visavis'])) {
                            foreach ($this->metadata['visavis'] as $group) {
                                if (in_array($part['name'], $group)) {
                                    $toDo = ($part['name'] == $group[0]);
                                    $this->dones = array_merge($this->dones, $group);
                                    $partTitles = [];
                                    foreach ($this->metadata['parts'] as $_part) {
                                        if (in_array($_part['name'], $group)) {
                                            $partTitles[] = $_part['title'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if ($toDo) {
                        $li = $this->toc->createElement('li');
                        $element->appendChild($li);
                        $li->setAttribute('data-part', $part['link']);
                        $li->setAttribute('data-id', $part['id']);
                        $li->setAttribute('data-level', $part['level']);
                        $title = htmlspecialchars(implode(' | ', $partTitles));
                        $span = $this->toc->createElement('span', $title);
                        $li->appendChild($span);
                        $pageInAriadne = false;
                        foreach ($this->metadata['ariadne'] as $entry) {
                            if ($entry['link'] == $part['link']) {
                                $pageInAriadne = true;
                                break;
                            }
                        }
                        if (!$pageInAriadne) {
                            $this->metadata['ariadne'][] = [
                                'id'    => $part['id'],
                                'link'  => $part['link'],
                                'title' => $title
                            ];
                        }
                    }
                }
            }
        }
        if ($this->is_fragment($part)) {
            $partText = new DOMDocument();
            $partText->preserveWhiteSpace = false;
            $partText->formatOutput = true;
            $partText->loadXML('<div'.$this->xmlns.' class="text"></div>');
            $partXPath = new DOMXPath($partText);
            $node = $partText->importNode($part['node'], true);
            if ($node) {
                $partText->documentElement->appendChild($node);
                $subs = $partXPath->query('/div[@class="text"]/'.$this->prefix.':div[@type]/'.$this->prefix.':div[@type]');
                foreach ($subs as $sub) {
                    foreach ($this->metadata['parts'] as $entry) {
                        if ($entry['id'] == $sub->getAttribute('id')) {
                            if ($this->is_fragment($entry)) {
                                $sub->parentNode->removeChild($sub);
                            }
                            break;
                        }
                    }
                }
                $graphics = $partXPath->query('//'.$this->prefix.':graphic');
                foreach ($graphics as $graphic) {
                    $url = $graphic->getAttribute('url');
                    if (in_array($url, $this->metadata['zoom'])) {
                        $graphic->setAttribute('data-zoom', '/zoom/'.$isbn.'/'.str_replace('jpg', 'dzi', $url));
                    } else {
                        $imgFile = $this->metadata['medias'][$url];
                        $loading = $partText->createElement($this->prefix.':loading');
                        $height = min([file_exists($imgFile) ? getimagesize($imgFile)[1] : GRAPHIC_MAX_HEIGHT, GRAPHIC_MAX_HEIGHT]);
                        $loading->setAttribute('style', 'height:'.$height.'px;');
                        $graphic->appendChild($loading);
                    }
                }
                $refs = $partXPath->query('//'.$this->prefix.':ref[@target]');
                foreach ($refs as $ref) {
                    $target = $ref->getAttribute('target');
                    $anchor = $partText->createElement('a');
                    $matches = [];
                    if (preg_match('/#(\d{13}):(.*)/', $target, $matches)) {
                        $metadata = Store::data($matches[1], 'meta', 'array');
                        if (isset($metadata['refs'])) {
                            $extRefs = $metadata['refs'];
                            if (isset($extRefs['#'.$matches[2]])) {
                                $base = $this->base($matches[1]);
                                $anchor->setAttribute('href', ($base !== false ? $base : '').'/book/'.$matches[1].'/'.$extRefs['#'.$matches[2]].'#'.$matches[2]);
                            }
                        }
                    } else if (substr($target, 0, 1) == '#' && isset($this->metadata['refs'][$target])) {
                        $anchor->setAttribute('href', $this->metadata['refs'][$target].$target);
                    } else if (substr($target, 0, 4) == 'http') {
                        $anchor->setAttribute('href', $target);
                    }
                    if ($anchor->hasAttribute('href')) {
                        for ($i = 0; $i < $ref->childNodes->length; $i++) {
                            $anchor->appendChild($ref->removeChild($ref->childNodes->item(0)));
                        }
                        $ref->appendChild($anchor);
                    }
                }
                $partNotes = new DOMDocument();
                $partNotes->preserveWhiteSpace = false;
                $partNotes->formatOutput = true;
                $partNotes->loadXML('<div class="footnotes"></div>');
                $elements = $partXPath->query('//'.$this->prefix.':note | //'.$this->prefix.':pb[@n]');
                if ($elements) {
                    $pages = [];
                    foreach ($elements as $element) {
                        if ($element->nodeName == $this->prefix.':pb') {
                            $number = $element->getAttribute('n');
                            if (!$element->hasAttribute('rend') || $element->getAttribute('rend') !== 'temoin') {
                                $this->page = $number;
                            }
                        } else if ($element->nodeName == $this->prefix.':note') {
                            $id = $element->getAttribute('xml:id');
                            $name = $element->getAttribute('n');
                            if (!in_array($this->page, $pages)) {
                                $page = $partNotes->createElement('div', 'p.'.$this->page);
                                $page->setAttribute('class', 'foot-page');
                                $partNotes->documentElement->appendChild($page);
                                $pages[] = $this->page;
                            }
                            $ref = $partNotes->createElement('div');
                            $ref->setAttribute('id', 'footref_'.$id);
                            $counter = $partNotes->createElement('div', $name);
                            $counter->setAttribute('class', 'footnote-counter');
                            $counter->setAttribute('data-id', $id);
                            $ref->appendChild($counter);
                            $note = $partNotes->createElement('div');
                            $note->setAttribute('class', 'footnote-note');
                            $content = $partNotes->importNode($element, true);
                            if ($content) {
                                $content->removeAttribute('xml:id');
                                $this->renameAttributes($content, 'xml:id', 'id');
                                $note->appendChild($content);
                            }
                            $ref->appendChild($note);
                            $partNotes->documentElement->appendChild($ref);
                        }
                    }
                }
                $partXML = new DOMDocument();
                $partXML->preserveWhiteSpace = false;
                $partXML->formatOutput = true;
                $partXML->loadXML('<'.$this->prefix.':tei'.$this->xmlns.'></'.$this->prefix.':tei>');
                $textElement = $partXML->importNode($partText->documentElement, true);
                $partXML->documentElement->appendChild($textElement);
                $notesElement = $partXML->importNode($partNotes->documentElement, true);
                $partXML->documentElement->appendChild($notesElement);
                $part['content'] = $this->tei2html(str_replace(
                    "xml:id", "id",
                    $partXML->saveXML()
                ));
                file_put_contents($folder.DS.$part['name'].'.xhtml', $part['content']);
            }
        }
        $part['index'] = $this->is_fragment($part);
        $part['epub']  = $this->is_fragment($part, false);
        return $result;
    }
    
    protected function is_fragment($part, $allvisavis = true) {
        return 
            $part['name'] == 'home' || 
            in_array($part['type'], Zord::value('import', ['types','fragment'])) ||
            (isset($part['synch']) && ($allvisavis || !isset($part['corresp'])));
    }
    
    protected function xmlError($step, $element, $message, $parameters = null) {
        if (isset($parameters) && is_array($parameters)) {
            $message = Zord::substitute($message, $parameters);
        }
        $this->logError($step, $this->locale->line.' '.(is_int($element) ? $element : $element->getLineNo()).' : '.$message);
    }
    
    private function createSearchFacets($isbn, $source) {
        foreach (Zord::value('search', 'facets') as $facet) {
            $values = isset($source[$facet]) ? $source[$facet] : [];
            if (!is_array($values)) {
                $values = [$values];
            }
            foreach ($values as $value) {
                if (!isset($this->facets[$isbn][$facet][$value])) {
                    (new BookHasFacetEntity())->create([
                        'book'  => $isbn,
                        'facet' => $facet,
                        'value' => $value
                    ]);
                    $this->facets[$isbn][$facet][$value] = 'done';
                }
            }
        }
    }
    
    private function loadXML(&$document, $content) {
        libxml_clear_errors();
        $previous = libxml_use_internal_errors(true);
        if (file_exists($content)) {
            $document->load($content);
        } else {
            $document->loadXML($content);
        }
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
    }
    
    private function status($context, $isbn) {
        if (isset($this->publish) && isset($this->publish[$context])) {
            if (is_array($this->publish[$context])) {
                if (isset($this->publish[$context][$isbn])) {
                    return $this->publish[$context][$isbn];
                }
            } else {
                return $this->publish[$context];
            }
        }
        return 'no';
    }
    
    private function addPart($name, $type, $title, $base, $part, $level, $node) {
        $contentType = 0;
        foreach (Zord::value('import', 'contents') as $index => $types) {
            if (in_array($type, $types)) {
                $contentType = $index + 1;
                break;
            }
        }
        $newPart = [
            'name'        => $name,
            'id'          => $node->getAttribute('id'),
            'base'        => $base,
            'title'       => $title,
            'type'        => $type,
            'contentType' => $contentType,
            'count'       => $this->idCount,
            'node'        => $node,
            'level'       => $level,
            'part'        => $part,
            'parent'      => $node->parentNode,
            'line'        => $node->getLineNo()
        ];
        foreach (['synch', 'corresp'] as $attribute) {
            if ($node->hasAttribute($attribute)) {
                $newPart[$attribute] = $node->getAttribute($attribute);
            }
        }
        $extra = $this->extraPart($newPart);
        if (is_array($extra)) {
            foreach ($extra as $key => $value) {
                $newPart[$key] = $value;
            }
        }
        $newPart['ref'] = $this->getRefName($newPart);
        $this->metadata['parts'][] = $newPart;
        return $newPart;
    }
    
    private function getRefName($part) {
        if ($this->is_fragment($part, false)) {
            return $part['name'];
        } else {
            if (isset($this->metadata['parts'])) {
                foreach ($this->metadata['parts'] as $base) {
                    if ($base['name'] == $part['base']) {
                        return $this->getRefName($base);
                    }
                }
            }
        }
    }
    
    private function scan($node, $base, $level) {
        $divs = $this->adjXPath->query('//'.$this->prefix.':'.$node.'/'.$this->prefix.':div[@type]');
        $visavis = array();
        for ($index = 0 ; $index < $divs->length ; $index++) {
            $div = $divs[$index];
            $name = $base.'-'.($index + 1);
            $type = $div->getAttribute('type');
            $id = $div->getAttribute('id');
            if ($id == null) {
                $this->idCount++;
                $id = PART_ID_PREFIX.$this->idCount;
                $div->setAttribute('id', $id);
                if ($div->getAttribute('xml:id')) {
                    $div->removeAttribute('xml:id');
                }
            }
            if ($div->hasAttribute('synch')) {
                $group = $div->hasAttribute('corresp') ? $div->getAttribute('corresp') : $div->parentNode->getAttribute('id');
                $visavis[$group][] = $name;
            }
            $title = '';
            foreach ($div->childNodes as $child) {
                if ($child->localName == 'head') {
                    foreach ($child->childNodes as $grandChild) {
                        $isTextNode  = $grandChild->nodeType == XML_TEXT_NODE;
                        $isValidTag  = in_array($grandChild->localName, ['hi','emph']);
                        $isSeparator = in_array($grandChild->localName, ['lb']);
                        if ($isSeparator) {
                            $title .= ' ';
                        }
                        if ($isTextNode || $isValidTag) {
                            $title .= ($isTextNode ? ' ' : '').$grandChild->textContent;
                        }
                    }
                }
            }
            $title = Library::compact($title);
            if ($title == '' && $div->hasAttribute('synch')) {
                $title = $div->getAttribute('synch');
            }
            $part = $this->addPart($name, $type, $title, $base, $node, $level, $div);
            foreach ($this->adjXPath->query('//'.$this->prefix.':div[@id="'.$id.'"]//*[@xml:id]') as $element) {
                $this->metadata['refs']['#'.$element->getAttribute('xml:id')] = $part['ref'];
            }
            if (in_array($type, Zord::value('import', ['types','fragment'])) || in_array($type, Zord::value('import', ['types','toc']))) {
                $this->scan('div[@id="'.$id.'"]', $name, $level + 1);
            }
        }
        if (count($visavis) > 0) {
            foreach ($visavis as $name => $group) {
                foreach($group as $entry) {
                    $this->metadata['visavis'][$name][] = $entry;
                }
            }
        }
    }
    
    private function tei2html($content) {
        if (!empty($content)) {
            $fragment = new DOMDocument();
            $fragment->preserveWhiteSpace = false;
            $fragment->formatOutput = true;
            $fragment->loadXML($content);
            $fragXPath = new DOMXPath($fragment);
            $elements = $fragXPath->query('//*');
            foreach ($elements as $element) {
                $tag = explode(':', $element->nodeName);
                if (count($tag) == 2 && $tag[0] == $this->prefix) {
                    $parent = $element->parentNode;
                    if ($parent) {
                        if ($tag[1] == 'fw') {
                            $parent->removeChild($element);
                        }
                        if ($tag[1] == 'note' && $parent->nodeName !== 'div') {
                            $count = $element->childNodes->length;
                            for ($i = 0; $i < $count; $i++) {
                                $element->removeChild($element->childNodes->item(0));
                            }
                        }
                    }
                    $attributes = [];
                    foreach ($element->attributes as $name => $attribute) {
                        $attributes[$name] = $attribute->value;
                    }
                    foreach ($attributes as $name => $value) {
                        if (!in_array($name, ['id','class','style']) && substr($name, 0, 5) !== 'data-') {
                            $element->removeAttribute($name);
                            if ($tag[1] == 'cell' && in_array($name, ['rows','cols'])) {
                                $name = $name.'pan';
                            } else {
                                $name = 'data-'.$name;
                            }
                            $element->setAttribute($name, $value);
                        }
                    }
                }
            }
            $fragment->loadXML(preg_replace_callback(
                '#</?'.$this->prefix.':(\w+)#',
                function ($matches) {
                    $start = (substr($matches[0], 1, 1) != '/');
                    $type = $matches[1];
                    $tags = [
                        'table' => 'table',
                        'row'   => 'tr',
                        'cell'  => 'td'
                    ];
                    $tag = isset($tags[$type]) ? $tags[$type] : 'div';
                    return $start ? '<'.$tag.($tag == 'div' ? ' class="'.$type.'"' : '') : '</'.$tag;
                },
                preg_replace(
                    ['#'.preg_quote($this->xmlns).'#','#<'.$this->prefix.':lb><\/'.$this->prefix.':lb>#'],
                    ['','<br/>'],
                    $fragment->saveXML()
                )
            ));
            $fragXPath = new DOMXPath($fragment);
            $tableHeads = $fragXPath->query('//table/div[@class="head"]');
            for ($index = 0 ; $index < $tableHeads->length ; $index++) {
                $tableHead = $tableHeads->item($index);
                $parent = $tableHead->parentNode;
                $children = [];
                foreach($tableHead->childNodes as $child) {
                    $children[] = $child;
                }
                $caption = $fragment->createElement('caption');
                foreach($children as $child) {
                    $child = $fragment->importNode($child, true);
                    $caption->appendChild($child);
                }
                foreach ($tableHead->attributes as $name => $attribute) {
                    if ($name != 'class') {
                        $caption->setAttribute($name, $attribute->nodeValue);
                    }
                }
                $parent->replaceChild($caption, $tableHead);
            }
            $tablePageBreaks = $fragXPath->query('//table/div[@class="pb"]');
            foreach ($tablePageBreaks as $tablePageBreak) {
                $parent = $tablePageBreak->parentNode;
                $row = $fragment->createElement('tr');
                $cell = $fragment->createElement('td');
                $row->appendChild($cell);
                $cell->appendChild($tablePageBreak->cloneNode(true));
                $parent->replaceChild($row, $tablePageBreak);
            }
            return preg_replace(
                [
                    '#<div class="lb"></div>#',
                    '#(\s*)<div class="note"#',
                    '#(\s*),#',
                    '#(\s*)\)#',
                    '#\((\s*)#',
                    '#(\s+):(\s+)#',
                ],
                [
                    '<br/>',
                    '<div class="note"',
                    ',',
                    ')',
                    '(',
                    ' : '
                ],
                /*
                preg_replace_callback(
                    '/<div[\w|\s|\x22|\x23|\x26|\x27|\x28|\x29|\x2A|\x2C|\x2D|\x2E|\x2F|\x3A|\x3B|\x3D|\x5B|\x5D|\x5F]+\/>/',
                    function ($matches) {
                        return substr($matches[0], 0, strlen($matches[0]) - 2)."></div>";
                    },
                    $fragment->saveXML($fragment->documentElement)
                )
                */
                $fragment->saveXML($fragment->documentElement, LIBXML_NOEMPTYTAG)
            );
        }
    }
    
    private function renameAttributes($element, $old, $new) {
        if ($element->hasAttribute($old)) {
            $element->setAttribute($new, $element->getAttribute($old));
            $element->removeAttribute($old);
        }
        foreach ($element->childNodes as $child) {
            if ($child->nodeType == XML_ELEMENT_NODE) {
                $this->renameAttributes($child, $old, $new);
            }
        }
    }
    
    private function url($isbn, $graphic, $name, $relative = false) {
        if ($graphic->hasAttribute($name)) {
            $url = $graphic->getAttribute($name);
            if (substr($url, 0, 1) !== '/') {
                $url = '/'.$isbn.'/'.$url;
            }
            return ($relative ? '' : '/').'medias'.$url;
        }
        return null;
    }
    
    private function base($isbn) {
        if (!isset($this->base[$isbn])) {
            $book = (new BookHasContextEntity())->retrieve([
                'where' => ['book' => $isbn]
            ]);
            if ($book) {
                $context = $book->context;
                $url = Zord::value('context', [$context,'url'])[0];
                $host = $url['host'];
                $path = $url['path'];
                $secure = isset($url['secure']) ? $url['secure'] : false;
                $this->base[$isbn] = ($secure ? 'https' : 'http').'://'.$host.($path !== '/' ? $path : '');
            } else {
                $this->base[$isbn] = false;
            }
        }
        return $this->base[$isbn];
    }
}

?>
