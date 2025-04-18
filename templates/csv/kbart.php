<?php echo implode(',', RECORDS_FIELDS['KBART'] ?? [])."\n"; ?>
<?php foreach ($models['books'] as $metadata) { ?>
<?php   $this->render('line', ['metadata' => $metadata]); ?>
<?php } ?>
