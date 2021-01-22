<?php

class LibraryMenu extends Menu {
    
    protected function entry($name) {
        $entry = parent::entry($name);
        switch ($name) {
            case 'context': {
                foreach (Zord::getConfig('context') as $context => $config) {
                    if (isset($config['url']) && !empty($config['url'])) {
                        $title = $context;
                        if (isset($config['title'][$this->lang])) {
                            $title = $config['title'][$this->lang];
                        } else if (isset($config['title'][DEFAULT_LANG])) {
                            $title = $config['title'][DEFAULT_LANG];
                        } else if (isset($config['title']) && is_string($config['title'])) {
                            $title = $config['title'];
                        }
                        $entry['menu'][$context] = [
                            'type'  => 'nolink',
                            'label' => $title
                        ];
                    }
                }
                break;
            }
            case 'lang': {
                foreach (Zord::value('portal', 'lang') as $lang) {
                    $entry['menu'][$lang] = [
                        'type'  => 'nolink',
                        'label' => Zord::getLocale('portal', $this->lang)->lang->$lang
                    ];
                }
                break;
            }
            case 'connect': {
                if ($this->user->hasRole('admin', $this->context) && !$this->user->isConnected()) {
                    $entry['active'] = false;
                } else {
                    $connected = $this->user->isConnected();
                    $entry['action'] = $connected ? 'disconnect' : 'connect';
                    $label = $connected ? 'logout' : 'login';
                    $entry['label'] = Zord::getLocale('portal', $this->lang)->menu->$label;
                }
                break;
            }
        }
        return $entry;
    }
    
}

?>