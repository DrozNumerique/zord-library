<?php

class LibraryImport extends Import {
    
    private static $XML_PARSE_BIG_LINES = 4194304;

    protected $adjust     = NS_ADJUST;
    protected $prefix     = NS_PREFIX;
    protected $namespace  = NS_URL;
    protected $xmlns      = ' xmlns:'.NS_PREFIX.'="'.NS_URL.'"';
    protected $publish    = null;
    protected $base       = [];
    protected $deep       = [];
    
    protected $size     = 0;
    protected $total    = 0;
    protected $facets   = [];
    
    protected $xml      = null;
    protected $document = null;
    protected $adjusted = null;
    protected $metadata = null;
    protected $medias   = null;
    protected $zoom     = null;
    protected $parts    = null;
    protected $anchors  = null;
    protected $visavis  = null;
    protected $ariadne  = null;
    protected $idCount  = 1;
    protected $dones    = [];
    protected $xpath    = null;
    protected $ajdXpath = null;
    protected $toc      = null;
    protected $tocXPath = null;
    protected $page     = null;
    protected $check    = true;
    
    protected function contents($ean) {
        $metadata = Library::data($ean, 'metadata.json', 'array');
        $parts = Library::data($ean, 'parts.json', 'array');
        $contents = [];
        if (isset($parts)) {
            foreach ($parts as $part) {
                if ($part['index']) {
                    $part['content'] = Store::align(Library::data($ean, $part['name'].'.xhtml', 'content'), 'xhtml');
                    foreach ($metadata as $name => $value) {
                        if (!array_key_exists($name, $part)) {
                            $part[$name] = $value;
                        }
                    }
                    $contents[] = $part;
                }
            }
        }
        return $contents;
    }
    
    protected function configure($parameters = []) {
        parent::configure($parameters);
        if (isset($parameters['books'])) {
            $this->refs = $parameters['books'];
        }
        if (isset($parameters['publish'])) {
            $this->publish = $parameters['publish'];
        }
        if (isset($parameters['adjust'])) {
            $this->adjust = $parameters['adjust'];
        }
        if (isset($parameters['prefix'])) {
            $this->adjust = $parameters['prefix'];
        }
        if (isset($parameters['check'])) {
            $this->check = $parameters['check'];
        }
        if (isset($parameters['zoom'])) {
            $this->deep = $parameters['zoom'];
        }
        if (!isset($this->publish) && file_exists($this->folder.'publish.json')) {
            $this->publish = Zord::arrayFromJSONFile($this->folder.'publish.json');
        }
        if (!isset($this->refs)) {
            $set = $this->folder.'*';
            $xmls = glob($set.'.xml');
            $medias = glob($set, GLOB_ONLYDIR);
            $this->refs = [];
            foreach ([$xmls, $medias] as $items) {
                foreach ($items as $item) {
                    $book = pathinfo($item, PATHINFO_FILENAME);
                    if (!in_array($book, $this->refs)) {
                        $this->refs[] = $book;
                    }
                }
            }
        } else if (count($this->refs) === 1 && ($this->refs[0] === 'all' || substr($this->refs[0], 0, 1) === '+')) {
            $greater = substr($this->refs[0], 0, 1) === '+' ? substr($this->refs[0], 1) : '0000000000000';
            $this->refs = [];
            foreach ((new BookEntity())->retrieveAll(['order' => ['asc' => 'ean']]) as $book) {
                if ($book->ean > $greater) {
                    $this->refs[] = $book->ean;
                }
            }
        }
    }
    
    protected function preRefs() {
        if (in_array('slice', $this->steps)) {
            foreach ($this->refs as $ean) {
                $text = $this->folder.$ean.'.xml';
                if (file_exists($text)) {
                    $this->total = $this->total + filesize($text);
                }
            }
        }
    }
    
