		 		<span class="visavis-variants">
<?php foreach ($models['portal']['visavis'] as $variant) { ?>
					<button type="button" name="<?php echo $variant['id']; ?>" class="<?php echo $variant['class']; ?>"><?php echo $variant['label']; ?></button>
<?php } ?>
				</span>
