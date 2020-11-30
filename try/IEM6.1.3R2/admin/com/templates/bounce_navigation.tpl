<div id="Bounce_Step_Nav_Container">
	<ul style="margin-left:0px;">
		<li class="Bounce_Step_Nav_First">
			<span class="Bounce_Step_Nav_General_First">
				<span style="background:url('images/1.gif') no-repeat center left;" class="Bounce_Step_Nav_Number">
				{$lang.Pick_Contact_List}
				</span>
			</span>
		</li>
		<li class="Bounce_Step_Nav">
			<span class="Bounce_Step_Nav_General">
				<span style="background:url('images/2.gif') no-repeat center left;" class="Bounce_Step_Nav_Number">
				{$lang.Find_Mail_Server}
				</span>
			</span>
		</li>
		<li class="Bounce_Step_Nav">
			<span class="Bounce_Step_Nav_General">
				<span style="background:url('images/3.gif') no-repeat center left;" class="Bounce_Step_Nav_Number">
				{$lang.Find_Bounces}
				</span>
			</span>
		</li>
		<li class="Bounce_Step_Nav">
			<span class="Bounce_Step_Nav_General">
				<span style="background:url('images/4.gif') no-repeat center left;" class="Bounce_Step_Nav_Number">
				{$lang.Remove_Contacts}
				</span>
			</span>
		</li>
		<li class="Bounce_Step_Nav">
			<span class="Bounce_Step_Nav_General">
				<span style="background:url('images/5.gif') no-repeat center left;" class="Bounce_Step_Nav_Number">
				{$lang.Finished}
				</span>
			</span>
		</li>
		<li class="Bounce_Step_Nav_Last">
			<span class="Bounce_Step_Nav_General">&nbsp;</span>
		</li>
	</ul>
</div>

<script>

	function findStep(num)
	{
		var select = '#Bounce_Step_Nav_Container > ul > li';
		return $(select + ':eq(' + num + ')');
	}

	function highlightNav()
	{
		var cur = {$step} - 1;
		var setclass = {
			'current': 'Bounce_Step_Nav_Selected',
			'next': 'Bounce_Step_Nav_After_Selected'
		};
		switch ({$step}) {
		case 1:
			setclass['current'] = 'Bounce_Step_Nav_Selected_First';
			break;
		case 5:
			setclass['next'] = 'Bounce_Step_Nav_Selected_Last';
			break;
		}
		findStep(cur).removeClass().addClass(setclass['current']);
		findStep(cur+1).removeClass().addClass(setclass['next']);
	}

	$(function() {
		highlightNav();
	});
</script>
