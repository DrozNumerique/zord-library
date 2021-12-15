<?php $context = $_REQUEST['ctx'] ?? $context; ?>
               		<table id="books" class="admin-table">
               			<thead>
               				<tr>
<?php foreach (['isbn','title'] as $field) { ?>
               					<th class="sortable <?php echo $field; ?>" data-field="<?php echo $field == 'isbn' ? 'ean' : $field; ?>">
               						<?php echo $locale->tab->publish->book->$field; ?>
<?php   if (($order ?? null) == ($field == 'isbn' ? 'ean' : $field)) { ?>
<?php     $this->render('/portal/widget/list/headers/sort', ['field' => $field, 'direction' => $models['direction']]); ?>
<?php   } ?>
               					</th>
<?php } ?>
<?php for ($index = 0 ; $index < ($user->isManager() ? 3 : 2) ; $index++) { ?>
               					<th class="action"></th>
<?php } ?>
               				</tr>
               			</thead>
               			<tbody>
<?php foreach ($data as $book) { ?>
                        	<tr class="data">
                        		<td class="show" data-open="<?php echo $context; ?>" data-action="show" data-isbn="<?php echo $book['isbn']; ?>" data-context="<?php echo $context; ?>">
                        			<span class="content" title="<?php echo $locale->tab->publish->show; ?>"><?php echo $book['isbn']; ?></span>
                        		</td>
                        		<td class="show" data-open="<?php echo $context; ?>" data-action="show" data-isbn="<?php echo $book['isbn']; ?>" data-context="<?php echo $context; ?>">
                        			<span class="content" title="<?php echo $locale->tab->publish->show; ?>"><?php echo $book['title']; ?></span>
                        		</td>
                        		<td class="epub" data-action="epub" data-isbn="<?php echo $book['isbn']; ?>" data-context="<?php echo $context; ?>">
          							<i class="display fa fa-book fa-fw" title="<?php echo $locale->tab->publish->epub; ?>"></i>
                        		</td>
                        		<td class="state" data-type="publish" data-context="<?php echo $context; ?>" data-book="<?php echo $book['isbn']; ?>">
          							<input name="check" data-empty="no" type="hidden" value="<?php echo $book['status']; ?>"/>
          							<i class="display fa <?php echo Zord::value('portal', ['states','publish',$book['status']]); ?> fa-fw"></i>
                        		</td>
<?php if ($user->isManager()) { ?>
                        		<td class="delete" data-context="<?php echo $context; ?>" data-book="<?php echo $book['isbn']; ?>" style="color: red;">
          							<i class="display fa fa-times fa-fw"></i>
                        		</td>
<?php } ?>
                        	</tr>
<?php } ?>
               			</tbody>
               		</table>
