<?php
if (isset($models['user'])) {
    $this->render('profile');
} else {
    $this->render('setup');
}
?>
       			