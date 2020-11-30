<style type="text/css">
	.popupContainer {
		border: 0px;
	}
</style>
<script>
	$(function() {
		$(document.frmLogin.ss_takemeto).val('%%GLOBAL_ss_takemeto%%');
	});
</script>
<form action="index.php?Page=%%PAGE%%&Action=%%GLOBAL_SubmitAction%%" method="post" name="frmLogin" id="frmLogin">
	<div id="box" class="loginBox">
		<table><tr><td style="border:solid 2px #DDD; padding:20px; background-color:#FFF; width:300px;">
		<table>
			<tr>
			<td class="Heading1">
				<img src="%%WHITELABEL_ApplicationLogoImage%%" alt="{$lang.SendingSystem}" />
			</td>
			</tr>
			<tr>
			<td style="padding:10px 0px 5px 0px">%%GLOBAL_Message%%</td>
			</tr>
			<tr>
			<td>
				<table>

				<tr>
					<td nowrap="nowrap" style="padding:0px 10px 0px 10px">%%LNG_UserName%%:</td>
					<td>
					<input type="text" name="ss_username" id="username" class="Field150" value="%%GLOBAL_ss_username%%">
					</td>
				</tr>
				<tr>
					<td nowrap="nowrap" style="padding:0px 10px 0px 10px">%%LNG_Password%%:</td>

					<td>
					<input type="password" name="ss_password" id="password" class="Field150" value="">
					</td>
				</tr>
				<tr>
					<td nowrap="nowrap" style="padding:0px 10px 0px 10px">%%LNG_TakeMeTo%%:</td>
					<td>
						<select name="ss_takemeto" class="Field150">
							<option value="index.php">%%LNG_TakeMeTo_HomePage%%</option>
							<option value="index.php?Page=Subscribers&Action=Manage">%%LNG_TakeMeTo_Contacts%%</option>
							<option value="index.php?Page=Lists">%%LNG_TakeMeTo_Lists%%</option>
							<option value="index.php?Page=Segment">%%LNG_TakeMeTo_Segments%%</option>
							<option value="index.php?Page=Newsletters&Action=Manage">%%LNG_TakeMeTo_Campaign%%</option>
							<option value="index.php?Page=Autoresponders&Action=Manage">%%LNG_TakeMeTo_Autoresponder%%</option>
							<option value="index.php?Page=Stats">%%LNG_TakeMeTo_Statistics%%</option>
						</select>
					</td>
				</tr>
				<tr>
					<td nowrap>&nbsp;</td>
					<td>&nbsp;<input type="checkbox" name="rememberme" id="remember" value="1" style="margin-left:-0px" > <label for="remember">%%LNG_RememberMe%%</label>
					</td>
				</tr>
					<tr>
					<td>&nbsp;</td>
					<td>
						<input type="submit" name="SubmitButton" value="%%LNG_Login%%" class="FormButton">
						&nbsp;&nbsp;%%LNG_ForgotPasswordReminder%%
					</td>
					</tr>

					<tr><td class="Gap"></td></tr>
				</table>
			</td>
			</tr>
		</table>
		</td></tr>

		<tr>
			<td>

				<div class="PageFooter" style="padding: 10px 10px 10px 0px; margin-bottom: 20px; text-align: center;">
					%%LNG_Copyright%%
				</div>
			</td>
		</tr>

		</table>

	</div>

	</form>

	<script>

		$('#frmLogin').submit(function() {
			var f = document.frmLogin;

			if(f.username.value == '')
			{
				alert('Please enter your username.');
				f.username.focus();
				f.username.select();
				return false;
			}

			if(f.password.value == '')
			{
				alert('Please enter your password.');
				f.password.focus();
				f.password.select();
				return false;
			}

			// Everything is OK
			f.action = 'index.php?Page=%%PAGE%%&Action=%%GLOBAL_SubmitAction%%';
			return true;
		});

		function sizeBox() {
			var w = $(window).width();
			var h = $(window).height();
			$('#box').css('position', 'absolute');
			$('#box').css('top', h/2-($('#box').height()/2)-50);
			$('#box').css('left', w/2-($('#box').width()/2));
		}

		$(document).ready(function() {
			sizeBox();
			$('#username').focus();
		});

		$(window).resize(function() {
			sizeBox();
		});
		createCookie("screenWidth", screen.availWidth, 1);

	</script>