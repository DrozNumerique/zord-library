<?php

abstract class Filter {
    public abstract function add(&$query, $key, $value);    
}

?>