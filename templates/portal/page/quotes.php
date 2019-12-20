<div id="marker_styles">
	<div id="markers_buttons">
		<div class="markers_buttonswarp">
			<span id="marker_title"><?php echo $locale->title; ?></span>
		</div>
		<div style="width:290px;float:left">
			<button id="markers_clear" data-tooltip="<?php echo $locale->clear_tooltip; ?>"><?php echo $locale->clear; ?></button>
			<button id="markers_export" data-tooltip="<?php echo $locale->export_tooltip; ?>"><?php echo $locale->export; ?></button>
			<br><br>
			<span data-tooltip="<?php echo $locale->styles_tooltip; ?>">
				<select id="marker_styles_select">
					<?php foreach (Zord::getConfig('csl') as $key => $name) { ?>
					<option value="<?php echo $key; ?>"><?php echo $name; ?></option>
					<?php } ?>
				</select>
			</span>
		</div>
	</div>
	<br>
	<div>
		<span class="help_bubble_red" style="margin-top:8px;">
			<div>
				<?php foreach ($locale->citation_help as $line) { ?>
				<p style="text-align:justify"><?php echo $line; ?></p>
				<?php } ?>
			</div>
		</span>
	</div>
</div>
<div id="markers"></div>
<div id="dialogs">
<?php $this->render('/portal/widget/help'); ?>
</div>
