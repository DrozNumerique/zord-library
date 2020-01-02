		<abbr class="unapi-id" title="<?php echo $models['book']['ISBN']; ?>"></abbr>
<?php $this->render('/portal/widget/slide', ['id' => 'toc', 'top' => '/portal/widget/query', 'content' => '/portal/page/book/toc', 'align' => 'left', 'handle' => '≡']); ?>
		<div id="tools" class="fixed">
			<a id="searchBack" class="fa fa-search fa-fw<?php echo !isset($models['book']['search']) ? ' __disabled' : ''; ?>" href="<?php echo isset($models['book']['search']) ? $baseURL.'/search'.'?id='.$models['book']['search'] : ''; ?>" title="<?php echo $locale->search_back; ?>">
				<i class="fa fa-arrow-left fa-stack-1x searchBack"></i>
			</a>
			<i id="tool_citation" class="fa fa-bookmark fa-fw" title="<?php echo $locale->cite; ?>"></i>
			<i id="switchTemoin" class="fa fa-tag fa-fw" title="<?php echo $locale->references; ?>"></i>
<?php if (isset($models['book']['metadata']['ref_url'])) { ?>
			<a id="get_book" class="fa fa-book fa-fw" target="_blank" title="<?php echo $locale->get_book; ?>" href="<?php echo $models['book']['metadata']['ref_url']; ?>"></a>
<?php } ?>
			<i id="tool_bug" class="fa fa-bug fa-fw __disabled" title="<?php echo $locale->misprint; ?>"></i>
			<i id="quote" class="fa fa-quote-left fa-fw __disabled" title="<?php echo $locale->quote; ?>"></i>
		</div>
		<article id="tei">
			<div id="markerAnchorLeft">❯</div>
			<div id="markerAnchorRight">❮</div>
<?php $this->render('parts'); ?>
		</article>
		<div id="dialogs">
<?php $this->render('dialogs'); ?>
<?php $this->render('/portal/widget/help'); ?>
		</div>
		