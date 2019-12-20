<?php

class MinMaxFilter extends Filter {
    
    public function add(&$query, $key, $value) {
        if (isset($value['min']) && !empty($value['min']) && isset($value['max']) && !empty($value['max'])) {
            $query->addFilterQuery($key.':['.$value['min'].' TO '.$value['max'].']');
        }
    }
    
}

?>