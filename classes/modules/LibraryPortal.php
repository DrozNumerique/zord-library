<?php

class LibraryPortal extends StorePortal {
    
    public function home() {
        return $this->page('home', (new Book($this->controler))->classify($this->params['year'] ?? false));
    }
    
    protected function metadata($ean) {
        $metadata = Library::data($ean, 'metadata.json', 'array');
        return [
            'about'     => $this->baseURL.'/'.$ean,
            'title'     => Library::title($metadata),
            'abstract'  => $metadata['abstract'] ?? null,
            'publisher' => $metadata['publisher'] ?? null,
            'locality'  => $metadata['locality'] ?? null,
            'date'      => $metadata['date'] ?? null,
            'language'  => $metadata['language'] ?? null,
            'isbn13'    => $metadata['isbn'] ?? null,
            'uri'       => $metadata['uri'] ?? null,
            'type'      => $metadata['type'] ?? null,
            'rights'    => $metadata['rights'] ?? null,
            'serie'     => $metadata['relation'] ?? null,
            'number'    => $metadata['collection_number'] ?? null,
            'pages'     => $metadata['page'] ?? null,
            'creator'   => $metadata['creator'] ?? null,
            'editor'    => $metadata['editor'] ?? null
        ];
    }
    
    protected function _options($type) {
        $options = parent::_options($type);
        $facets = Zord::value('search', 'facets') ?? [];
        if (!isset($type)) {
            foreach ($facets as $facet) {
                $options[] = $facet;
            }
        } else {
            $options = [];
            if ($type == 'titles') {
                foreach (Library::inContext($this->context, 'BookEntity') as $book) {
                    $options[$book->ean] = Library::title($book->title, $book->subtitle);
                }
            } else if (in_array($type, $facets)) {
                $keys   = [];
                $values = [];
                $locale = Zord::value($type, $this->context);
                foreach (Library::facets($this->context, $type) as $key) {
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
                $options = array_combine($keys, $values);
            }
        }
        return $options;
    }
}

?>