<?php

abstract class Liner {
    
    protected $shelves  = [];
    protected $locale   = [];
    protected $context  = null;
    protected $lang     = DEFAULT_LANG;
    
    public abstract function getLocale();
    public abstract function sortValue($shelf, $book);
    public abstract function store($book);
    
    public function __construct($context, $lang) {
        $this->context  = $context;
        $this->lang     = $lang;
        $this->locale   = $this->getLocale();
    }
    
    public function apart($book, $apart) {
        return (in_array($book['status'], Zord::value('portal', 'apart') ?? []) && $apart) ? $book['status'] : false;
    }
    
    public function getClass($name) {
        return [];
    }
    
    public function other($book) {
        $this->shelves['other']['books'][] = $book;
    }
    
    public function line($books, $apart = true, $classes = null) {
        foreach ($books as $book) {
            $shelf = $this->apart($book, $apart);
            if ($shelf) {
                $this->shelves[$shelf]['apart'] = true;
                $this->shelves[$shelf]['books'][] = $book;
            } else {
                if ($this->store($book) === false) {
                    $this->other($book);
                }
            }
        }
        foreach($this->shelves as $name => $shelf) {
            $this->shelves[$name]['name'] = $name;
            $this->shelves[$name]['apart'] = isset($shelf['apart']) ? $shelf['apart'] : false;
            $this->addClass($name, $this->shelves[$name]['apart'] ? 'apart' : 'panel');
            $this->addClass($name, $name);
            $this->addClass($name, $classes);
            $this->addClass($name, $this->getClass($name));
            usort($this->shelves[$name]['books'], function($first, $second) use ($name) {
                $x = $this->sortValue($name, $first);
                $y = $this->sortValue($name, $second);
                if (is_int($x) && is_int($y)) {
                    if ($x == $y) {
                        return 0;
                    } else {
                        return $x < $y ? -1 : 1;
                    }
                } else {
                    return strcasecmp($x, $y);
                }
            });
        }
        return [
            'shelves' => $this->shelves,
            'labels'  => $this->locale
        ];
    }
    
    private function addClass($name, $classes) {
        if (isset($classes)) {
            if (!is_array($classes)) {
                $classes = [$classes];
            }
            foreach($classes as $class) {
                $this->shelves[$name]['class'][] = $class;
            }
        }
    }
}

?>