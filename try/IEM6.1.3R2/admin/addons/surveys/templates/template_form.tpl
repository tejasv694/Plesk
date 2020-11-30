<script>
	$(document).ready(function(){
	/*
		$('.color_change').keyup(function() {
			var show_div = $('#' + this.id + "_show");
			show_div.css('background','#' + $(this).val());
		});
	*/	
		templateSetStyle(default_style);
	});
</script>

<form name="frmEditNewsletter" method="post" action="{$AdminUrl}&Action=Templates&SubAction=Save" enctype="multipart/form-data">
	<table cellspacing="0" cellpadding="0" width="100%" align="center">
		<tr>
			<td class="Heading1">
				{$Heading}
			</td>
		</tr>
		<tr>
			<td class="body pageinfo">
				<p>
					{$Intro}
				</p>
			</td>
		</tr>
		<tr>
			<td>
				{$Message}
			</td>
		</tr>
		<tr>
			<td>
				<input class="FormButton" type="submit" value="%%LNG_Save%%">
				<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if(confirm("%%GLOBAL_CancelButton%%")) { document.location="index.php?Page=Newsletters" }'>
				<br />
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_Addon_survey_Template_Details%%
						</td>
					</tr>
					<tr>
						<td width="10%" class="FieldLabel">
							<img src="images/blank.gif" width="200" height="1" /><br />
							%%LNG_Addon_survey_Template_Name%%:
						</td>
						<td width="90%">
							<input type="text" name="name" value="" class="Field250" style="width:300px">
						</td>
					</tr>
					
					<tr>
						<td width="10%" class="FieldLabel">
							<img src="images/blank.gif" width="200" height="1" /><br />
							%%LNG_Addon_survey_Template_Properties%%:
						</td>
						<td width="90%">
						</td>
					</tr>
					
					<tr>
						<td colspan="2">
							<table id="question_style">
								<tr>
									<td style="padding-left: 10px;vertical-align: top;">
										<select size="10" id="style_name">
											<option value="surveytitle">Survey Title</option>
											<option value="questiontitle">Question Title</option>
											<option value="questionchoices">Question Choices</option>
											<option value="welcometext">Welcome Text</option>
										</select>
									</td>
									
									<td style="vertical-align: top;">
										<table id="style_config">
											<tr>
												<td>
													Font:
												</td>
												<td colspan="2">
													<select class="style_font">
														<option value="arial">Arial</option>
														<option value="tahoma">Tahoma</option>
														<option value="verdana">Verdana</option>
													</select>
												</td>
											</tr>
											<tr>
												<td>
													Size:
												</td>

												<td>
													<select style="width: 100px;" class="style_size">
														<option value="1">1 (smallest)</option>
														<option value="2">2</option>
														<option value="3">3</VVoption>
														<option value="4">4</option>
														<option value="5">5 (biggest)</option>
													</select>
												</td>
											</tr>
											
											<tr>
												<td>Color:</td>
												<td><input type="text" value="" id="color_change_2" class="color_change style_color"  style="width: 100px;"></td>
												<td>
												<!-- <div id="color_change_2_show" style="position:relative;width: 30px;height: 30px;border: 1px solid #000;">&nbsp;</div> -->
												</td>
											</tr>
											
											<tr>
												<td>Background:</td>
												<td><input type="text" value="" id="color_change_3" class="color_change style_background"  style="width: 100px;"></td>
												<td>
												<!-- <div id="color_change_3_show" style="position:relative;width: 30px;height: 30px;border: 1px solid #000;">&nbsp;</div> -->
												</td>
											</tr>
											
											<tr>
												<td colspan="3" style="text-align: center;">
													<label for="style_1_bold">
														<input type="checkbox" name="" id="style_1_bold" class="style_bold">
														<span style="font-weight: bold;">
															Bold
														</span>
													</label>
													
													<label for="style_1_italic">
														<input type="checkbox" name="" id="style_1_italic" class="style_italic">
														<span style="font-style: italic;">
															Italic
														</span>
													</label>
													
													<label for="style_1_underline">
														<input type="checkbox" name="" id="style_1_underline" class="style_underline">
														<span style="text-decoration: underline;">
															Underline
														</span>
													</label>
												</td>
											</tr>
										</table>
										
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>

<style>
	.question_title {
	}
	
</style>

