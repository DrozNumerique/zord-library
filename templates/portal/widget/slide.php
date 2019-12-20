		<div id="<?php echo $models['id']; ?>" class="slide fixed">
			<div class="top">
<?php $this->render($models['top']); ?>
			</div>
			<div class="content" align="<?php echo $models['align']; ?>">
<?php $this->render($models['content']); ?>
			</div>
			<div class="handle">
<?php $this->render('handle', ['handle' => $models['handle']]); ?>
			</div>
		</div>
