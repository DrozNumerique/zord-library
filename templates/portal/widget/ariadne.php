        <div id="ariadne">
        	<span class="ariadne-previous" data-part="<?php echo $models['portal']['ariadne']['previous']['link'] ?? ''; ?>" data-id="<?php echo $models['portal']['ariadne']['previous']['id'] ?? ''; ?>" title="<?php echo $this->locale('portal')->ariadne->chapter->previous.' : '.(!empty($models['portal']['ariadne']['previous']['flat']) ? $models['portal']['ariadne']['previous']['flat'] : ($models['portal']['ariadne']['previous']['title'] ?? '')); ?>">
        		<i class="fa fa-fw fa-chevron-left"></i>
        	</span>
        	<div class="ariadne-content">
        		<span class="ariadne-book" data-part="<?php echo $models['book']['ISBN'] ?>"><?php echo $models['book']['TITLE'] ?></span>
<?php if (isset($models['portal']['ariadne']['current']) && $models['book']['PART'] !== 'home') { ?>
        		<span class="ariadne-current" data-part="<?php echo $models['portal']['ariadne']['current']['link'] ?>" data-id="<?php echo $models['portal']['ariadne']['current']['id']; ?>">
        		<?php echo $models['portal']['ariadne']['current']['title']; ?>
<?php if (isset($models['portal']['visavis']) && count($models['portal']['visavis']) > 1) { ?>
<?php   $this->render('visavis'); ?>
<?php } ?>
        		</span>
<?php } ?>
        	</div>
        	<span class="ariadne-next" data-part="<?php echo $models['portal']['ariadne']['next']['link'] ?? ''; ?>" data-id="<?php echo $models['portal']['ariadne']['next']['id'] ?? ''; ?>" title="<?php echo $this->locale('portal')->ariadne->chapter->next.' : '.(!empty($models['portal']['ariadne']['next']['flat']) ? $models['portal']['ariadne']['next']['flat'] : ($models['portal']['ariadne']['next']['title'] ?? '')); ?>">
        		<i class="fa fa-fw fa-chevron-right"></i>
        	</span>
        </div>
        