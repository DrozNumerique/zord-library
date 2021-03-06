<form id="counter" class="counter" action="<?php echo $baseURL ?>" method="post">
    <input type="hidden" name="module"  value="Book"/>
    <input type="hidden" name="action"  value="counter"/>
<?php if (isset($models['counter']['context'])) { ?>
    <input type="hidden" name="context" value="<?php echo $models['counter']['context']['name']; ?>"/>
    <p><?php echo $models['counter']['context']['label']; ?></p>
<?php } ?>
<?php if (isset($models['counter']['user'])) { ?>
    <input type="hidden" name="user"    value="<?php echo $models['counter']['user']['login']; ?>"/>
    <p><?php echo $models['counter']['user']['name']; ?></p>
<?php } ?>
<?php if (isset($models['counter']['readers'])) { ?>
    <input type="hidden" name="readers" value="<?php echo $models['counter']['readers']['name']; ?>"/>
    <p><?php echo $models['counter']['readers']['label']; ?></p>
<?php } ?>
<?php if (!isset($models['counter']['context']) && !isset($models['counter']['user']) && !isset($models['counter']['readers']) && $user->isManager()) { ?>
	<p><?php echo $locale->all; ?>
<?php }?>
    <div>
        <label for="start"><?php echo $locale->from ?></label>
        <input type="date" id="start" name="start" required data-empty="no" value="<?php echo isset($models['counter']['range']['start']) ? $models['counter']['range']['start'] : date("Y").'-01-01'; ?>"/>
        <label for="end"><?php echo $locale->to ?></label>
        <input type="date" id="end" name="end" required data-empty="no" value="<?php echo isset($models['counter']['range']['end']) ? $models['counter']['range']['end'] : date("Y").'-12-31'; ?>"/>
        <input type="submit" value="<?php echo $locale->report ?>"/>
    </div>
</form>
<?php if (isset($models['counter']['reports']) && isset($models['counter']['months'])) { ?>
<div id="reports" data-id="<?php echo $models['counter']['id']; ?>">
<?php   foreach (array_keys($models['counter']['reports']) as $type) { ?>
    <a href="/counter?type=<?php echo $type; ?>&id=<?php echo $models['counter']['id']; ?>" download="<?php echo $models['counter']['prefix'].'BR'.$type.'_'.$models['counter']['range']['start'].'_'.$models['counter']['range']['end'].'.xlsx'; ?>"><?php echo $locale->download; ?></a>
    <div class="report">
<?php     $this->render('report', ['counter' => $models['counter'], 'type' => $type]); ?>
    </div>
<?php   } ?>
<?php } ?>