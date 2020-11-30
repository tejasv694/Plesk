<script>
	$('a.TagLink').click(function() {
		key = this.id;
		name = $(this).text();
		InsertLink('['+name+']', '%%GLOBAL_ContentArea%%', '%%GLOBAL_EditorName%%');
		linkWin.close();
		return false;
	});
</script>
