<script type="text/javascript">
// <![CDATA[
	if (!Application) var Application = {};
	if (!Application.Page) Application.Page = {};
	if (!Application.Page.ClientCAPTCHA) {
		Application.Page.ClientCAPTCHA = {
			sessionIDString: '',
			captchaURL: [],
			getRandomLetter: function () { return String.fromCharCode(Application.Page.ClientCAPTCHA.getRandom(65,90)); },
			getRandom: function(lowerBound, upperBound) { return Math.floor((upperBound - lowerBound + 1) * Math.random() + lowerBound); },
			getSID: function() {
				if (Application.Page.ClientCAPTCHA.sessionIDString.length <= 0) {
					var tempSessionIDString = '';
					for (var i = 0; i < 32; ++i) tempSessionIDString += Application.Page.ClientCAPTCHA.getRandomLetter();
					Application.Page.ClientCAPTCHA.sessionIDString.length = tempSessionIDString;
				}
				return Application.Page.ClientCAPTCHA.sessionIDString;
			},
			getURL: function() {
				if (Application.Page.ClientCAPTCHA.captchaURL.length <= 0) {
					var tempURL = '{$captcha_baseurl}?c=';
					
					{if $captcha_dynamic}
						tempURL += Application.Page.ClientCAPTCHA.getRandom(1,1000);
						{if $captcha_session}
							tempURL += '&ss=' + Application.Page.ClientCAPTCHA.getSID();
						{/if}
						Application.Page.ClientCAPTCHA.captchaURL.push(tempURL);
					{else}
						for (var i = 0; i < {$captcha_length}; ++i) {
							var tempEach = tempURL + i;
							{if $captcha_session}
								tempEach += '&ss=' + Application.Page.ClientCAPTCHA.getSID();
							{/if}
							Application.Page.ClientCAPTCHA.captchaURL.push(tempEach);
						}
					{/if}
				}
				return Application.Page.ClientCAPTCHA.captchaURL;
			}
		}
	}

	var temp = Application.Page.ClientCAPTCHA.getURL();
	for (var i = 0, j = temp.length; i < j; i++) document.write('<img src="' + temp[i] + '" alt="img' + i + '" />');
// ]]>
</script>
