<?php

class LibraryControler extends Controler {
        
    public function findTarget($host, $path) {
        if (parse_url(OPENURL, PHP_URL_HOST) == $host) {
            return [
                'host'    => $host,
                'scheme'  => parse_url(OPENURL, PHP_URL_SCHEME),
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
            return parent::findTarget($host, $path);
        }
    }
    
    public function getTarget($url, $redirect = false) {
        $target = parent::getTarget($url, $redirect);
        if ($target['module'] === 'Book' && $target['action'] === 'show') {
            $target['base'] = $target['baseURL'].'/book/'.($target['params']['isbn'] ?? null).'/'.($target['params']['part'] ?? null);
        }
        return $target;
    }

    public function models() {
	    $models = parent::models();
	    $models['portal']['header']['right']['text'] = $this->skin->header->right->text ?? explode(' ', Zord::getLocaleValue('title', $this->config, $this->lang));
        $connected = $this->user->isConnected();
        $models['portal']['account'] = [
            'action' => $connected ? 'disconnect' : 'connect',
            'label'  => $connected ? $models['portal']['locale']['menu']['logout'] : $models['portal']['locale']['menu']['login']
        ];
        return $models;
    }
}

?>
