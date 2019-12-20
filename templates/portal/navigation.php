	<nav id="navbar">
<?php 
if (isset($models['portal']['menu'])) {
        $this->render('menu');
}
if (isset($models['portal']['ariadne'])) {
        $this->render('ariadne');
}
if (isset($models['portal']['message'])) {
        $this->render('message');
} 
?>
	</nav>
