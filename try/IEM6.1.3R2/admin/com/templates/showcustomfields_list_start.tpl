<script>
	window.focus();
	$('.CustomFieldArea ul').hide();
	$($('.CustomFieldArea ul').get(0)).show();
	$('.DropDownArrow').click(function(event) {
		$('ul', $(this).parent().parent()).toggle();
		event.preventDefault();
		event.stopPropagation();
	});
</script>
<div>
	<div class="toolTipBox" style="padding:10px; margin: 10px 10px 0 10px; background-image: url('images/infoballon.gif'); background-repeat: no-repeat; padding-left: 24px; background-position: 5px 10px;">
		%%LNG_CustomFields_Description%%
	</div>
	<div class="customfieldlist" style="480px; padding: 10px; margin-left: 10px;">%%TPL_ShowCustomFields_List_Details%%</div>
</div>