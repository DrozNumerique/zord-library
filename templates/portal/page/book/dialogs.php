		<div id="template_dialog_citation">
			<div class="dialog-box">
				<p class="dialog-title"><?php echo $locale->dialogs->comment; ?></p>
				<div class="dialog-content">
					<button data-id="dialog_citation_cancel" style="margin-top:0"><?php echo $locale->dialogs->cancel; ?></button>
					&#160;&#160;&#160;&#160;&#160;
					<button data-id="dialog_citation_ok" style="margin-top:0"><?php echo $locale->dialogs->ok; ?></button>
					<br/>
					<p class="dialog-subtitle" style="margin-top:30px"><?php echo $locale->dialogs->addnote; ?></p>
					<textarea data-id="dialog_citation_comment"></textarea><br/>
				</div>
			</div>
		</div>
		<div id="template_dialog_bug">
			<div class="dialog-box">
				<p class="dialog-title"><?php echo $locale->dialogs->bug; ?></p>
				<div class="dialog-content">
					<button data-id="dialog_bug_cancel" style="margin-top:0"><?php echo $locale->dialogs->cancel; ?></button>
					&#160;&#160;&#160;&#160;&#160;
					<button data-id="dialog_bug_ok" style="margin-top:0"><?php echo $locale->dialogs->ok; ?></button>
					<br/>
					<p class="dialog-subtitle" style="margin-top:30px"><?php echo $locale->dialogs->addnote; ?></p>
					<textarea data-id="dialog_bug_comment"></textarea><br/>
				</div>
			</div>
		</div>
		<div id="template_dialog_citation_valid">
			<div class="dialog-box"><p class="waitmsg"><?php echo $locale->dialogs->add_citation; ?></p></div>
		</div>
		<div id="template_dialog_bug_valid">
			<div class="dialog-box"><p class="waitmsg"><?php echo $locale->dialogs->bug_save; ?></p></div>
		</div>