<script>
	var currentStyleName = false;
	$(function() {
		$('.style_font, .style_size',$('#question_style')).change(function() {
			templateUpdateStyle();
		});
		
		$('.style_color, .style_background').keyup(function() {
			templateUpdateStyle();
		});
		
		$('.style_bold, .style_italic, .style_underline').click(function() {
			templateUpdateStyle();
		});
		
		function templateUpdateStyle() {
			var style_name = $('#style_name').val();
			templateSaveStyle(currentStyleName);
			templateSetStyle(currentStyle);
		}
		
		$('#style_name').change(function() {
			if ($('#style_name')[0].selectedIndex < 0) {
				$('#style_config').hide();
			} else {
				if (currentStyleName) {
					// save the current style
					templateSaveStyle(currentStyleName);
				}
				
				// load the new style into the fields
				templateLoadStyle($('#style_name').val());
				
				currentStyleName = $('#style_name').val();
			}
		});
		$('#style_config').hide();
		templateSetStyle(currentStyle);
	});

	var currentStyle;
	function templateSetStyle(style) {
		var q = $('#survey_page');
		templateSetStyles('.question_title',style.questiontitle,q);
		templateSetStyles('.question_input li label',style.questionchoices,q);
		templateSetStyles('.survey_title',style.surveytitle,q);
		templateSetStyles('.survey_welcome',style.welcometext,q);
		currentStyle = style;
	}
	
	function templateGetStyle() {
		var q = $('#question_style');
		style = {
			font: $('.style_font',q).val(),
			size: Number($('.style_size',q).val()),
			color: $('.style_color',q).val(),
			background: $('.style_background',q).val(),
			bold: $('.style_bold',q)[0].checked,
			italic: $('.style_italic',q)[0].checked,
			underline: $('.style_underline',q)[0].checked
		}
		return style;24.109.241.244/iem_dev/
	}
	
	function templateSetStyles(selector,style,context) {
		$.each(style,function(idx) {
			var style = idx;
			var value = this;
			//if (this != false) {
				switch (idx) {
					case 'color':
					case 'background':
						value = '#' + this;
					break;
					
					case 'font':
						style = 'font-family';
						value = this;
					break;
					
					case 'size':
						style = 'font-size';
						value = 10 + Number(this);
					break;
					
					case 'italic':
						style = 'font-style';
						value = 'italic';
					break;24.109.241.244/iem_dev/
					
					case 'underline':
						style = 'text-decoration';
						value = 'underline';
					break;
					
					case 'bold':
						style = 'font-weight';
						value = 'bold';
					break;
					
					default:
				}
				
				
				if (this == false && (idx != 'color' && idx != 'background')) {
					if (idx == 'underline') {
						value = "none";
					} else {
						value = "normal";
					}
				}
				
				$(selector,context).css(style,value);
			//}
		});
	}
	
	function templateSetSelection(elm,value) {
		$.each(elm[0].options,function(idx) {
			if (this.value == value) {
				elm[0].selectedIndex = idx;
				return false;
			}
		});
	}
	
	function templateSaveStyle(stylename) {
		var style = templateGetStyle();
		currentStyle[stylename] = style;
	}
	
	function templateLoadStyle(stylename) {
		if (currentStyle[stylename] != null) {
			$('#style_config').show();
			templateSetSelection($('.style_font'),currentStyle[stylename].font);
			templateSetSelection($('.style_size'),currentStyle[stylename].size);
			$('.style_color').val(currentStyle[stylename].color);
			$('.style_background').val(currentStyle[stylename].background);
			
			$.each(['bold','underline','italic'],function(idx) {
				if (currentStyle[stylename][this]) {
					$('.style_' + this).attr('checked','checked');
				} else {
					$('.style_' + this).attr('checked','');
				}
			});
		} else {
			$('#style_config').hide();
		}
	}
	
	var default_style = {
		surveytitle: {
			font: "verdana",
			size: 3,
			color: '000000',
			background: 'ffffff',
			bold: true,
			italic: true,
			underline: true
		},
		questiontitle: {
			font: "verdana",
			size: 3,
			color: '000000',
			background: 'ffffff',
			bold: true,
			italic: false,
			underline: false
		},
		questionchoices: {
			font: "verdana",
			size: 3,
			color: '000000',
			background: 'ffffff',
			bold: false,
			italic: false,
			underline: false
		}
		welcometext: {
			font: "verdana",
			size: 3,
			color: '000000',
			background: 'ffffff',
			bold: false,
			italic: false,
			underline: false
		}
	};
</script>

<div id="survey_page">

	<span class="survey_title">
		Survey Title
	</span>

	<span class="welcome_text">
		Welcome Text
	</span>

	<div class="question question_multiplechoice" id="preview_question">
	<span class="question_title">
	1. Question Title
	</span>
	<br>
	<div class="question_input">
	<ul style="list-style-type: none;">
	<li>
		<label for="question_1_1">
			<input type="radio" name="answer[1][]" id="question_1_1">
			Choice 1
		</label>
	</li>
	<li>
		<label for="question_1_2">
			<input type="radio" name="answer[1][]" id="question_1_2">
			Choice 2
		</label>
	</li>
	<li>
		<label for="question_1_3">
			<input type="radio" name="answer[1][]" id="question_1_3">
			Choice 3
		</label>
	</li>
	</ul>
	</div>
	</div>

</div>