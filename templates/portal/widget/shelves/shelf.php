				<div data-panel="<?php echo $models['shelf']['name']; ?>" class="<?php echo implode(' ', $models['shelf']['class']); ?>" style="display:<?php echo $models['shelf']['apart'] ? "block" : "none"; ?>;">
					<table id="<?php echo $models['shelf']['name']; ?>">
						<thead>
							<tr>
								<th class="source"  colspan="2">
									<span style="white-space: nowrap;">
										<span class="sort" data-column="0">
											<i class="fa fa-sort fa-fw"></i>
											[&nbsp;
										</span>
										<?php echo $locale->source_date."\n"; ?>
										<span class="sort" data-column="1">
											&nbsp;]
											<i class="fa fa-sort fa-fw"></i>
										</span>
									</span>
								</th>
								<th class="authors"  style="width: 150px;">
									<span class="sort" data-column="2">
										<?php echo $locale->authors."\n"; ?>
										<i class="fa fa-sort fa-fw"></i>
									</span>
								</th>
								<th class="title"    style="width: 450px;">
									<span class="sort" data-column="3">
										<?php echo $locale->title."\n"; ?>
										<i class="fa fa-sort fa-fw"></i>
									</span>
								</th>
								<th class="editors"  style="width: 200px;">
									<span class="sort" data-column="4">
										<?php echo $locale->editors."\n"; ?>
										<i class="fa fa-sort fa-fw"></i>
									</span>
								</th>
								<th class="date"	 style="width: 55px;" >
									<span class="sort" data-column="5">
										<?php echo $locale->publication_date."\n"; ?>
										<i class="fa fa-sort fa-fw"></i>
									</span>
								</th>
<?php if (in_array('search', $models['shelf']['class'])) { ?>
								<th class="category" style="width: 30px;" >
									<span class="sort" data-column="6">
										<?php echo $locale->category."\n"; ?>
										<i class="fa fa-sort fa-fw"></i>
									</span>
								</th>
								<th class="search"   style="width: 30px;" >
									<i class="fa fa-search fa-fw"></i>
								</th>
<?php } ?>
							</tr>
						</thead>
						<tbody>
<?php foreach ($models['shelf']['books'] as $book) { ?>
							<tr class="book sort<?php echo !$book['readable'] ? ' unreadable' : ''; ?>" data-instances="<?php echo $models['shelf']['instances'][$book['isbn']]; ?>">
								<td class="t_date source" colspan="<?php echo $book['source']; ?>"><?php echo $book['from']; ?></td>
								<td class="t_date source" style="display:<?php echo $book['source'] != 2 ? 'table-cell' : 'none'; ?>;"><?php echo $book['to']; ?></td>
								<td class="t_person authors"><?php echo Library::listActors($book['creator'], 'HTML'); ?></td>
								<td class="t_title title">
									<a href="<?php echo $baseURL.'/book/'.$book['isbn'].(isset($models['search']) ? '?search='.$models['search'] : ''); ?>" class="content"><i class="fa fa-<?php echo $book['readable'] ? 'unlock-alt' : 'lock'; ?> fa-fw" style="color: <?php echo $book['readable'] ? 'green' : '#C0392B'; ?>;"></i><?php echo Library::title($book['title'], $book['subtitle']); ?></a>
								</td>
								<td class="t_person editors"><?php echo Library::listActors($book['editor'], 'HTML'); ?></td>
								<td class="t_date date"><?php echo $book['date']; ?></td>
<?php   if (in_array('search', $models['shelf']['class'])) { ?>
								<td class="category"><?php echo implode(',', $book['category']); ?></td>
								<td class="search" data-isbn="<?php echo $book['isbn']; ?>" style="color:white;"><i class="fa fa-check fa-fw"></i></td>
<?php   } ?>
							</tr>
<?php   $this->render('matches', ['isbn' => $book['isbn'], 'parts' => $book['parts'], 'matches' => $book['matches']]); ?>
<?php } ?>
						</tbody>
					</table>
				</div>
