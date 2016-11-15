<script type="text/x-jquery-tmpl" id="widget_row">
	<?= (new CRow([
			(new CTextBox('widgets[#{rowNum}][value]', '', false, 64))->setWidth(ZBX_TEXTAREA_SMALL_WIDTH),
			'&rArr;',
			(new CTextBox('widgets[#{rowNum}][newvalue]', '', false, 64))->setWidth(ZBX_TEXTAREA_SMALL_WIDTH),
			(new CButton('widgets[#{rowNum}][remove]', _('Remove')))
				->addClass(ZBX_STYLE_BTN_LINK)
				->addClass('element-table-remove')
		]))
			->addClass('form_row')
			->toString()
	?>
</script>
<script type="text/javascript">
	jQuery(function($) {
		$('#widgets_table').dynamicRows({
			template: '#widget_row'
		});

		// clone button
		$('#clone').click(function() {
			$('#valuemapid, #delete, #clone').remove();
			$('#update')
				.text(<?= CJs::encodeJson(_('Add')) ?>)
				.attr({id: 'add', name: 'add'});
			$('#form').val('clone');
			$('#name').focus();
		});
	});
</script>
