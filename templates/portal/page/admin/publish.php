<?php $context = $_REQUEST['ctx'] ?? $context; ?>
				<div align="center">
					<select id="context">
<?php foreach (Zord::getConfig('context') as $name => $data) { ?>
						<option value="<?php echo $name; ?>" <?php echo $name == $context ? 'selected' : ''; ?>><?php echo Zord::getLocaleValue('title', Zord::value('context', $name), $lang); ?></option>
<?php } ?>
					</select>
               		<table id="books" class="admin-table">
               			<thead>
               				<tr>
               					<th class="isbn">
               						<span class="sort" data-column="0">
               							<?php echo $locale->tab->publish->book->id; ?>
               							<i class="fa fa-sort fa-fw"></i>
               						</span>
               					</th>
               					<th class="title">
               						<span class="sort" data-column="1">
               							<?php echo $locale->tab->publish->book->title; ?>
               							<i class="fa fa-sort fa-fw"></i>
               						</span>
               					</th>
               					<th class="action"></th>
               					<th class="action"></th>
<?php if ($user->isManager()) { ?>
                        		<th class="action"></th>
<?php } ?>
               				</tr>
               			</thead>
               			<tbody>
<?php foreach (Library::books($context) as $book) { ?>
                        	<tr class="data sort" data-included="<?php echo $book['status'] !== 'no' ? 'yes' : 'no'; ?>">
                        		<td class="show" data-open="<?php echo $context; ?>" data-action="show" data-isbn="<?php echo $book['isbn']; ?>" data-context="<?php echo $context; ?>">
                        			<input name="book" type="hidden" value="<?php echo $book['isbn']; ?>"/>
                        			<span class="content" title="<?php echo $locale->tab->publish->show; ?>"><?php echo $book['isbn']; ?></span>
                        		</td>
                        		<td class="show" data-open="<?php echo $context; ?>" data-action="show" data-isbn="<?php echo $book['isbn']; ?>" data-context="<?php echo $context; ?>">
                        			<input name="title" data-isbn="<?php echo $book['isbn']; ?>" type="hidden" value="<?php echo $book['isbn']; ?>"/>
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
				</div>
