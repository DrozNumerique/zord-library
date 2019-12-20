<?php if (isset($models['slide']) && $models['slide'] == true) { ?>
<?php   
      $this->render('/portal/widget/slide', [
          'id'      => 'search', 
          'top'     => '/portal/widget/query', 
          'content' => '/portal/page/search/refine', 
          'align'   => 'left', 
          'handle'  => '&#x1f50d;'
      ]); 
?>
<?php } else { ?>
<div id="searchCanvas" align="center">
  <div id="searchPanel" align="left">
<?php   $this->render('/portal/widget/query'); ?>
<?php   $this->render('refine'); ?>
  </div>
</div>
<?php } ?>
<?php $this->render('/portal/widget/shelves'); ?>
