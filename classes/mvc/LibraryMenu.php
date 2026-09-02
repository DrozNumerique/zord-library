<?php

class LibraryMenu extends Menu {
    
    protected function entry($name) {
        if ($this->context === 'root' && !in_array($name, Zord::value('menu', 'root') ?? [])) {
            return null;
        }
        $entry = parent::entry($name);
        switch ($name) {
            case 'context': {
                foreach (Zord::contextList($this->lang) as $context => $title) {
                    if ($context !== 'root') {
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
                    $entry['params'] = ['success' => $_SERVER['REQUEST_URI']];
                }
                break;
            }
            case 'quick': {
                $entry['render'] = 'quick';
                break;
            }
            case 'counter': {
                $entry['active'] = Library::isCounter($this->user, $this->context) || $this->user->hasRole('admin', $this->context);
                break;
            }
        }
        return $entry;
    }
    
}

?>