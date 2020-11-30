<html>
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<script language="javascript" type="text/javascript">
		function checkboxValidation()
		{
			valid = false;
			for(i = 0; i < document.forms[0].elements.length; i++) {
				element = document.forms[0].elements[i];
				if (element.type == 'checkbox') {
					if (element.checked) {
						valid = true;
					}
				}
			}
			
			if (!valid) {
				alert ('%%LNG_Unsubscribe_InvalidList%%');
				return false;
			} else {
				 document.unsubscribe_form.submit();
				return true;
			}

			
		}
	</script>
	</head>
		<body>
		<style type="text/css">
		
			.myForm td, input, select, textarea, checkbox, div {
				font-family: tahoma;
				font-size: 12px;
			}
		
			.required {
				color: red;
			}
			
			.FlashMessage {
				background-color:#FFF1A8;
				margin-bottom:15px;
				margin-left:5px;
				padding:8px 5px 8px 10px;
			}
		
		</style>

		<form name="unsubscribe_form" action="unsubscribe_confirmed.php" method="get">  
			%%GLOBAL_Message%%
			{foreach from=$page item=each key=index}
			<input type="hidden" name="{$index}" value="{$each}" />  
			{/foreach}
			<table border="0" cellpadding="2" class="myForm">
				<tr valign="top">
					<td>
					<span class="required">*</span>&nbsp;Contact Lists:
					</td>
					<td>
						<table cellpadding="0" cellspacing="0">
						{foreach from=$list item=each}
							<tr valign="top">
								<td>
									<label for="lists_{$each.listid}">
									<input type="checkbox" id="lists_{$each.listid}" name="lists[{$each.subscriberid}][{$each.listid}]" value="{$each.cc}" />{$each.name}</label>
								</td>
							</tr>
						{/foreach}
						</table>
					</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td>
						<input type="button" value="%%LNG_Unsubscribe_Yes%%" onclick="checkboxValidation();" />
					</td>
				</tr>
			</table>

		         
		</form>  
	</body>
</html>