<script>
	cf_bucket = %%GLOBAL_CustomFieldJSON%%
	$('a.CustomFieldLink').click(function() {
		key = this.id;
		InsertLink(cf_bucket[key], '%%GLOBAL_ContentArea%%', '%%GLOBAL_EditorName%%');
		linkWin.close();
		return false;
	});
</script>
