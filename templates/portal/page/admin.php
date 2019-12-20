<?php $current = $models['current']; ?>
<div class="admin-page">
    <div id="menu" class="admin-menu">
    	<?php foreach($models['tabs'] as $name) { ?>
    	<span data-tab="<?php echo $name; ?>" class="admin-menu-entry<?php if ($current == $name) echo ' admin-menu-entry-selected'; ?>"><?php echo $locale->menu->$name; ?></span>
		<?php } ?>
    </div>
    <div id="panel" class="admin-panel">
    	<div class="admin-tab">
            <div class="admin-panel-title"><?php echo $locale->tab->$current->title; ?></div>
            <div class="admin-panel-content">
            <?php $this->render($current); ?>
            </div>
        </div>
    </div>
</div>       



