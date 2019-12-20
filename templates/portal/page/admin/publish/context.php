   				<input type="hidden" id="context" value="<?php echo $models['context']; ?>"/>
				<div align="center">
           			<div class="admin-panel-title"><?php echo $models['title']; ?></div>
<?php 
if ($user->isManager()) {
    $this->render('#urls');
}
$this->render('#books');
$this->render('#submit');
?>
				</div>
