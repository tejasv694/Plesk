</select>
<script>
	function ShowPreview() {
		Template = document.getElementById('TemplateID');
		selectedTemplate = Template.selectedIndex;
		if (selectedTemplate == -1 || selectedTemplate == 0) {
			alert('%%LNG_SelectTemplate%%');
			document.getElementById('TemplateID').focus();
			return false;
		}
		selectedTemplateID = Template.options[Template.selectedIndex].value;
		url = 'index.php?Page=Templates&Action=View&id=' + selectedTemplateID;
		window.open(url);
	}
</script>

