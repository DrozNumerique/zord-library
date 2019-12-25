<div align="center">
	<h1><?php echo $locale->records; ?></h1>
	<div>
		<button id="selectall"><?php echo $locale->selectall; ?></button>
		<button id="unselectall"><?php echo $locale->unselectall; ?></button>
<?php foreach (array_keys(Zord::getConfig('records')) as $format) { ?>
		<button class="format" data-format="<?php echo $format; ?>"><?php echo $format; ?></button>
<?php } ?>
	</div>
</div>
<?php
    foreach(['new','other'] as $status) { 
        if (isset($models['books'][$status]) && count($models['books'][$status]) > 0) { 
?>
<br/>
<br/>
<div>
	<table>
		<thead>
			<tr>
				<th> </th>
				<th>ISBN</th>
				<th><?php echo $locale->authors; ?></th>
				<th><?php echo $locale->title; ?></th>
				<th><?php echo $locale->editors; ?></th>
				<th><?php echo $locale->date; ?></th>
			</tr>
		</thead>
		<tbody>
<?php foreach ($models['books'][$status] as $book) { ?>
			<tr>
				<td class="t_check"><input value="<?php echo $book['isbn']; ?>" data-type="check" type="checkbox"></td>
				<td class="t_id" data-isbn="<?php echo $book['isbn']; ?>"><a href="<?php echo $baseURL.'Book/header?isbn='.$book['isbn']; ?>"><?php echo $book['isbn']; ?></a></td>
				<td class="t_person" data-author_ss="<?php echo implode('|', $book['authors']); ?>"><?php echo Library::listActors($book['authors'], 'HTML'); ?></td>
				<td class="t_title" data-sort="<?php echo $book['title']; ?>"><a href="<?php echo $baseURL.'book/'.$book['isbn']; ?>"><?php echo $book['title']; ?></a></td>
				<td class="t_person" data-editor_ss="<?php echo implode('|', $book['editors']); ?>"><?php echo Library::listActors($book['editors'], 'HTML'); ?></td>
				<td class="t_date"><?php echo $book['date']; ?></td>
			</tr>
<?php } ?>
	</table>
</div>
<?php 
        }
    }
?>
