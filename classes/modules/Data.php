<?php

class Data extends Module {
    
    public function chosen() {
        return Zord::getConfig('chosen');
    }
}

?>
