<?php

class LibraryControler extends Controler {
        
    public function findTarget($host, $path) {
        if (parse_url(OPENURL, PHP_URL_HOST) == $host) {
            return [
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

    public function models() {
	    $models = parent::models();
        $skin = Zord::getSkin($this->context);
        if (isset($skin->header->right->text)) {
            $models['portal']['header']['right']['text'] = $skin->header->right->text;
        } else {
            $models['portal']['header']['right']['text'] = explode(' ', Zord::getLocaleValue('title', Zord::value('context', $this->context), $this->lang));
        }
        $layout = Zord::value('menu', 'layout');
        if (!isset($layout)) {
            $layout = array_keys(Zord::getConfig('menu'));
        }
        $locale = $models['portal']['locale']['menu'] ?? [];
        foreach ($layout as $name) {
            $entry = Zord::value('menu', $name);
            if ((!isset($entry['role']) || $this->user->hasRole($entry['role'], $this->context)) && (!isset($entry['connected']) || ($this->user->isConnected() && $entry['connected']) || (!$this->user->isConnected() && !$entry['connected']) || $this->user->isManager())) {
                list($type, $url, $class, $label) = $this->menu($entry, $name, $locale);
                $subMenu  = [];
                if ($type == 'menu' && isset($entry['menu']) && is_array($entry['menu']) && Zord::is_associative($entry['menu'])) {
                    foreach ($entry['menu'] as $subName => $subEntry) {
                        list(, $subURL, $subClass, $subLabel) = $this->menu($subEntry, $subName, $locale);
                        $subMenu[] = [
                            'name'  => $subName,
                            'url'   => $subURL,
                            'class' => $subClass,
                            'label' => $subLabel
                        ];
                    }
                }
                $models['portal']['menu']['link'][] = [
                    'type'  => $type,
                    'name'  => $name,
                    'url'   => $url,
                    'class' => $class,
                    'label' => $label,
                    'menu'  => $subMenu
                ];
            }
        }
        foreach (Zord::getConfig('context') as $name => $config) {
            if (isset($config['url']) && !empty($config['url'])) {
                $title = $name;
                if (isset($config['title'][$this->lang])) {
                    $title = $config['title'][$this->lang];
                } else if (isset($config['title'][DEFAULT_LANG])) {
                    $title = $config['title'][DEFAULT_LANG];
                } else if (isset($config['title']) && is_string($config['title'])) {
                    $title = $config['title'];
                }
                $models['portal']['menu']['context'][$name] = $title;
            }
        }
        foreach (Zord::getConfig('lang') as $name => $value) {
            $models['portal']['menu']['lang'][$name] = $value;
        }
        $connected = $this->user->isConnected();
        $models['portal']['account'] = [
            'action' => $connected ? 'disconnect' : 'connect',
            'label'  => $connected ? $models['portal']['locale']['menu']['logout'] : $models['portal']['locale']['menu']['login']
        ];
        return $models;
    }
    
    private function menu($entry, $name, $locale) {
        $type  = isset($entry['type'])  ? $entry['type']  : 'default';
        $path  = isset($entry['path'])  ? $entry['path']  : ($type == 'shortcut' ? (isset($entry['module']) && isset($entry['action']) ? '/'.$entry['module'].'/'.$entry['action'] : '/'.$name) : ($type == 'page' ? '/page/'.$name : ''));
        $url   = isset($entry['url'])   ? $entry['url']   : ($type == 'menu' ? null : $this->baseURL.$path);
        $class = isset($entry['class']) ? (is_array($entry['class']) ? $entry['class'] : [$entry['class']]) : [];
        $label = isset($entry['label'][$this->lang]) ? $entry['label'][$this->lang] : (isset($locale[$name]) ? $locale[$name] : $name);
        return [$type, $url, $class, $label];
    }
}

?>
