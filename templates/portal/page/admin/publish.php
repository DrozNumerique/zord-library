   				<input type="hidden" id="context" value="<?php echo $context; ?>"/>
				<div align="center">
           			<div class="admin-panel-title"><?php echo Zord::getLocaleValue('title', Zord::value('context', $context), $lang); ?></div>
            		<div class="admin-panel-title"><?php echo $locale->tab->publish->books; ?></div>
               		<table id="books" class="admin-table">
               			<thead>
               				<tr>
               					<th style="width: 145px;">
               						<span class="sort" data-column="0">
               							<?php echo $locale->tab->publish->book->id; ?>
               							<i class="fa fa-sort fa-fw"></i>
               						</span>
               					</th>
               					<th style="width: 615px;">
               						<span class="sort" data-column="1">
               							<?php echo $locale->tab->publish->book->title; ?>
               							<i class="fa fa-sort fa-fw"></i>
               						</span>
               					</th>
               					<th style="width: 30px;">
               						<i id="expand-list" class="fa fa-compress fa-fw" title="<?php echo $locale->tab->publish->select; ?>"></i>
               					</th>
               				</tr>
               			</thead>
               			<tbody>
<?php foreach (LibraryAdmin::books($context) as $book) { ?>
                        	<tr class="data sort" data-included="<?php echo $book['status'] !== 'no' ? 'yes' : 'no'; ?>">
                        		<td>
                        			<input name="book" data-isbn="<?php echo $book['isbn']; ?>" type="hidden" value="<?php echo $book['isbn']; ?>"/>
                        			<span class="content" title="<?php echo $locale->tab->publish->show; ?>"><?php echo $book['isbn']; ?></span>
                        		</td>
                        		<td>
                        			<input name="title" data-isbn="<?php echo $book['isbn']; ?>" type="hidden" value="<?php echo $book['isbn']; ?>"/>
                        			<span class="content" title="<?php echo $locale->tab->publish->epub; ?>"><?php echo $book['title']; ?></span>
                        		</td>
                        		<td class="state" data-type="<?php echo $user->isManager() ? 'manage' : 'select'; ?>">
          							<input name="check" data-empty="no" type="hidden" value="<?php echo $book['status']; ?>"/>
          							<i class="display fa <?php echo $book['status'] === 'new' ? 'fa-star' : 'fa-check'; ?> fa-fw <?php echo $book['status'] == 'no' ? 'hidden' : ''; ?>"></i>
                        		</td>
                        	</tr>
<?php } ?>
               			</tbody>
               		</table>
      				<br/>
     				<br/>
    		        <input id="submit-publish" type="button" class="admin-button" value="<?php echo $locale->tab->publish->submit; ?>"/>
     				<br/>
     				<br/>
				</div>
