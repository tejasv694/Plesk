<form method="post" action="index.php?Page=Subscribers&Action=Edit&SubAction=Save&List=%%GLOBAL_list%%">
	<table cellspacing="0" cellpadding="0" width="100%" align="center">
		<tr>
			<td class="Heading1">
				%%LNG_Subscribers_Edit%%
			</td>
		</tr>
		<tr>
			<td class="body pageinfo">
				<p>
					%%LNG_Subscribers_Edit_Intro%%
				</p>
			</td>
		</tr>
		<tr>
			<td>
				%%GLOBAL_Message%%
			</td>
		</tr>
		<tr>
			<td>
				<input class="FormButton" type="submit" value="%%LNG_Save%%">
				<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if(confirm("%%LNG_Subscribers_Edit_CancelPrompt%%")) { document.location="index.php?Page=Subscribers&Action=Manage&SubAction=Step3&List=%%GLOBAL_list%%" }'>
				<br />
				&nbsp;
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_EditSubscriberDetails%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Required"}
							%%LNG_Email%%:&nbsp;
						</td>
						<td>
							<input type="text" name="emailaddress" value="%%GLOBAL_emailaddress%%" class="Field250">
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Required"}
							%%LNG_Format%%:&nbsp;
						</td>
						<td>
							<select name="format" class="Field250">
								%%GLOBAL_FormatList%%
							</select>
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Required"}
							%%LNG_ConfirmedStatus%%:&nbsp;
						</td>
						<td>
							<select name="confirmed" class="Field250">
								%%GLOBAL_ConfirmedList%%
							</select>
						</td>
					</tr>
					%%GLOBAL_CustomFieldInfo%%
					<tr>
						<td>
							&nbsp;
						</td>
						<td>
							<input type="hidden" name="subscriberid" value="%%GLOBAL_subscriberid%%">
							<input class="FormButton" type="submit" value="%%LNG_Save%%">
							<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if(confirm("%%LNG_Subscribers_Edit_CancelPrompt%%")) { document.location="index.php?Page=Subscribers&Action=Manage&SubAction=Step3&List=%%GLOBAL_list%%" }'>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>
