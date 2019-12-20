<?php
    
class SourceDateFilter extends Filter {

    public function add(&$query, $key, $value) {
        if (isset($value['to']) && !empty($value['to'])) {
            $query->addFilterQuery('creation_date_from_s:[* TO '.$value['to'].']');
        }
        if (isset($value['from']) && !empty($value['from'])) {
            $query->addFilterQuery('creation_date_to_s:['.$value['from'].' TO *]');
        }
    }
}

?>