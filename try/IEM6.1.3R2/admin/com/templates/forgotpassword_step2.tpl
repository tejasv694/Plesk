<style type="text/css">
	.popupContainer {
		border: 0px;
	}
</style>
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
						<td class="SmallFieldLabel">%%LNG_UserName%%:</td>
						<td align="left">
							%%GLOBAL_UserName%%
						</td>
					</tr>
					<tr>
						<td class="SmallFieldLabel">
							%%LNG_NewPassword%%:
						</td>
						<td align="left">
							<input type="password" id="ss_password" name="ss_password" class="Field150" value="" autocomplete="off" />
						</td>
					</tr>
					<tr>
						<td class="SmallFieldLabel">
							%%LNG_PasswordConfirm%%:
						</td>
						<td align="left">
							<input type="password" name="ss_password_confirm" value="" class="Field150" autocomplete="off" />
						</td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td>
							<input type="submit" name="SubmitButton" value="%%LNG_ChangePassword%%"  class="Field150">
						</td>
					</tr>

					<tr><td class="Gap"></td></tr>
				</table>
			</td>
			</tr>
		</table>
		</td></tr>
		</table>

	</div>

	</form>

	<script>

		$('#frmLogin').submit(function() {
			var f = document.frmLogin;

			if (f.ss_password.value == '') {
				alert('%%LNG_NoPassword%%');
				f.ss_password.focus();
				return false;
			}

			if (f.ss_password_confirm.value == "") {
				alert("%%LNG_PasswordConfirmAlert%%");
				f.ss_password_confirm.focus();
				return false;
			}

			if (f.ss_password.value != f.ss_password_confirm.value) {
				alert("%%LNG_PasswordsDontMatch%%");
				f.ss_password_confirm.select();
				f.ss_password_confirm.focus();
				return false;
			}

			// Everything is OK
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
			$('#ss_username').focus();
			$('#ss_username').select();
		});

		$(window).resize(function() {
			//sizeBox();
		});

	document.getElementById('ss_password').focus();

	</script>