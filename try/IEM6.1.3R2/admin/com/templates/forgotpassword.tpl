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
					<td nowrap="nowrap" style="padding:0px 10px 0px 10px">%%LNG_UserName%%:</td>
					<td>
					<input type="text" name="ss_username" id="username" class="Field150" value="%%GLOBAL_ss_username%%">
					</td>
				</tr>
					<tr>
					<td>&nbsp;</td>
					<td>
						<input type="submit" name="SubmitButton" value="%%LNG_SendPassword%%" class="FormButton">
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

				if(f.ss_username.value == '')
				{
					alert('%%LNG_NoUsername%%');
					f.ss_username.focus();
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
		createCookie("screenWidth", screen.availWidth, 1);

	</script>