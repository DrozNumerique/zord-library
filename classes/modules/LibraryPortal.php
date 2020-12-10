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
}

?>