    protected function resetRef($ean) {
        parent::resetRef($ean);
        $folder = Library::data($ean);
        if (!file_exists($folder)) {
            mkdir($folder);
        }
        $text = $this->folder.$ean.'.xml';
        if ($this->total > 0 && file_exists($text)) {
            $this->size += filesize($text);
            $this->progress = $this->size / $this->total;
        }
        $this->xml = $this->folder.$ean.'.xml';
        $this->document = null;
        $this->adjusted = null;
        $this->metadata = null;
        $this->medias = null;
        $this->zoom = null;
        $this->parts = null;
        $this->anchors = null;
        $this->visavis = null;
        $this->ariadne = null;
        $this->xpath = null;
        $this->adjXPath = null;
        $this->toc = null;
        $this->tocXPath = null;
        $this->idCount = 1;
        $this->page = null;
        $this->dones = [];
    }
    
    protected function grant($ean) {
        $entity = (new BookHasContextEntity())->retrieve([
            'where' => ['book' => $ean],
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
                    $this->logError('grant', Zord::substitute($this->locale->messages->grant->error->noadmin, [
                        'context' => $name,
                        'name'    => Zord::getLocaleValue('title', Zord::value('context', $name), $this->lang)
                    ]));
                }
                return false;
            }
        }
        return true;
    }
    
    protected function folders($ean) {
        return [
            $this->folder.$ean,
            Store::resource('medias', $ean)
        ];
    }
    
    protected function load($ean) {
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
    
    protected function metadata($ean) {
        if ($header = $this->header($ean)) {
            file_put_contents(Library::data($ean, 'header.xml'), $header);
            $header = simplexml_load_string($header);
            $uuid = md5($header);
            $metadata = [
                'ean'    => $ean,
                'format' => 'text/html',
                'uuid'   => substr($uuid, 0, 8).'-'.substr($uuid, 8, 4).'-'.substr($uuid, 12, 4).'-'.substr($uuid, 16, 4).'-'.substr($uuid, 20, 12),
                'uri'    => OPENURL.'?id='.$ean,
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
            if (isset($header->fileDesc->sourceDesc->biblFull->publicationStmt->idno)) {
                foreach ($header->fileDesc->sourceDesc->biblFull->publicationStmt->idno as $id) {
                    if (isset($id['type']) && $id['type'] == 'ISBN-13') {
                        $metadata['isbn'] = $id.'';
                    }
                }
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
            file_put_contents(Library::data($ean, 'metadata.json'), Zord::json_encode($metadata));
            $book = [
                "ean"      => $ean,
                "title"    => $metadata['title'] ?? '',
                "subtitle" => $metadata['subtitle'] ?? '',
                "creator"  => $metadata['creator'] ?? [],
                "editor"   => $metadata['editor'] ?? [],
                "category" => $metadata['category'] ?? [],
                "number"   => $metadata['category_number'] ?? 0,
                "date"     => $metadata['date'] ?? '',
                "s_from"   => $metadata['creation_date_from'] ?? '',
                "s_to"     => $metadata['creation_date_to'] ?? ''
            ];
            if ((new BookEntity())->retrieve($ean)) {
                (new BookEntity())->update($ean, $book);
            } else {
                (new BookEntity())->create($book);
            }
            return true;
        } else {
            $this->logError('metadata', $this->locale->messages->metadata->error->missing);
            return false;
        }
    }
    
    protected function validate($ean) {
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
            $this->medias = [];
            $this->zoom = [];
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
                                $fragments = Zord::value('import', ['types','fragment']);
                                if (in_array($type, $fragments)) {
                                    $parent = $element->parentNode;
                                    while (isset($parent)) {
                                        if ($parent->localName === 'div') {
                                            $_type = $parent->hasAttribute('type') ? $parent->getAttribute('type') : null;
                                            if (in_array($_type, $fragments)) {
                                                $this->xmlError('validate', $element, $this->locale->messages->validate->error->parent, ['type' => $type, 'parent' => $_type]);
                                                $result = false;
                                            }
                                        }
                                        $parent = $parent->parentNode;
                                    }
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
                                $imgFile = STORE_FOLDER.str_replace('/', DS, $this->url($ean, $element, 'url'));
                                $this->medias[$url] = $imgFile;
                                if (!file_exists($imgFile)) {
                                    $this->xmlError('validate', $element, $this->locale->messages->validate->error->missing.$url);
                                    $result = false;
                                }
                                if ($element->parentNode->nodeName == 'figure' &&
                                    $element->parentNode->hasAttribute('rend') &&
                                    in_array($element->parentNode->getAttribute('rend'), ['zoom','facsimile'])) {
                                    $this->zoom[] = $url;
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
                                if (!$element->hasAttribute('rend') || ($element->getAttribute('rend') !== 'temoin' && $element->getAttribute('rend') !== 'block')) {
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
            foreach (['medias','zoom'] as $data) {
                file_put_contents(Library::data($ean, $data.'.json'), Zord::json_encode($this->$data));
            }
        } else {
            $this->logError('validate', $this->locale->messages->validate->error->nodata);
            $result = false;
        }
        return $result;
    }
    
    protected function adjust($ean) {
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
            
    protected function slice($ean) {
        $result = true;
        $this->metadata = Library::data($ean, 'metadata.json', 'array');
        foreach(['parts','anchors','ariadne','visavis'] as $name) {
            $this->$name = [];
        }
        if (file_exists($this->xml) && isset($this->adjusted) && isset($this->adjXPath) && isset($this->metadata)) {
            
            $this->toc = new DOMDocument();
            $this->toc->preserveWhiteSpace = false;
            $this->toc->formatOutput = true;
            $this->toc->loadXML(Zord::substitute(file_get_contents(Zord::getComponentPath('xml'.DS.'toc.xhtml')), ['isbn' => $ean]));
            $this->tocXPath = new DOMXPath($this->toc);
                        
            $titlePage = $this->adjXPath->query('//'.$this->prefix.':front/'.$this->prefix.':titlePage')[0];
            $titlePage->setAttribute('id', PART_ID_PREFIX.$this->idCount);
            $title = isset($this->metadata['title']) && !empty($this->metadata['title']) ? $this->metadata['title'] : $this->locale->titlePage;
            $this->addPart('home', 'title', $title, $title, 'front', 'front', 1, $titlePage);            
            foreach (['front','body','back'] as $tag) {
                $this->scan($tag, $tag, 1);
            }
            
            $folder    = Library::data($ean);
            $tmpFolder = substr($folder, 0, -1).'.tmp'.DS;
            Zord::resetFolder($tmpFolder);
            
            foreach ($this->parts as &$part) {
                $result &= $this->handlePart($part, $ean, $tmpFolder);
                foreach (['node','parent','part','content'] as $useless) {
                    unset($part[$useless]);
                }
            }
            $book = STORE_FOLDER.'books'.DS.$ean.'.xml';
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
            file_put_contents($tmpFolder.'book.xml', $book->saveXML());
            foreach (['metadata','medias','zoom'] as $data) {
                if (file_exists($folder.$data.'.json')) {
                    copy($folder.$data.'.json', $tmpFolder.$data.'.json');
                }
            }
            foreach (['parts','anchors','visavis','ariadne'] as $data) {
                file_put_contents($tmpFolder.$data.'.json', Zord::json_encode($this->$data));
            }
            file_put_contents($tmpFolder.'toc.xhtml', $this->toc->saveXML($this->toc->documentElement));
            
            Zord::deleteRecursive($folder);
            rename($tmpFolder, $folder);
            
            (new BookHasPartEntity())->delete([
                'where' => ['book' => $ean],
                'many'  => true
            ]);
            foreach ($this->parts as &$part) {
                (new BookHasPartEntity())->create([
                    'book'  => $ean,
                    'part'  => $part['name'],
                    'count' => $part['count'],
                    'data'  => $part
                ]);
                $ref = Library::data($ean, $part['ref'].'.xhtml');
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
    
    protected function zoom($ean) {
        $medias = Library::data($ean, 'medias.json', 'array');
        $zoom = Library::data($ean, 'zoom.json', 'array');
        if (!empty($zoom)) {
            $deepzoom = new Deepzoom(Zord::getConfig('zoom'));
            $this->info(2, $this->locale->messages->image->info->processor.$deepzoom->processor);
            if ($deepzoom->processor == ImageProcessor::$DEFAULT_PROCESSOR) {
                $this->info(2, $this->locale->messages->image->info->convert.$deepzoom->convert);
            }
            $this->info(2, $this->locale->messages->image->info->folder.STORE_FOLDER.'medias'.DS.$ean);
            $folder = STORE_FOLDER.'zoom'.DS.$ean.'.tmp';
            Zord::deleteRecursive($folder);
            $count = 0;
            foreach($zoom as $url) {
                $file = $medias[$url];
                $count++;
                $this->info(3, basename($file).Zord::str_pad($count.' / '.count($zoom), 20, '.', STR_PAD_LEFT), false, false, true);
                if (empty($this->deep) || (isset($this->deep[$ean]) && in_array($url, $this->deep[$ean]))) {
                    $deepzoom->process($file, $folder);
                }
            }
            $this->info(2, Zord::substitute($this->locale->messages->image->info->count, ['count' => $count]), true, ' ');
            $folder = STORE_FOLDER.'zoom'.DS.$ean;
            if (empty($this->deep)) {
                Zord::deleteRecursive($folder);
                rename($folder.'.tmp', $folder);
            } else {
                foreach (glob($folder.'.tmp'.DS.'*') as $file) {
                    copy($file, $folder.DS.basename($file));
                }
                Zord::deleteRecursive($folder.'.tmp');
            }
        } else {
            $this->info(2, $this->locale->messages->zoom->info->none);
        }
        return true;
    }
    
    protected function publish($ean) {
        if ($this->publish) {
            foreach ($this->publish as $context => $status) {
                $status = $this->status($context, $ean);
                $context.' ('.$status.')';
                $bookInContext = (new BookHasContextEntity())->retrieve([
                    'where' => [
                        'book' => $ean,
                        'context' => $context
                    ]
                ]);
                if ($bookInContext) {
                    if ($status !== 'no') {
                        (new BookHasContextEntity())->update(
                            ['where' => [
                                'book' => $ean,
                                'context' => $context
                            ], 'many' => true],
                            ['status' => $status]
                        );
                    } else {
                        (new BookHasContextEntity())->delete(
                            ['where' => [
                                'book' => $ean,
                                'context' => $context
                            ], 'many' => true]
                        );
                    }
                } else {
                    if ($status !== 'no') {
                        (new BookHasContextEntity())->create([
                            'book' => $ean,
                            'context' => $context,
                            'status' => $status
                        ]);
                        $this->postPublish($ean);
                    }
                }
                $this->info(2, $context.' => '.$this->locale->messages->publish->info->status->$status);
            }
        } else {
            $this->info(2, $this->locale->messages->publish->info->none);
        }
        return true;
    }
    
    protected function postPublish($ean) {
        return Library::postPublish($ean);
    }
    
    protected function facets($ean) {
        (new BookHasFacetEntity())->delete([
            'many'  => true,
            'where' => ['book' => $ean]
        ]);
        $metadata = Library::data($ean, 'metadata.json', 'array');
        $parts = Library::data($ean, 'parts.json', 'array');
        $this->createSearchFacets($ean, $metadata);
        if (isset($parts)) {
            foreach ($parts as $part) {
                $this->createSearchFacets($ean, $part);
            }
        }
        return true;
    }
    
    protected function epub($ean) {
        $result = true;
        $metadata = Library::data($ean, 'metadata.json', 'array');
        $medias = Library::data($ean, 'medias.json', 'array');
        $parts = Library::data($ean, 'parts.json', 'array');
        $anchors = Library::data($ean, 'anchors.json', 'array');
        if (isset($metadata['epub']) && !empty($metadata['epub'])) {
            $eanEPUB = $metadata['epub'];
            $this->info(2, 'EAN : '.$eanEPUB);
            $cover = Store::resource('medias', $ean, ['epub_cover','titlepage','frontcover']);
            $cover = STORE_FOLDER.($cover === false ? 'epub'.DS.'cover.jpg' : $cover);
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
            $tmpFile = STORE_FOLDER.'epub'.DS.$eanEPUB.'.tmp.epub';
            copy(Zord::getComponentPath('templates'.DS.'mimetype.epub'), $tmpFile);
            $epub = new ZipArchive(); 
            if ($epub->open($tmpFile)) {
                if (file_exists($cover)) {
                    $epub->addFile($cover, 'OPF/medias/cover.jpg');
                }
                Zord::addRecursive($epub, Zord::getComponentPath('templates'.DS.'epub'));
                $obfuscator = new Obfuscator();
                $styles = array_keys(Zord::getInstance('Book', null)->styles(['screen','epub']));
                foreach ($styles as $css) {
                    $file = Zord::getComponentPath('web'.DS.'css'.DS.'book'.DS.$css.'.css');
                    if ($file) {
                        if (OBFUSCATE_BOOK) {
                            $file = BUILD_FOLDER.$obfuscator->getCSS($css);
                        }
                        $epub->addFromString('OPF/css/'.$css.'.css', file_get_contents($file));
                    }
                }
                $fonts = Zord::addRecursive($epub, Zord::getComponentPath('web'.DS.'css'.DS.'fonts'), 'OPF/fonts');
                $id = 1;
                $items = [];
                $navbar = [];
                $epub->addFromString('OPF/cover.xhtml', (new View('/epub/OPF/cover.xhtml', ['lang' => $metadata['language'] ?? 'fr']))->render());
                foreach ($parts as &$part) {
                    if ($part['epub']) {
                        $partFile = $part['name'].'.xhtml';
                        $part['styles'] = $styles;
                        $part['text'] = Library::data($ean, $part['name'].'.xhtml', 'content');
                        $part['lang'] = $metadata['language'] ?? 'fr';
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
                            $img->setAttribute('src', $this->url($ean, $graphic, 'data-url'));
                            $caption = $this->caption($graphic);
                            if (isset($caption)) {
                                $img->setAttribute('alt', $this->caption($graphic));
                            } else {
                                $img->setAttribute('role', 'presentation');
                            }
                        }
                        $links = $partXPath->query('//a[@href]');
                        foreach ($links as $anchor) {
                            $tokens = explode('#', $anchor->getAttribute('href'));
                            $_part = $tokens[0];
                            $_anchor = $tokens[1];
                            if (substr($_anchor, 0, 8) == 'footref_') {
                                $_anchor = substr($_anchor, 8);
                            }
                            if (count($tokens) == 2 && isset($anchors['#'.$_anchor]) && $anchors['#'.$_anchor] == $_part) {
                                $anchor->setAttribute('href', $_part.'.xhtml#'.$tokens[1]);
                            }
                        }
                        $notes = $partXPath->query('//div[@class="note"][@id]');
                        foreach ($notes as $note) {
                            $anchor = $partDoc->createElement('a');
                            $anchor->setAttribute('href', '#footref_'.$note->getAttribute('id'));
                            $clone = $note->cloneNode(true);
                            $clone->appendChild($partDoc->createTextNode($note->getAttribute('data-n')));
                            $anchor->appendChild($clone);
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
                        $items[$partFile]['title'] = $part['flat'];
                    }
                }
                foreach ($parts as &$part) {
                    if ($part['level'] <= ((!empty($metadata['tocDepth']) && is_int($metadata['tocDepth'])) ? $metadata['tocDepth'] : MAX_TOC_DEPTH)) {
                        $navbar[] = [
                            'id'    => $part['id'],
                            'part'  => $part['ref'],
                            'level' => $part['level'],
                            'text'  => $part['flat']
                        ];
                    }
                }
                $epub->addEmptyDir('OPF/medias/'.$ean);
                $width = Zord::parameter($this->parameters, 'EPUB_IMAGE_MAX_WIDTH', '');
                $height = Zord::parameter($this->parameters, 'EPUB_IMAGE_MAX_HEIGHT', '');
                foreach ($medias as $file) {
                    if (file_exists($file)) {
                        $path = substr($file, strlen(STORE_FOLDER));
                        if (defined("RESIZE_COMMAND")) {
                            $resized = STORE_FOLDER.'epub'.DS.$path;
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
                foreach (['navigation.xhtml','navigation.ncx','package.opf'] as $metaFile) {
                    $epub->addFromString('OPF/'.$metaFile, (new View('/epub/OPF/'.$metaFile, [
                        'metadata' => $metadata,
                        'navbar'   => $navbar,
                        'items'    => $items,
                        'styles'   => $styles,
                        'fonts'    => $fonts
                    ]))->render());
                }
                if ($epub->close()) {
                    $rename = !$this->check;
                    if ($this->check) {
                        $command = Zord::substitute(EPUBCHECK_COMMAND, [
                            'EXEC' => defined('EPUBCHECK_PATH') ? 'java -jar '.EPUBCHECK_PATH.DS.'epubcheck.jar' : 'epubcheck',
                            'LANG' => $this->lang,
                            'EPUB' => $tmpFile
                        ]);
                        $this->info(2, $command);
                        $errors = shell_exec($command);
                        if (substr($errors, 0, strlen($this->locale->messages->epub->info->ok)) == $this->locale->messages->epub->info->ok) {
                            $rename = true;
                        } else {
                            foreach (explode("\n", $errors) as $error) {
                                $this->logError('epub', str_replace($tmpFile.DS, '', $error));
                            }
                            $result = false;
                        }
                    }
                    if ($rename) {
                        $epubFile = STORE_FOLDER.'epub'.DS.$eanEPUB.'.epub';
                        rename($tmpFile, $epubFile);
                    }
                } else {
                    $this->logError('epub', Zord::substitute($this->locale->messages->epub->error->close, [
                        'file' => $tmpFile
                    ]));
                    $result = false;
                }
                Zord::resetFolder(STORE_FOLDER.'epub'.DS.'medias', false);
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
    
    protected function header($ean) {
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
    
    protected function handlePart(&$part, $ean, $folder) {
        $result = true;
        if (empty($part['title'])) {
            $this->xmlError('slice', $part['line'], $this->locale->messages->slice->error->notitle);
            $result = false;
        } else {
            if ($part['name'] == 'home') {
                $part['link'] = $ean.'/home';
                $this->ariadne[] = [
                    'id'    => $part['node']->getAttribute('id'),
                    'link'  => $ean,
                    'title' => $part['title'],
                    'flat'  => $part['flat']
                ];
            } else {
                $element = null;
                if ($part['level'] == 1) {
                    $element = $this->tocXPath->query('//ul[@id="tocTEI_' . $part['part'] . '"]')[0];
                } else if ($part['level'] <= ((!empty($this->metadata['tocDepth']) && is_int($this->metadata['tocDepth'])) ? $this->metadata['tocDepth'] : MAX_TOC_DEPTH)) {
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
                    $partFlats = [$part['flat']];
                    if ($this->is_fragment($part)) {
                        $part['link'] = $ean.'/'.$part['name'];
                    } else {
                        $part['link'] = $element->parentNode->getAttribute('data-part');
                    }
                    $toDo = !in_array($part['name'], $this->dones) && substr($part['title'], 0, 1) !== '#';
                    if ($toDo) {
                        if (isset($this->visavis)) {
                            foreach ($this->visavis as $group) {
                                if (in_array($part['name'], $group)) {
                                    $toDo = ($part['name'] == $group[0]);
                                    $this->dones = array_merge($this->dones, $group);
                                    $partTitles = [];
                                    $partFlats = [];
                                    foreach ($this->parts as $_part) {
                                        if (in_array($_part['name'], $group)) {
                                            $partTitles[] = $_part['title'];
                                            $partFlats[] = $_part['flat'];
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
                        $title = implode(' | ', $partTitles);
                        $flat = implode(' | ', $partFlats);
                        $span = $this->toc->createElement('span');
                        $fragment = $this->toc->createDocumentFragment();
                        $fragment->appendXML($title);
                        $span->appendChild($fragment);
                        $li->appendChild($span);
                        $pageInAriadne = false;
                        foreach ($this->ariadne as $entry) {
                            if ($entry['link'] == $part['link']) {
                                $pageInAriadne = true;
                                break;
                            }
                        }
                        if (!$pageInAriadne) {
                            $this->ariadne[] = [
                                'id'    => $part['id'],
                                'link'  => $part['link'],
                                'title' => $title,
                                'flat'  => $flat
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
                    foreach ($this->parts as $entry) {
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
                    if (in_array($url, $this->zoom)) {
                        $graphic->setAttribute('data-zoom', '/zoom/'.$ean.'/'.str_replace('jpg', 'dzi', $url));
                    } else {
                        $imgFile = $this->medias[$url];
                        $loading = $partText->createElement($this->prefix.':loading');
                        $loading->setAttribute('data-load', 'replaceGraphic');
                        $height = min([file_exists($imgFile) ? getimagesize($imgFile)[1] : GRAPHIC_LOADING_MAX_HEIGHT, GRAPHIC_LOADING_MAX_HEIGHT]);
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
                        $anchors = Library::data($matches[1], 'anchors.json', 'array');
                        if (isset($anchors)) {
                            if (isset($anchors['#'.$matches[2]])) {
                                $base = $this->base($matches[1]);
                                $anchor->setAttribute('href', ($base !== false ? $base : '').'/book/'.$matches[1].'/'.$anchors['#'.$matches[2]].'#'.$matches[2]);
                            }
                        }
                    } else if (substr($target, 0, 1) == '#' && isset($this->anchors[$target])) {
                        $_target = $target;
                        if (substr($target, 1, 1) == 'N') {
                            $_target = '#footref_'.substr($target, 1);
                        }
                        $anchor->setAttribute('href', $this->anchors[$target].$_target);
                    } else if (substr($target, 0, 4) == 'http') {
                        $anchor->setAttribute('href', $target);
                    }
                    if ($anchor->hasAttribute('href')) {
                        $length = $ref->childNodes->length;
                        for ($i = 0; $i < $length; $i++) {
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
            in_array($part['type'], Zord::value('import', ['types','root'])) || 
            in_array($part['type'], Zord::value('import', ['types','fragment'])) ||
            (isset($part['synch']) && substr($part['synch'], 0, 1) !== '#' && ($allvisavis || !isset($part['corresp'])));
    }
    
    protected function xmlError($step, $element, $message, $parameters = null) {
        if (isset($parameters) && is_array($parameters)) {
            $message = Zord::substitute($message, $parameters);
        }
        $this->logError($step, $this->locale->line.' '.(is_int($element) ? $element : $element->getLineNo()).' : '.$message);
    }
    
    private function createSearchFacets($ean, $source) {
        foreach (Zord::value('search', 'facets') as $facet) {
            $values = isset($source[$facet]) ? $source[$facet] : [];
            if (!is_array($values)) {
                $values = [$values];
            }
            foreach ($values as $value) {
                if (!isset($this->facets[$ean][$facet][$value])) {
                    (new BookHasFacetEntity())->create([
                        'book'  => $ean,
                        'facet' => $facet,
                        'value' => $value
                    ]);
                    $this->facets[$ean][$facet][$value] = 'done';
                }
            }
        }
    }
    
    private function loadXML(&$document, $content) {
        libxml_clear_errors();
        $previous = libxml_use_internal_errors(true);
        if (file_exists($content)) {
            $document->load($content, self::$XML_PARSE_BIG_LINES);
        } else {
            $document->loadXML($content, self::$XML_PARSE_BIG_LINES);
        }
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
    }
    
    private function status($context, $ean) {
        if (isset($this->publish) && isset($this->publish[$context])) {
            if (is_array($this->publish[$context])) {
                if (isset($this->publish[$context][$ean])) {
                    return $this->publish[$context][$ean];
                }
            } else {
                return $this->publish[$context];
            }
        }
        return 'no';
    }
    
    private function addPart($name, $type, $title, $flat, $base, $part, $level, $node) {
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
            'flat'        => $flat,
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
        $this->parts[] = $newPart;
        return $newPart;
    }
    
    private function getRefName($part) {
        if ($this->is_fragment($part, false)) {
            return $part['name'];
        } else {
            if (isset($this->parts)) {
                foreach ($this->parts as $base) {
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
            if ($div->hasAttribute('synch') && substr($div->getAttribute('synch'), 0, 1) !== '#') {
                $group = null;
                if ($div->hasAttribute('corresp')) {
                    $group = $div->getAttribute('corresp');
                } else if ($div->parentNode->hasAttribute('id')) {
                    $group = $div->parentNode->getAttribute('id');
                }
                if (!empty($group)) {
                    $visavis[$group][] = $name;
                }
            }
            $title = '';
            $flat = '';
            foreach ($div->childNodes as $child) {
                if ($child->localName == 'head') {
                    $title .= empty($title) ? '' : ' ';
                    $flat .= empty($flat) ? '' : ' ';
                    foreach ($child->childNodes as $grandChild) {
                        $isTextNode  = $grandChild->nodeType == XML_TEXT_NODE;
                        $isValidTag  = in_array($grandChild->localName, ['hi','emph']);
                        $isSeparator = in_array($grandChild->localName, ['lb']);
                        if ($isSeparator) {
                            $title .= empty($title) ? '' : ' ';
                            $flat .= empty($flat) ? '' : ' ';
                        }
                        if ($isTextNode || $isValidTag) {
                            $title .= $this->format($grandChild);
                            $flat .= $grandChild->textContent;
                        }
                    }
                }
            }
            $title = Library::compact($title);
            $flat = !empty($flat) ? Library::compact($flat) : strip_tags($title);
            if ($title == '' && $div->hasAttribute('synch')) {
                $title = $div->getAttribute('synch');
            }
            if ($flat == '' && $div->hasAttribute('synch')) {
                $flat = $div->getAttribute('synch');
            }
            $part = $this->addPart($name, $type, $title, $flat, $base, $node, $level, $div);
            foreach ($this->adjXPath->query('//'.$this->prefix.':div[@id="'.$id.'"]//*[@xml:id]') as $element) {
                $this->anchors['#'.$element->getAttribute('xml:id')] = $part['ref'];
            }
            if (in_array($type, Zord::value('import', ['types','root'])) || in_array($type, Zord::value('import', ['types','fragment'])) || in_array($type, Zord::value('import', ['types','toc']))) {
                $this->scan('div[@id="'.$id.'"]', $name, $level + 1);
            }
        }
        if (count($visavis) > 0) {
            foreach ($visavis as $name => $group) {
                foreach($group as $entry) {
                    $this->visavis[$name][] = $entry;
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
                    if ($name !== 'class') {
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
    
    private function url($ean, $graphic, $name) {
        if ($graphic->hasAttribute($name)) {
            $url = $graphic->getAttribute($name);
            if (substr($url, 0, 1) === '/') {
                $url = 'public'.$url;
            } else {
                $url = 'medias/'.$ean.'/'.$url;
            }
            return $url;
        }
        return null;
    }
    
    private function base($ean) {
        if (!isset($this->base[$ean])) {
            $book = (new BookHasContextEntity())->retrieve([
                'where' => ['book' => $ean]
            ]);
            if ($book) {
                $context = $book->context;
                $url = Zord::value('context', [$context,'url'])[0];
                $host = $url['host'];
                $path = $url['path'];
                $secure = isset($url['secure']) ? $url['secure'] : false;
                $this->base[$ean] = ($secure ? 'https' : 'http').'://'.$host.($path !== '/' ? $path : '');
            } else {
                $this->base[$ean] = false;
            }
        }
        return $this->base[$ean];
    }
    
    private function caption($graphic) {
        $caption = null;
        foreach ([Zord::firstElementChild($graphic),Zord::nextElementSibling($graphic),Zord::previousElementSibling($graphic)] as $candidate) {
            if (isset($candidate) && $candidate->hasAttribute('class') &&  $candidate->getAttribute('class') == 'caption') {
                $caption = $candidate->nodeValue;
                break;
            }
        }
        return $caption;
    }
    
    private function format($node) {
        $begin = '';
        $end = '';
        switch ($node->localName) {
            case 'emph':
            case 'hi': {
                $rend = $node->getAttribute('rend');
                if (in_array($rend, ['i','strong','sup','sub'])) {
                    $begin = '<'.$rend.'>';
                    $end   = '</'.$rend.'>';
                }
                if ($rend == 'sc') {
                    $begin = '<span style="font-variant:small-caps;">';
                    $end   = '</span>';
                }
                break;
            }
        }
        return $begin.htmlspecialchars($node->textContent).$end;
    }
}

?>
