<?php

class LibraryPortal extends StorePortal {
    
    public function home() {
        if ($this->context === 'root') {
            $corpus = [];
            foreach (Zord::getConfig('context') as $context => $config) {
                if ($context !== 'root' && !empty($config['urls']) && isset($config['corpus']) && in_array($config['corpus'], Zord::value('portal', 'corpus'))) {
                    $corpus[$config['corpus']][$context] = $config['position'] ?? 0;
                }
            }
            uksort($corpus, function($first, $second) {
                return $this->corpus($first) <=> $this->corpus($second);
            });
            $models = ['corpus' => $corpus];
        } else {
            $models = (new Book($this->controler))->classify($this->params['year'] ?? false);
        }
        return $this->page('home', $models);
    }
    
    private function corpus($corpus) {
        return array_search($corpus, Zord::value('portal', 'corpus'));
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
    
    protected function _options($scope, $key) {
        $options = parent::_options($scope, $key);
        if ($scope == 'context') {
            $facets = Zord::value('search', 'facets') ?? [];
            if (!isset($key)) {
                foreach ($facets as $facet) {
                    $options[] = $facet;
                }
            } else {
                $options = [];
                if ($key == 'titles') {
                    foreach (Library::inContext($this->context, 'BookEntity') as $book) {
                        $options[$book->ean] = Library::title($book->title, $book->subtitle);
                    }
                } else if (in_array($key, $facets)) {
                    $keys   = [];
                    $values = [];
                    $locale = Zord::value($key, $this->context);
                    foreach (Library::facets($this->context, $key) as $name) {
                        if (isset($locale)) {
                            if (isset($locale[$name])) {
                                $keys[$name]   = $name;
                                $values[$name] = $locale[$name];
                            }
                        } else {
                            $keys[$name]   = $name;
                            $values[$name] = $name;
                        }
                    }
                    $options = array_combine($keys, $values);
                }
            }
        }
        return $options;
    }
}

?>