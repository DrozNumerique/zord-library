<?php

class LibraryControler extends Controler {
        
    public function findTarget($scheme, $host, $path, $query, $fragment) {
        if (parse_url(OPENURL, PHP_URL_HOST) == $host) {
            return [
                'host'    => $host,
                'scheme'  => parse_url(OPENURL, PHP_URL_SCHEME),
                'method'  => 'GET',
                'config'  => null,
                'skin'    => null,
                'context' => 'unknown',
                'indexURL'=> 0,
                'baseURL' => OPENURL,
                'prefix'  => '/',
                'module'  => 'Book',
                'action'  => 'openurl'
            ];
        } else {
            return parent::findTarget($scheme, $host, $path, $query, $fragment);
        }
    }
    /*
    public function getTarget($url, $redirect = false) {
        $target = parent::getTarget($url, $redirect);
        if ($target['module'] === 'Book' && $target['action'] === 'show') {
            $target['base'] = $target['basePath'].'/book/'.($target['params']['isbn'] ?? null).'/'.($target['params']['part'] ?? null);
        }
        return $target;
    }
    */
    public function models() {
	    $models = parent::models();
	    $models['portal']['header']['right']['text'] = $this->skin->header->right->text ?? explode(' ', Zord::getLocaleValue('title', $this->config, $this->lang));
        return $models;
    }
}

?>
