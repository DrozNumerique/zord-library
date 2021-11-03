        <div id="ariadne">
<?php if (isset($models['portal']['ariadne']['previous'])) { ?>
        	<span class="ariadne-previous" data-part="<?php echo $models['portal']['ariadne']['previous']['link'] ?>" data-id="<?php echo $models['portal']['ariadne']['previous']['id'] ?>" title="<?php echo $portal->locale->ariadne->chapter->previous.' : '.($models['portal']['ariadne']['previous']['flat'] ?? $models['portal']['ariadne']['previous']['title']); ?>"></span>
<?php } ?>
        	<div class="ariadne-content">
<?php if (isset($models['portal']['visavis']) && count($models['portal']['visavis']) > 1) { ?>
<?php   $this->render('visavis'); ?>
<?php } ?>
        		<span data-part="<?php echo $models['book']['ISBN'] ?>"><?php echo $models['book']['TITLE'] ?></span>
<?php if (isset($models['portal']['ariadne']['current']) && $models['book']['PART'] !== 'home') { ?>
        		<span class="ariadne-current" data-part="<?php echo $models['portal']['ariadne']['current']['link'] ?>" data-id="<?php echo $models['portal']['ariadne']['current']['id']; ?>"><?php echo $models['portal']['ariadne']['current']['title']; ?></span>
<?php } ?>
        	</div>
<?php if (isset($models['portal']['ariadne']['next'])) { ?>
        	<span class="ariadne-next" data-part="<?php echo $models['portal']['ariadne']['next']['link']; ?>" data-id="<?php echo $models['portal']['ariadne']['next']['id']; ?>" title="<?php echo $portal->locale->ariadne->chapter->next.' : '.($models['portal']['ariadne']['next']['flat'] ?? $models['portal']['ariadne']['next']['title']); ?>"></span>
<?php } ?>
        </div>
        