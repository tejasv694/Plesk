/**
 * JavaScript main class that is going to be used throughout the application.
 * This should be the base class for Application's JavaScript
 */
	var Application = {
		// Collection of functions that will be executed when this script loads
		init: [],
	
		// Define User Interface related functionalities
		Ui: {},
		
		// Define general JavaScript utilities
		Util: {
			isDefined: function(obj) { return (typeof(obj) != 'undefined'); },
			isFunction: function(obj) { return jQuery.isFunction(obj); },
			isObject: function(o) { return (o && typeof o == 'object'); },
			isArray: function(o) { return (isObject(o) && o.constructor == Array); },
			submitPost: function(url, parameters) { Application.Util._submitRequest('post', url, parameters); },
			submitGet: function(url, parameters) { Application.Util._submitRequest('get', url, parameters); },
			_submitRequest:					function(method, url, parameters) {
				var form = $('<form method="' + method + '" action="' + url + '" style="display:none;">' + Application.Util._createSubmitRequestData(parameters) + '</form>');
				form.appendTo(document.body);
				form.get(0).submit();
			},
			_createSubmitRequestData:		function(parameters, namePrefix) {
				if(Application.Util.isArray(parameters)) {
					var temp = '';
					if(!namePrefix) namePrefix = 'array';
					for(var i = 0, j = parameters.length; i < j; ++i) {
						temp += Application.Util._createSubmitRequestData(parameters[i], namePrefix + '[' + i + ']');
					}
					return temp;
				} else if(Application.Util.isObject(parameters)) {
					var temp = '';
					if(!namePrefix) namePrefix = '';
					for(var i in parameters) {
						temp += Application.Util._createSubmitRequestData(parameters[i], (namePrefix == ''? i : (namePrefix + '[' + i + ']')));
					}
					return temp;
				} else {
					if(!namePrefix) namePrefix = 'input';
					return '<input type="hidden" name="' + namePrefix + '" value="' + parameters + '" />';
				}
			},
			// Provides XOR encryption/decryption.
			encrypt: function(s, key) {
				var result = '';
				while (key.length < s.length) {
					key += key;
				}
				for (var i=0; i<s.length; i++) {
					result += String.fromCharCode(key.charCodeAt(i)^s.charCodeAt(i));
				}
				return encodeURIComponent(result);
			},
			decrypt: function(s, key) {
				return Application.Util.encrypt(s, key);
			}
		},
	
		// Define general information
		Info: {
			// Browser sniffing functions
			Browser: {
				_cache: {},
				ie6: function() {
					if(!Application.Util.isDefined(this._cache.ie6))
						this._cache.ie6 = (jQuery.browser.msie && parseFloat(jQuery.browser.version) <= 6);
					return this._cache.ie6;
				}
			}
		},
	
		// Other miscellaneous functionalities
		Misc: {
			/**
			 * Specify document Min width.
			 * The function can only be called once, subsequent call will be ignored.
			 * @param Integer width Minimum document width
			 * @return void Return nothing
			 */
			specifyDocumentMinWidth: function(width) {
				if(Application.Misc._specifyDocumentMinWidth) return;
	
				Application.Misc._specifyDocumentMinWidth = {
					minwidth:width,
					donotresize:false,
					resize: function() {
						if(Application.Misc._specifyDocumentMinWidth.donotresize) {
							Application.Misc._specifyDocumentMinWidth.donotresize = false;
							return;
						}
	
						if($('div#IEM_HTML_Body').width() < this.minwidth) {
							$('div#IEM_HTML_Body').css('width', Application.Misc._specifyDocumentMinWidth.minwidth+'px');
							Application.Misc._specifyDocumentMinWidth.donotresize = true;
						} else $('div#IEM_HTML_Body').css('width', 'auto');
					},
					eventDOMReady: function(event) {
						if($.browser.msie && parseInt($.browser.version) == 6) {
							$(window).resize(Application.Misc._specifyDocumentMinWidth.eventWindowResize);
							Application.Misc._specifyDocumentMinWidth.resize();
						} else $('div#IEM_HTML_Body').css('min-width', Application.Misc._specifyDocumentMinWidth.minwidth+'px');
					},
					eventWindowResize: function(event) { Application.Misc._specifyDocumentMinWidth.resize(); }
				};
	
				Application.init.push(Application.Misc._specifyDocumentMinWidth.eventDOMReady);
			},
	
			/**
			 * Ping the server on an interval basis.
			 * The function can only be called once, subsequent call will be ignored.
			 * @param String url The url to be ping
			 * @param Integer interval The interval server needs to be ping against in seconds
			 * @param Boolean post Specify to use POST method instead of GET (OPTIONAL, default FALSE)
			 * @param Object data Data to be passed to the URL (OPTIONAL, default NOTHING)
			 * @return void Return nothing
			 */
			 setPingServer: function(url, interval, post, data) {
			 	if(Application.Misc._setPingServer) return;
	
			 	Application.Misc._setPingServer = {
			 		interval:interval,
			 		url:url,
			 		post:!!post,
			 		data:(data || {}),
			 		eventDOMReady: function(event) { setInterval("Application.Misc._setPingServer.pingServer();", Application.Misc._setPingServer.interval); },
			 		pingServer: function() {
			 			if(Application.Misc._setPingServer.url == '') return;
			 			if(Application.Misc._setPingServer.post) $.post(Application.Misc._setPingServer.url, data);
			 			else $.get(Application.Misc._setPingServer.url, data);
			 		}
			 	};
	
			 	Application.init.push(Application.Misc._setPingServer.eventDOMReady);
			 }
		},
	
		// Provides a place holder for page specific code
		Page: {},
	
		// Provides a placeholder for modules (so that you can extends the script)
		Modules: {},
	
	
		WYSIWYGEditor: {
			getContent: function() {
				return tinyMCE.activeEditor.getContent();
			},
			setContent: function(content) {
				tinyMCE.activeEditor.setContent(content);
			},
			isWysiwygEditorActive: function() {
				if (typeof(tinyMCE) != 'undefined' && tinyMCE.activeEditor != null) {
					return true;
				}
				return false;
			},
			insertText: function(text) {
				tinyMCE.activeEditor.execCommand('mceInsertContent',false, text);
			}
		},
	
	
		// Function that handle on DOM ready event
		eventDocumentReady: function(event) {
			for(var i = 0, j = Application.init.length; i < j; ++i)
				if(jQuery.isFunction(Application.init[i])) Application.init[i]();
		}
	};
	
	// This will initialize JavaScript main application when document is loaded
	// Requires jQuery to be defined first...
	$(document).one('ready', Application.eventDocumentReady);
/**
 * -----
 */




/**
 * Ui.Table
 */
 	if (!Application.Ui._) Application.Ui._ = {};
 	if (!Application.Ui._.Table) {
 		Application.Ui._.Table = {
 			eventGridRowHover: function() { $(this).toggleClass('GridRowOver'); },
			GridSetup: function() { $('tr.GridRow').hover(Application.Ui._.Table.eventGridRowHover, Application.Ui._.Table.eventGridRowHover); }
 		};
 	}

 	// Queue this to run @ run time
	Application.init.push(Application.Ui._.Table.GridSetup);
/**
 * -----
 */




/**
 * Ui.Menu
 * Defines menu classes and common variables to be shared accross all of menu's classes
 */
	Application.Ui.Menu = {
		currentMenu: null,
		topCurrentMenu: null,
		topCurrentButton: null,
	
		_: {},
	
		closeMenu: function() {
			if(Application.Ui.Menu.currentMenu) {
				$(Application.Ui.Menu.currentMenu).parent().removeClass('over');
				$(Application.Ui.Menu.currentMenu).parent().find('ul').css('display', 'none');
				$('embed, object, select').css('visibility', 'visible');
				Application.Ui.Menu.currentMenu = null;
			}
	
			if (Application.Ui.Menu.topCurrentMenu) {
				$(Application.Ui.Menu.topCurrentMenu).hide();
				$(Application.Ui.Menu.topCurrentButton).removeClass('ActiveButton');
				if(Application.Info.Browser.ie6()) $('select').css('visibility', '');
				Application.Ui.Menu.topCurrentMenu = null;
				Application.Ui.Menu.topCurrentButton = null;
			}
		}
	};
/**
 * -----
 */




/**
 * Ui.Menu.PopDown
 */
	// Worker
	if (!Application.Ui.Menu._) Application.Ui.Menu._ = {};
	if (!Application.Ui.Menu._.PopDown) {
		Application.Ui.Menu._.PopDown = {
			eventDocumentReady: function(event) { Application.Ui.Menu.PopDown('.PopDownMenu'); },
			eventMenuClick: function(event) {
				if(jQuery.isFunction(event.data.onClickStart)) event.data.onClickStart(event, this);
				Application.Ui.Menu.closeMenu();
		
				if(Application.Info.Browser.ie6()) $('select').css('visibility', 'hidden');
		
				var id = this.id.replace(/Button$/, '');
				if(!('#'+id)) return false;
		
				var obj = this;
				offsetTop = 0;
				offsetLeft = 0;
				while(obj) {
					offsetLeft += obj.offsetLeft;
					offsetTop += obj.offsetTop;
					obj = obj.offsetParent;
					if(obj && CurrentStyle(obj, 'position')) {
						var pos = CurrentStyle(obj, 'position');
						if(pos == "absolute" || pos == "relative") {
							break;
						}
					}
				}
				obj = null;
		
				$(this).addClass('ActiveButton');
				var menu = $('#'+id);
				menu.css({	'position': 'absolute',
							'top': (offsetTop + this.offsetHeight - (event.data.topMarginPixel)) +"px",
							'left': (offsetLeft + 2) + "px"});
				menu.addClass('PopDownMenuContainer');
		
				this.blur();
				menu.show();
		
				if(event.data.maxHeight != null) {
					var temp = parseInt(event.data.maxHeight);
					if(temp != 0 && menu.height() > temp) {
						$('.DropDownMenu', menu).css({	height: temp+'px',
														overflow: 'auto'});
					}
				}
		
				if(event.data.minHeight != null) {
					var temp = parseInt(event.data.maxHeight);
					if(temp != 0 && menu.height() < temp) {
						$('.DropDownMenu', menu).css({	height: temp+'px'});
					}
				}
		
				Application.Ui.Menu.topCurrentMenu = menu.get(0);
				Application.Ui.Menu.topCurrentButton = this;
				menu = null;
		
				$(document).one('click', {menuid: id}, Application.Ui.Menu._.PopDown.eventCloseMenu);
		
				event.stopPropagation();
				event.preventDefault();
		
				if(jQuery.isFunction(event.data.onClickEnd)) event.data.onClickEnd(event, this);
			},
			eventCloseMenu: function(event) {
				$('#'+event.data.menuid).hide();
				$(Application.Ui.Menu.topCurrentButton).removeClass('ActiveButton');
				Application.Ui.Menu.topCurrentButton = null;
				if(Application.Info.Browser.ie6()) $('select').css('visibility', '');
			}
		};
	}

	// Interface
	$.extend(Application.Ui.Menu, {
		PopDown: function(selector, params) {		
			var defaultParams = {	maxHeight: null,
									minHeight: null,
									onClickStart: null,
									onClickEnd: null,
									topMarginPixel: -1};
	
			$.extend(defaultParams, params || {});
			$(selector).bind('click', defaultParams, Application.Ui.Menu._.PopDown.eventMenuClick);
		}
	});
	
	// Add to initialization procedure to convert all elements that have "PopDownMenu" as one of their class to be converted
	Application.init.push(Application.Ui.Menu._.PopDown.eventDocumentReady);
/**
 * -----
 */




/**
 * Ui.HelpToolTip
 */
	// Worker
	if (!Application.Ui._) Application.Ui._ = {};
	if (!Application.Ui._.HelpToolTip) {
		Application.Ui._.HelpToolTip = {
			eventDOMReady: function(event) {
				var elm = $('span.HelpToolTip');
				for (var i = 0, j = elm.size(); i < j; ++i) {
					var helpTitle = $('span.HelpToolTip_Title', elm.get(i)).html();
					var helpContents = $('span.HelpToolTip_Contents', elm.get(i)).html();
	
					$(elm.get(i)).bind('mouseover', {'title':helpTitle, 'contents':helpContents}, Application.Ui._.HelpToolTip.eventOnMouseOver);
					$(elm.get(i)).mouseout(Application.Ui._.HelpToolTip.eventOnMouseOut);
				}
			},
	
			eventOnMouseOver: function(event) {
				$('<div class="HelpToolTip_Placeholder" style="display:inline; position: absolute; width: 240px; background-color: #FEFCD5; border: solid 1px #E7E3BE; padding: 10px;">'
					+ '<span class="helpTip"><b>' + event.data.title + '</b></span>'
					+ '<br /><img src="images/1x1.gif" width="1" height="5" />'
					+ '<br /><div style="padding-left: 10px; padding-right: 5px;">' + event.data.contents + '</div>'
					+ '</div>').appendTo(this);
			},
	
			eventOnMouseOut: function(event) { $('div.HelpToolTip_Placeholder', this).remove(); }
		};
	}

	// Interface
	$.extend(Application.Ui, {
		HelpToolTip: function(selector, title, contents) {	
			$(selector).bind('mouseover', {'title':title, 'contents':contents}, Application.Ui._.HelpToolTip.eventOnMouseOver);
			$(selector).mouseout(Application.Ui._.HelpToolTip.eventOnMouseOut);
		}
	});
	
	// Add to initialization procedure to convert all span elements that have "HelpToolTip" as one of their class to be converted
	Application.init.push(Application.Ui._.HelpToolTip.eventDOMReady);
/**
 * -----
 */




/**
 * Ui.CheckboxSelection
 * Defines CheckboxSelection
 */
	// Worker
	if(!Application.Ui._) Application.Ui._ = {};
	Application.Ui._.CheckboxSelection = {
		eventClick: function(event) {
			if($(event.target).is(event.data.parentSelector)) {
				if($(event.target).attr('checked')) {
					$(event.data.childSelector).attr('checked', true);
					if(Application.Util.isFunction(event.data.onSelectAll))
						event.data.onSelectAll();
				} else {
					$(event.data.childSelector).attr('checked', false);
					if(Application.Util.isFunction(event.data.onSelectNone))
						event.data.onSelectNone();
				}
	
	
			} else if($(event.target).is(event.data.childSelector)) {
				var childrens = $(event.data.childSelector);
				var selected = childrens.filter(':checked');
	
				$(event.data.parentSelector).attr('checked', childrens.size() == selected.size());
	
				if(selected.size() == 0) {
					if(Application.Util.isFunction(event.data.onSelectNone))
						event.data.onSelectNone();
					return;
				}
	
				if(childrens.size() == selected.size()) {
					if(Application.Util.isFunction(event.data.onSelectAll))
						event.data.onSelectAll();
					return;
				}
	
				if(Application.Util.isFunction(event.data.onSelectPartial))
					event.data.onSelectPartial();
			}
		}
	};

	// Interface
	Application.Ui.CheckboxSelection = function(container, allSelector, eachSelector, params) {
		var defaultParams = {
			parentSelector: allSelector,
			childSelector: eachSelector,
			onSelectAll: null,
			onSelectNone: null,
			onSelectPartial: null
		};
		$.extend(defaultParams, params || {});
		$(container).bind('click', defaultParams, Application.Ui._.CheckboxSelection.eventClick);
	}
/**
 * -----
 */

 
 
 

/**
 * Ui.Folders
 * Common JavaScript for Folder display and manipulation.
 */
 	Application.Ui.Folders = {
		lang: null,
		
		RemoveFolder: function(fid)
		{
			if (this.lang == null) {
				this.lang = Application.Modules.Language.Get(['folders.php'], ['Folders_DeleteConfirmation']);
			}
			var result = confirm(this.lang["Folders_DeleteConfirmation"]);
			if (!result) {
				return;
			}
			$.ajax({
				cache: false,
				url: 'index.php?Page=Folders&Action=ajax',
				type: 'POST',
				dataType: 'json',
				data:	{AjaxType: 'Remove',
						folder_id: fid},
				success: function(response) {
							Application.Ui.Folders.ReloadTable();
						},
				error:	function(response) {
							alert("An error occurred while performing this operation.");
						}
			});
		},
	
		ToggleFolder: function(id, type) {
			var folder = $('#folder-' + id);
			if ($('ul', folder).css('display') == 'none') {
				Application.Ui.Folders.ExpandFolder(folder, true, type);
			} else {
				Application.Ui.Folders.CollapseFolder(folder, true, type);
			}
	
		},
	
		CollapseFolder: function(folder, remember, folder_type) {
			var params = {
				ajax_type: 'Collapse',
				expanded: 0,
				folder: folder,
				folder_type: folder_type,
				image: 'images/plus.gif',
				label: 'Expand',
				remember: remember
			};
			Application.Ui.Folders.ChangeFolder(params);
		},
	
		ExpandFolder: function(folder, remember, folder_type) {
			var params = {
				ajax_type: 'Expand',
				expanded: 1,
				folder: folder,
				folder_type: folder_type,
				image: 'images/minus.gif',
				label: 'Collapse',
				remember: remember
			};
			Application.Ui.Folders.ChangeFolder(params);
		},
	
		ChangeFolder: function(p) {
			var children = $('ul', p.folder);
			if (p.expanded) {
				children.show();
			} else {
				children.hide();
			}
			$('img.Toggle', p.folder).attr('src', p.image).attr('alt', p.label).attr('expanded', p.expanded);
			if (p.remember) {
				$.ajax({
					cache: false,
					url: 'index.php?Page=Folders&Action=ajax',
					type: 'POST',
					dataType: 'json',
					data: {AjaxType: p.ajax_type, folder_id: (p.folder.attr('id')).match(/\d+$/)[0], folder_type: p.folder_type}
				});
			}
		},
	
		SetFolderVisibility: function(folder) {
			$("li.Folder").each(function(i) {
				var folder = $(this);
				if (folder.attr('expanded') != 0 || $('ul', folder).children().size() == 0) {
					Application.Ui.Folders.ExpandFolder(folder, false);
				} else {
					Application.Ui.Folders.CollapseFolder(folder, false);
				}
			});
		},
	
		ReloadTable: function() {
			var page = window.location.href.match(/index\.php.*$/)[0];
			$('#PlaceholderParent').load(page + ' #PlaceholderSortable', {}, function() {
				$(function() {
					Application.Ui.Folders.CreateSortableList();
				});
			});
		},
	
		SetStyles: function() {
			$('.sort-handle').css('cursor', 'move');
		},
	
		CreateSortableList: function() {
			$('#PlaceholderSortable').NestedSortable({
				accept: 'SortableRow',
				noNestingClass: 'no-nesting',
				notNestableClass: 'not-nestable',
				opacity: 0.8,
				helperclass: 'SortableRowHelper',
				onChange: function(serialized) {
					this.updatingSortables = true;
					if (this.updateTimeout != null) {
						window.clearTimeout(this.updateTimeout);
					}
					$.ajax({
						cache: false,
						url: 'index.php?Page=Folders&Action=ajax',
						type: 'POST',
						dataType: 'json',
						data: serialized[0].hash,
						success: function(response) {
							if (response.status != 'OK') {
								// fail silently
							}
							/*if (document.all && typeof document.body.style.maxHeight == undefined) {
								// IE has problems here - it breaks on sortable lists so for now we just
								// refresh the current page
								window.location.reload();
							}*/
						}
					});
				},
				onStop: function() {
					/*if (document.all && typeof document.body.style.maxHeight == undefined && this.updatingSortables == false) {
						// IE has problems here - it breaks on sortable lists so for now we just
						// refresh the current page
						this.updateTimeout = window.setTimeout(function() { window.location.reload(); }, 100);
					}*/
				},
				autoScroll: true,
				handle: '.sort-handle'
			});
			Application.Ui.Folders.SetFolderVisibility();
			Application.Ui.Folders.SetStyles();
		}
	};
/**
 * -----
 */

/**
 * Modules.Langauge
 * Retrieves language tokens via AJAX.
 */
Application.Modules.Language = {

	tokens: {},

	/**
	 * MergeTokens
	 * Merges a new token hash into the main one (this.tokens).
	 *
	 * @param Hash A hash of the form {token: value}.
	 *
	 * @return Void Does not return anything.
	 */
	MergeTokens: function(new_tokens) {
		$.extend(this.tokens, new_tokens);
	},

	/**
	 * Get
	 * Retrives language token values from files.
	 * If the call is asynchronous, then the tokens can only be accessed at Application.Modules.Language.tokens.
	 *
	 * @param Array files A list of files to look in (e.g. ['language.php', 'folders.php'])
	 * @param Array tokens A list of tokens to get the values off, without the 'LNG_' part.
	 * @param Boolean async Whether to make the AJAX call asynchronous or not.
	 *
	 * @return Object A mapping of tokens and their values. This is only reliably available if the call is synchronous.
	 */
	Get: function(files, tokens, async) {
		if (async == undefined) {
			async = false;
		}
		// Check the cache.
		for (var i=0; i<tokens.length; i++) {
			if (this.tokens[tokens[i]] != undefined) {
				// Remove this token from the request, as we have it cached already.
				tokens.splice(i, 1);
			}
		}
		// Don't perform an ajax request if we already have all the tokens we need.
		if (tokens.length < 1) {
			return this.tokens;
		}
		// Make the ajax call.
		$.ajax({
			async: async,
			cache: true,
			url: 'tokens.php',
			type: 'POST',
			dataType: 'json',
			data:	{'files[]': files,
					'tokens[]': tokens},
			success: function(response) {
						if (response.status && response.status == 'OK') {
							// Merge the new tokens into our cached ones.
							var new_tokens = eval('(' + response.tokens + ')');
							Application.Modules.Language.MergeTokens(new_tokens);
						}
					},
			error:	function(response) {
						alert("Error: " + response.responseText);
					}
		});
		return this.tokens
	}
}

/**
 * -----
 */

/**
 * Modules.SpamCheck
 * This provides a namespace for spam checking to operate within.
 */
Application.Modules.SpamCheck = {
	check_passed: false,
 	form: null
}

/**
 * -----
 */

Application.Modules.TinyMCE = {
	customCleanup: function(type, value) {
		switch (type) {
			case 'insert_to_editor_dom':
				// we should be able to filter [style*="text-align"], but it doesn't work
				var els = $('div, p', value).filter('[style]');

				els.each(function() {
					var current  = $(this);
					var oldStyle = current.attr('style');
					
					// because we can't filter with jQuery above, we have to check with a regex here
					if (!/text-align\s*:[^;]*;/i.test(oldStyle)) {
						return true;
					}
					
					var newStyle = oldStyle.replace(/text-align\s*:[^;]*;/i, '');
					
					// set the new style
					current.attr('align', current.css('text-align'));
					
					// set the element style attribute or remove it
					if ($.trim(newStyle) != '') {
						current.attr('style', newStyle);
					} else {
						current.removeAttr('style');
					}
				});
			break;
		}

		return value;
	}
}



// Tells AJAX what to do with the returned data
var ajaxWhat = "";
var ajaxData = "";
var ajaxButt = null;
var linkWin = null;

function ShowQuickHelp(div, title, desc)
{
	div = document.getElementById(div);
	div.style.display = 'inline';
	div.style.position = 'absolute';
	div.style.width = '185px';
	div.style.backgroundColor = '#FEFCD5';
	div.style.border = 'solid 1px #E7E3BE';
	div.style.padding = '10px';
	div.innerHTML = '<span class=helpTip><b>' + title + '</b></span><br><img src=images/1x1.gif width=1 height=5><br><div style="display:inline; padding-left:10; padding-right:5" class=helpTip>' + desc + '</div>';
}

function ShowHelp(div, title, desc)
{
	div = document.getElementById(div);
	div.style.display = 'inline';
	div.style.position = 'absolute';
	div.style.width = '240px';
	div.style.backgroundColor = '#FEFCD5';
	div.style.border = 'solid 1px #E7E3BE';
	div.style.padding = '10px';
	div.innerHTML = '<span class=helpTip><b>' + title + '</b></span><br><img src=images/1x1.gif width=1 height=5><br><div style="padding-left:10; padding-right:5" class=helpTip>' + desc + '</div>';
}

function HideHelp(div)
{
	div = document.getElementById(div);
	div.style.display = 'none';
}

function doCustomDate(myObj, tab) {
	if (myObj.options[myObj.selectedIndex].value == "Custom") {
		document.getElementById("customDate"+tab).style.display = ""
		document.getElementById("showDate"+tab).style.display = "none"
	} else {
		document.getElementById("customDate"+tab).style.display = "none"
		document.getElementById("showDate"+tab).style.display = ""
	}
}

function inArray(id, arraylist, returnvalue) {
	for (alitem = 0; alitem < arraylist.length; alitem++) {
		val = arraylist[alitem].toString();
		if (id == val) {
			if (returnvalue)
			{
				return alitem;
			}
			return true;
		}
	}

	if (returnvalue)
	{
		return -1;
	}
	return false;
}

function display(RowID) {
	Row = RowID + "_detail";

	var table = document.getElementById(Row);
	var rowCount = table.rows.length;

	for (i = 1; i < rowCount; i++) {
		table.rows[i].style.display = "";
	}

	document.getElementById(RowID + "plus").style.display = "none"
	document.getElementById(RowID + "minus").style.display = ""
}

function hide(RowID) {
	Row = RowID + "_detail";
	var table = document.getElementById(Row);
	var rowCount = table.rows.length;

	for (i = 1; i < rowCount; i++) {
		table.rows[i].style.display = "none";
	}

	document.getElementById(RowID + "plus").style.display = ""
	document.getElementById(RowID + "minus").style.display = "none"
}

function getIFrameDocument(aID){
	// if contentDocument exists, W3C compliant (Mozilla)
	if (document.getElementById(aID).contentDocument){
		return document.getElementById(aID).contentDocument;
	} else {
		// IE
		return document.frames[aID].document;
	}
}

function ShowCustomFields(contentarea, editorname, pagename) {
	/*
		In Firefox the full path to the admin/index.php file needs to be specified otherwise it tries to load /admin/de/index.php. This is because DevEdit runs in an iframe.
	*/
	if (location.href.indexOf('?') != -1) {
		var url_part = location.href.split(/\?/);
		var url_to_indexphp = url_part[0];
	} else {
		var url_to_indexphp = location.href;
	}

	var title = Application.Modules.Language.Get(['language.php'], ['CustomFieldsInsert_Editor'])['CustomFieldsInsert_Editor'];
	var temp = url_to_indexphp + '?Page=ShowCustomFields&EditorName=' + editorname + '&ContentArea=' + contentarea + (pagename? ('&PageName=' + pagename) : '');
	linkWin = $.fn.window.create({
		title:title,
		height:500,
		width:700,
		uri:temp
	});
	linkWin.open();
}

function ShowDynamicContentTag(contentarea, editorname, pagename) {
	/*
		In Firefox the full path to the admin/index.php file needs to be specified otherwise it tries to load /admin/de/index.php. This is because DevEdit runs in an iframe.
	 */
	if (location.href.indexOf('?') != -1) {
		var url_part = location.href.split(/\?/);
		var url_to_indexphp = url_part[0];
	} else {
		var url_to_indexphp = location.href;
	}

var title = Application.Modules.Language.Get(['language.php'], ['DynContentTagsInsert_Editor'])['DynContentTagsInsert_Editor'];
var temp = url_to_indexphp + '?Page=Addons&Addon=dynamiccontenttags&ajax=1&Action=ShowDynamicContentTag&EditorName=' + editorname + '&ContentArea=' + contentarea + (pagename? ('&PageName=' + pagename) : '');
linkWin = $.fn.window.create({
	title:title,
	height:300,
	width:500,
	uri:temp
});
linkWin.open();
}

// Used in text areas to make sure text is inserted into the Text area
function insertAtCursor(myField, myValue) {
	if (document.selection) {
		myField.focus();
		sel = document.selection.createRange();
		sel.text = myValue;
	} else {
		if (myField.selectionStart || myField.selectionStart == '0') {
			var startPos = myField.selectionStart;
			var endPos = myField.selectionEnd;
			myField.value = myField.value.substring(0, startPos)
				+ myValue
				+ myField.value.substring(endPos, myField.value.length);
		} else {
			myField.value += myValue;
		}
	}
}




function InsertLink(placeholder, contentarea, editorname) {
	// set the default for the editor name.
	if (!editorname || editorname == undefined)
	{
		editorname = 'myDevEditControl';
	}

	placeholder = '%%' + placeholder + '%%';
	if (contentarea == 'TextContent' || !UsingWYSIWYG) {
		if (contentarea == 'html') {
			contentarea = editorname + '_html';
		}


		id = document.getElementById(contentarea);
		insertAtCursor(id, placeholder);

		return;
	}

	if (placeholder == '%%unsubscribelink%%')
	{
		placeholder = "<a href='http://%%unsubscribelink%%/'>" + UnsubLinkPlaceholder + "</a>";
	}

	modcheck_regex = new RegExp("%%modifydetails_(.*?)%%", "i");
	modcheck = modcheck_regex.exec(placeholder);

	if (modcheck)
	{
		placeholder = "<a href='http://%%modifydetails_" + modcheck[1] + "%%/'>" + placeholder + "</a>";
	}

	modcheck_regex = new RegExp("%%sendfriend_(.*?)%%", "i");
	modcheck = modcheck_regex.exec(placeholder);

	if (modcheck)
	{
		placeholder = "<a href='http://%%sendfriend_" + modcheck[1] + "%%/'>" + placeholder + "</a>";
	}
	Application.WYSIWYGEditor.insertText(placeholder);
}


function InsertUnsubscribeLink(contentarea, editorname) {
	InsertLink('unsubscribelink', contentarea, editorname);
}

function enableDate_SubscribeDate(formElement, datefield) {
	if (formElement.checked) {
		document.getElementById(datefield).style.display = ""
	} else {
		document.getElementById(datefield).style.display = "none"
	}
}

function ChangeFilterOptionsSubscribeDate(formElement, datefield) {
	if (formElement.selectedIndex == 3) {
		document.getElementById(datefield+"date2").style.display = ""
	} else {
		document.getElementById(datefield+"date2").style.display = "none"
	}
}

var LinkSelectBox = "";
var LinksLoaded = false;

function enable_ClickedLink(formElement, linkfield, linkselect, loadingmessage, chosen_link) {
	LinkSelectBox = linkselect;
	if (formElement.checked) {
		document.getElementById(linkfield).style.display = "";

		if (!LinksLoaded) {
			linkselect = document.getElementById(linkselect);
			linkselect.options.length = 0;
			linkselect.options[0] = new Option(loadingmessage, '-2');
			ajaxWhat = "LoadLinks(" + chosen_link + ")";
			DoCallback('what=importlinks');
		}
	} else {
		document.getElementById(linkfield).style.display = "none";
	}
}

function LoadLinks(linkid) {
	LinksLoaded = true;
	mylinks = new Array();
	eval(ajaxData);
	linkselect = document.getElementById(LinkSelectBox);
	linkselect.options[0] = null;

	for(lnk in mylinks) {
		// we need to do this because eval'ing an array also evals prototype functions etc that go with it.
		// and we use that (above)...
		if (isNaN(lnk)) {
			continue;
		}

		linkselect.options[linkselect.options.length] = new Option(mylinks[lnk], lnk);

		// do we need to preselect a link?
		if (linkid == lnk)
		{
			linkselect.options[linkselect.options.length-1].selected = true;
		}
	}
}

var NewsSelectBox = "";
var NewsLoaded = false;

function enable_OpenedNewsletter(formElement, newsfield, newsselect, loadingmessage, chosen_news) {
	NewsSelectBox = newsselect;
	if (formElement.checked) {
		document.getElementById(newsfield).style.display = "";

		if (!NewsLoaded) {
			newsselect = document.getElementById(newsselect);
			newsselect.options.length = 0;
			newsselect.options[0] = new Option(loadingmessage, '-2');
			ajaxWhat = "LoadNewsletter(" + chosen_news + ")";
			DoCallback('what=importnewsletters');
		}
	} else {
		document.getElementById(newsfield).style.display = "none";
	}
}

function LoadNewsletter(chosen_news) {
	NewsLoaded = true;
	mynews = new Array();
	ajaxData = unescape(ajaxData);
	eval(ajaxData);
	newsselect = document.getElementById(NewsSelectBox);
	newsselect.options[0] = null;

	for(news in mynews) {
		// we need to do this because eval'ing an array also evals prototype functions etc that go with it.
		// and we use that (above)...
		if (isNaN(news)) {
			continue;
		}

		newsselect.options[newsselect.options.length] = new Option(mynews[news], news);

		// do we need to preselect a link?
		if (news == chosen_news)
		{
			newsselect.options[newsselect.options.length-1].selected = true;
		}
	}
}

function switchContentSource(HTMLOrText, Id)
{
	// Toggle the WYSIWYG editor, file upload box, or web file import box
	if(HTMLOrText == 'html')
	{
		var htmlCF = document.getElementById('htmlCF');
		var htmlNLFile = document.getElementById('htmlNLFile');
		var htmlNLImport = document.getElementById('htmlNLImport');
		var newsletterurl = document.getElementById('newsletterurl');

		switch(Id)
		{
			case 1:
			{
				document.getElementById('hct1').checked = true;
				htmlCF.style.display = '';
				htmlNLFile.style.display = 'none';
				htmlNLImport.style.display = 'none';
				break;
			}
			case 2:
			{
				document.getElementById('hct2').checked = true;
				htmlCF.style.display = 'none';
				htmlNLFile.style.display = '';
				htmlNLImport.style.display = 'none';
				break;
			}
			case 3:
			{
				document.getElementById('hct3').checked = true;
				htmlCF.style.display = 'none';
				htmlNLFile.style.display = 'none';
				htmlNLImport.style.display = '';
				newsletterurl.focus();
				newsletterurl.select();
				break;
			}
		}
	}
	else
	{
		var textCF = document.getElementById('textCF');
		var textNLFile = document.getElementById('textNLFile');
		var textNLImport = document.getElementById('textNLImport');
		var newsletterurl = document.getElementById('textnewsletterurl');

		switch(Id)
		{
			case 1:
			{
				document.getElementById('tct1').checked = true;
				textCF.style.display = '';
				textNLFile.style.display = 'none';
				textNLImport.style.display = 'none';
				break;
			}
			case 2:
			{
				document.getElementById('tct2').checked = true;
				textCF.style.display = 'none';
				textNLFile.style.display = '';
				textNLImport.style.display = 'none';
				break;
			}
			case 3:
			{
				document.getElementById('tct3').checked = true;
				textCF.style.display = 'none';
				textNLFile.style.display = 'none';
				textNLImport.style.display = '';
				newsletterurl.focus();
				newsletterurl.select();
				break;
			}
		}
	}
}

function createCookie(name,value,days)
{
	if (days)
	{
		var date = new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
		var expires = "; expires="+date.toGMTString();
	} else {
		var expires = "";
	}
	document.cookie = name+"="+value+expires+"; path=/";
}

/**
 * Gets the value of the specified cookie.
 *
 * name  Name of the desired cookie.
 *
 * Returns a string containing value of specified cookie,
 *   or null if cookie does not exist.
 */
function getCookie(name) {
	var dc = document.cookie;
	var prefix = name + "=";
	var begin = dc.indexOf("; " + prefix);
	if (begin == -1) {
		begin = dc.indexOf(prefix);
		if (begin != 0) return null;
	} else {
		begin += 2;
	}
	var end = document.cookie.indexOf(";", begin);
	if (end == -1) {
		end = dc.length;
	}
	return unescape(dc.substring(begin + prefix.length, end));
}

function ChangePaging(page, formAction, displayName, confMessage) {
	paging = document.getElementById('PerPageDisplay' + displayName);
	pagingId = paging.selectedIndex;
	pagingAmount = paging[pagingId].value;
	if (pagingAmount == 'all') {
		if (!confirm(confMessage)) {
			return false;
		}
	}
	document.location = 'index.php?Page=' + page + '&' + formAction + '&PerPageDisplay' + displayName + '=' + pagingAmount;
}

function toggleAllCheckboxes(check)
{
	formObj = check.form;
	for (var i=0;i < formObj.length; i++) {
		fldObj = formObj.elements[i];
		if (fldObj.type == 'checkbox') {
			fldObj.checked = check.checked;
		}
	}
}

function ImportWebsite(button, description, importtype, newButtonDesc, errorMsg)
{
	check_form = ImportCheck(importtype);
	if (!check_form) {
		return;
	}

	var url = "";
	if (importtype.toLowerCase() == 'text') {
		url = document.getElementById('textnewsletterurl').value;
	} else {
		url = document.getElementById('newsletterurl').value;
	}
	ajaxWhat = "DoImport('website', '" + importtype + "', '" + newButtonDesc + "', '" + errorMsg + "');";
	DoCallback('what=importurl&url=' + url);

	ajaxButt = button;
	button.value = description;
	button.style.width = "170px";
	button.disabled = true;
}

function DoImport(importtype, TextOrHTML, newButtonDesc, errorMsg)
{
	if (ajaxButt) {
		ajaxButt.value = newButtonDesc;
		ajaxButt.style.width = "70px";
		ajaxButt.disabled = false;
		ajaxButt = null;
	}

	if(ajaxData.length == 0) {
		alert(errorMsg);
	} else {
		if (TextOrHTML.toLowerCase() == 'text') {
			switchContentSource('text', 1);
			document.getElementById('TextContent').value = ajaxData;
		} else {
			// Everything was OK
			switchContentSource('html', 1);
			if (typeof(tinyMCE) == 'undefined' || typeof(tinyMCE) == null || (tinyMCE && tinyMCE.activeEditor == null)) {
				document.getElementById('myDevEditControl_html').value = ajaxData;
				return;
			}
			Application.WYSIWYGEditor.setContent(ajaxData);
		}
	}
}

function DoCallback(data)
{
	var url = 'remote.php';

	// branch for native XMLHttpRequest object
	if (window.XMLHttpRequest) {
		req = new XMLHttpRequest();
		req.onreadystatechange = processReqChange;
		req.open('POST', url, true);
		req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		req.send(data);
	// branch for IE/Windows ActiveX version
	} else if (window.ActiveXObject) {
		req = new ActiveXObject('Microsoft.XMLHTTP')
		if (req) {
			req.onreadystatechange = processReqChange;
			req.open('POST', url, true);
			req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			req.send(data);
		}
	}
}

function processReqChange() {
	// only if req shows 'loaded'
	if (req.readyState == 4) {
		// only if 'OK'
		if (req.status == 200) {
			ajaxData = req.responseText;
			eval(ajaxWhat);
		} else {
			alert('There was a problem retrieving the XML data:\n' + req.responseText);
		}
	}
}

function CheckRadio(Id)
{
	return ($('form input[id^=' + Id + ']:radio:checked').size() > 0);
}

function CheckMultiple(name, frm) {
	return ($("input[@name^='" + name + "']:checked", (frm || document)).size() != 0);
}

/**
 * Returns true if the d/m/y is a valid date.
 */
function isValidDate(d, m, y)
{
	date = new Date();
	m = m - 1; // months start at 0
	date.setFullYear(y, m, d);
	return (m == date.getMonth());
}

/**
 * Validates a custom date field. Returns true if it has a valid date or is left empty.
 */
function CheckDate(field)
{
	date_fields = jQuery.map(["dd", "mm", "yy"], function(el, i) { return document.getElementById(field + "[" + el + "]"); } );
	error = false;
	all_blank = true;
	for (i = date_fields.length-1; i >= 0; i--) {
		if (date_fields[i].value == "") {
			error = true
			date_fields[i].focus();
		} else {
			all_blank = false;
		}
	}
	return all_blank || (!error && isValidDate(date_fields[0].value, date_fields[1].value, date_fields[2].value));
}

/**
 * Returns true if str is a (roughly) valid email address.
 */
function isValidEmail(str)
{
	// We use a simple pattern here because the server side check is complex
	// and we don't want to exclude stuff it will accept.
	if(str.indexOf('@') > -1 && str.indexOf('.') > -1) {
		return true;
	}

	return false;
}

var keyStr = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";

function decode64(input) {
	var output = "";
	var chr1, chr2, chr3;
	var enc1, enc2, enc3, enc4;
	var i = 0;

	// remove all characters that are not A-Z, a-z, 0-9, +, /, or =
	input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");

	do {
		enc1 = keyStr.indexOf(input.charAt(i++));
		enc2 = keyStr.indexOf(input.charAt(i++));
		enc3 = keyStr.indexOf(input.charAt(i++));
		enc4 = keyStr.indexOf(input.charAt(i++));

		chr1 = (enc1 << 2) | (enc2 >> 4);
		chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
		chr3 = ((enc3 & 3) << 6) | enc4;

		output = output + String.fromCharCode(chr1);

		if (enc3 != 64) {
			output = output + String.fromCharCode(chr2);
		}

		if (enc4 != 64) {
			output = output + String.fromCharCode(chr3);
		}
	} while (i < input.length);
	return output;
}

/**
 * Convert a single file-input element into a 'multiple' input list
 *
 * Usage:
 *
 *   1. Create a file input element (no name)
 *      eg. <input type="file" id="first_file_element">
 *
 *   2. Create a DIV for the output to be written to
 *      eg. <div id="files_list"></div>
 *
 *   3. Instantiate a MultiSelector object, passing in the DIV and an (optional) maximum number of files
 *      eg. var multi_selector = new MultiSelector( document.getElementById( 'files_list' ), 3 );
 *
 *   4. Add the first element
 *      eg. multi_selector.addElement( document.getElementById( 'first_file_element' ) );
 *
 *   5. That's it.
 *
 *   You might (will) want to play around with the addListRow() method to make the output prettier.
 *
 *   You might also want to change the line
 *       element.name = 'file_' + this.count;
 *   ...to a naming convention that makes more sense to you.
 *
 * Licence:
 *   Use this however/wherever you like, just don't blame me if it breaks anything.
 *
 * Credit:
 *   If you're nice, you'll leave this bit:
 *
 *   Class by Stickman -- http://www.the-stickman.com
 *      with thanks to:
 *      [for Safari fixes]
 *         Luis Torrefranca -- http://www.law.pitt.edu
 *         and
 *         Shawn Parker & John Pennypacker -- http://www.fuzzycoconut.com
 *      [for duplicate name bug]
 *         'neal'
 */
function MultiSelector( list_target, max ) {

	// Where to write the list
	this.list_target = list_target;
	// How many elements?
	this.count = 0;
	// How many elements?
	this.id = 0;
	// Is there a maximum?
	if( max ){
		this.max = max;
	} else {
		this.max = -1;
	};

	/**
	 * Add a new file input element
	 */
	this.addElement = function( element ){

		// Make sure it's a file input element
		if( element.tagName == 'INPUT' && element.type == 'file' ){

			// Element name -- what number am I?
			// element.name = 'file_' + this.id++;
			element.name = 'attachments[]';

			// Add reference to this object
			element.multi_selector = this;

			// What to do when a file is selected
			element.onchange = function(){

				var start_pos = element.value.lastIndexOf("/");

				if (start_pos < 0)
					start_pos = element.value.lastIndexOf("\\");

				var end_pos = element.value.length - 1;

				var file_size = element.value.substring(start_pos, end_pos);

				if (file_size.length > 30)
				{
					alert("This file name is too large and could cause problems in some email clients such as Outlook. Please rename the file to be less than 30 characters and try again.");
					return false;
				}

				// New file input
				var new_element = document.createElement( 'input' );
				new_element.type = 'file';
				new_element.className = "field250";

				// Add new element
				this.parentNode.insertBefore( new_element, this );

				// Apply 'update' to element
				this.multi_selector.addElement( new_element );

				// Update list
				this.multi_selector.addListRow( this );

				// Hide this: we can't use display:none because Safari doesn't like it
				this.style.position = 'absolute';
				this.style.left = '-1000px';

			};
			// If we've reached maximum number, disable input element
			if( this.max != -1 && this.count >= this.max ){
				element.disabled = true;
			};

			// File element counter
			this.count++;
			// Most recent element
			this.current_element = element;

		} else {
			// This can only be applied to file input elements!
			alert( 'Error: not a file input element' );
		};

	};

	/**
	 * Add a new row to the list of files
	 */
	this.addListRow = function( element ){

		// Row div
		var new_row = document.createElement( 'div' );

		// Delete button
		var new_div = document.createElement( 'div' );
		new_div.innerHTML = "&nbsp;&nbsp;"
		new_div.style.display = "inline";

		var new_row_button = document.createElement( 'a' );
		// new_row_button.type = 'button';
		new_row_button.innerHTML = 'Remove';
		new_row_button.href = "javascript:void()";

		// References
		new_row.element = element;

		// Delete function
		new_row_button.onclick= function(){

			// Remove element from form
			this.parentNode.element.parentNode.removeChild( this.parentNode.element );

			// Remove this row from the list
			this.parentNode.parentNode.removeChild( this.parentNode );

			// Decrement counter
			this.parentNode.element.multi_selector.count--;

			// Re-enable input element (if it's disabled)
			this.parentNode.element.multi_selector.current_element.disabled = false;

			// Appease Safari
			//    without it Safari wants to reload the browser window
			//    which nixes your already queued uploads
			return false;
		};

		// Set row value
		new_row.innerHTML = element.value;

		// Add button
		new_row.appendChild( new_div );
		new_row.appendChild( new_row_button );

		// Add it to the list
		this.list_target.appendChild( new_row );

	};

};

// we do this to get around the "click here to activate control" issue in internet explorer
// we don't need to do this with firefox, but at least it will be done consistently across all browsers
// for more info see http://msdn.microsoft.com/library/default.asp?url=/workshop/author/dhtml/overview/activating_activex.asp
function PrintChart(contents) {
	document.write(contents);
}

// used by autoresponders, templates & newsletters.
function previewTemplate(selectedValue) {
	if (isNaN(selectedValue)) {
		document.getElementById("imgPreview").src = "resources/email_templates/" + selectedValue+ "/preview.gif";
	} else {
		if (selectedValue > 0) {
			document.getElementById("imgPreview").src = "resources/user_template_previews/" + selectedValue + "_preview.gif";
		} else {
			document.getElementById("imgPreview").src = "images/nopreview.gif";
		}

		document.getElementById("imgPreview").onerror = function (evt) {
			document.getElementById("imgPreview").src = "images/nopreview.gif";
		}
	}
}

function stripHTML(c)
{
	var BodyContents = /([\s\S]*\<body[^\>]*\>)([\s\S]*)(\<\/body\>[\s\S]*)/i ;
	var h = c.match(BodyContents);
	if (h != null && h[2]) {
		c = h[2];
	}
	c = c.replace(/\/\/--\>/gi, "");
	c = c.replace(/(\n)/gi,"");
	c = c.replace(/(\r)/gi,"");
	c = c.replace(/<br\/?>/gi,"\n");
	c = c.replace(/(<\/h.>|<\/p>|<\/div>)/gi, "$1\n\n");
	c = c.replace(/<[^>]+>/g,"");
	c = c.replace(/&lt;/g,"<");
	c = c.replace(/&gt;/g,">");
	c = c.replace(/&nbsp;/g," ");
	return c;
}

function stripHTMLWithLinks(c) {
	var BodyContents = /([\s\S]*\<body[^\>]*\>)([\s\S]*)(\<\/body\>[\s\S]*)/i ;
	var h = c.match(BodyContents);
	if (h != null && h[2]) {
		c = h[2];
	}
	c = c.replace(/<a\s.*?href\s*=\s*"(.*?)".*?>(.*?)<\/a>/gi, "$2 [$1]");
	c = c.replace(/<a\s.*?href\s*=\s*'(.*?)'.*?>(.*?)<\/a>/gi, "$2 [$1]");
	c = c.replace(/\/\/--\>/gi, "");
	c = c.replace(/(\n)/gi,"");
	c = c.replace(/(\r)/gi,"");
	c = c.replace(/<title[^\>]*\>[\s\S]*\<\/title\>/gi,"");
	c = c.replace(/<br\s*\/\s*>/gi,"\n");
	c = c.replace(/(<\/h.>|<\/p>|<\/div>)/gi, "$1\n\n");
	c = c.replace(/<[^>]+>/g,"");
	c = c.replace(/&lt;/g,"<");
	c = c.replace(/&gt;/g,">");
	c = c.replace(/&nbsp;/g," ");
	return c;
}

/* ???? DEPRECECIATED ????
function grabTextContent(textareaname, editorname) {
	try{
		eval("var editr = " + editorname);
		document.getElementById(textareaname).value = editr.getTextContent();
	}
	catch (error){
		document.getElementById(textareaname).value = stripHTML(document.getElementById(editorname+'_html').value);
	}
}
*/

function grabTextContent(textareaname, editorname) {
	try{
        var strip = '';
		var strip = Application.WYSIWYGEditor.getContent();
		if ( strip.length <= 0) {
			strip = document.getElementById(editorname + '_html').value;
		}
		strip = stripHTMLWithLinks(strip);
		document.getElementById(textareaname).value = strip;
	}
	catch (error){
	}
}


/* Theme Change Javascript */

function getTids() {
	var allTids = new Array;

	// Since document.getElementsByName doesnt return body, td, div tags etc, we need to do this ourselves for I.E
	if (document.all) {
			allElements = document.getElementById('myDevEditControllevel0').contentWindow.document.getElementById('myDevEditControl_frame').contentWindow.document.all;

			x = 0;
			for (i=0; i<allElements.length; i++) {
				if (allElements[i].getAttribute("name") == "tid") {
					allTids[x] = allElements[i];
					x++;
				}
			}
	} else {
		 allTids = myDevEditControl._frame.getElementsByName('tid');
	}
	return allTids;
}

function setDefaultTids(allTids) {

}

function showContentTids() {
	allTids = getTids();
	setDefaultTids(allTids);

	var html = "";

	for (i=0; i<allTids.length; i++) {
		html = html + "<br>" + allTids[i].getAttribute("description") + allTids[i].getAttribute("bgcolor");
	}
	document.getElementById("themeChanger").innerHTML = html;
}

var flag = 0;
var defaultcolors = new Array();

function switchTheme(color) {

	if (color=='null') return false;
	var allTids = new Array;

	allTids = getTids();
	if (allTids.length==0) {
		alert('The file you are editing does support automated color theme changes.');
		return false;
	}

	var allcolors = new Array;

	if ((color=='default') && (flag==1)) {
		for (grd=0; grd<5; grd++) {
			allcolors[grd] = defaultcolors[grd];
		}
	} else {
		var grades = [0, 0.6, 0.25, 0.667, 0.333];

		var basecolor = new Array(HexToR(color), HexToG(color), HexToB(color));
		var onecolor = new Array;
		for (grd=0; grd<5; grd++) {
			for (bsc=0; bsc<3; bsc++) {
				onecolor[bsc] = Math.round(basecolor[bsc]+(255-basecolor[bsc])*grades[grd]);
			}
			allcolors[grd] = '#'+RGB2Hex(onecolor[0], onecolor[1], onecolor[2]);
		}
	}

	var description = '';

	for (i=0; i<allTids.length; i++) {

		description = allTids[i].getAttribute("description");
		if (description.search(/^lightest/)==0)		descgrade = 3;
		else if (description.search(/^light/)==0)	descgrade = 1;
		else if (description.search(/^darkest/)==0) descgrade = 0;
		else if (description.search(/^dark/)==0)	descgrade = 2;
		else if (description.search(/^medium/)==0)	descgrade = 4;
		else continue;

		pt = description.indexOf('.');
		if (pt==-1) {
			if (description.search(/BorderColor$/)>-1) {
				if (flag == 0) defaultcolors[descgrade] = allTids[i].style.borderColor;
				allTids[i].style.borderColor = allcolors[descgrade];
			} else if (description.search(/BorderBottomColor$/)>-1) {
				if (flag == 0) defaultcolors[descgrade] = allTids[i].style.borderBottomColor;
				allTids[i].style.borderColor = allcolors[descgrade];
			} else if (description.search(/BackgroundColor$/)>-1) {
				if (flag == 0) defaultcolors[descgrade] = allTids[i].style.backgroundColor;
				allTids[i].style.backgroundColor = allcolors[descgrade];
			} else if (description.search(/Bgcolor$/)>-1) {
				if (flag == 0) defaultcolors[descgrade] = allTids[i].bgColor;
				allTids[i].bgColor = allcolors[descgrade];
			} else if (description.search(/Color$/)>-1) {
				if (flag == 0) defaultcolors[descgrade] = allTids[i].style.color;
				allTids[i].style.color = allcolors[descgrade];
			}
			continue;
		}

		comm = description.substr(pt);

		eval("if (flag == 0) defaultcolors[descgrade] = allTids[i]"+comm+";");
		eval("allTids[i]"+comm+" = allcolors[descgrade];");

	}

	flag = 1;

}

function HexToR(h) {return parseInt((cutHex(h)).substring(0,2),16)}
function HexToG(h) {return parseInt((cutHex(h)).substring(2,4),16)}
function HexToB(h) {return parseInt((cutHex(h)).substring(4,6),16)}
function cutHex(h) {return (h.charAt(0)=="#") ? h.substring(1,7):h}

hexdig='0123456789ABCDEF';
function Dec2Hex(d) {
	return hexdig.charAt((d-(d%16))/16)+hexdig.charAt(d%16);
}

function RGB2Hex(r,g,b) {
	return Dec2Hex(r)+Dec2Hex(g)+Dec2Hex(b);
}

function Hex2Dec(h) {
	h=h.toUpperCase();
	d=0;
	for (i=0;i<h.length;i++) {
		d=d*16;
		d+=hexdig.indexOf(h.charAt(i));
	}
	return d;
}


/*
	ISSelectReplacement
*/

var ISSelectReplacement = {
	init: function()
	{
		if($) $(function() { ISSelectReplacement.on_load(); });
		else {
			if(window.addEventListener)
				window.addEventListener('load', ISSelectReplacement.on_load, false);
			else
				window.attachEvent('onload', ISSelectReplacement.on_load);
		}
	},

	on_load: function()
	{
		var selects = document.getElementsByTagName('SELECT');
		if(!selects) return false;

		for(var i = 0; i < selects.length; i++)
		{
			var select = selects[i];
			if(!select.multiple || select.className.indexOf('ISSelectReplacement') == -1 || select.className.indexOf('ISSelectAlreadyReplaced') != -1) continue; // Only multiple selects are supported

			ISSelectReplacement.replace_select(selects[i]);
		}
	},
	replace_select: function(element)
	{
		var name = element.name;

		element.style.visibility = 'hidden';

		// Start whipping up our replacement
		var replacement = document.createElement('DIV');
		replacement.className = "ISSelect "+element.className;
		replacement.className += " ISSelectAlreadyReplaced";

		// If the offsetHeight is 0, this select is hidden
		if(element.offsetHeight == 0)
		{
			var clone = element.cloneNode(true);
			clone.style.position = 'absolute';
			clone.style.left = '-10000px';
			clone.style.top = '-10000px';
			clone.style.display = 'block';
			document.body.appendChild(clone);
			offset_height = clone.offsetHeight+"px";
			offset_width = clone.offsetWidth+"px";
			clone.parentNode.removeChild(clone);
		}
		else
		{
			offset_height = element.offsetHeight+"px";
			offset_width = element.offsetWidth+"px";
		}
		var style_offset_width = ISSelectReplacement.get_prop(element, 'width');
		if(style_offset_width && style_offset_width != "auto") offset_width = style_offset_width;
		var style_offset_height = ISSelectReplacement.get_prop(element, 'height');
		if(style_offset_height && style_offset_height != "auto") offset_height = style_offset_height;

		replacement.style.height = offset_height;
		replacement.style.width = offset_width;

		if(!element.id)
		{
			// we might need to be more careful here, in case we conflict with IDs
			element.id = element.name.replace(/\[\]/, "");
		}
		replacement.id = element.id;
		element.id += "_old";

		replacement.select = element;
		replacement.options = element.options;
		replacement.selectedIndex = element.selectedIndex;
		this.select = element;
		this.replacement = replacement;

		if(element.onchange)
		{
			replacement.onclick = function()
			{
				$(this.select).trigger('change');
			}
		}

		if(element.ondblclick)
		{
			replacement.ondblclick = function()
			{
				$(this.select).trigger('dblclick');
			}
		}

		// load new options into replacement
		var innerhtml = '';
		var num = 0;
		for(var i = 0; i < element.childNodes.length; i++)
		{
			if(element.childNodes[i].nodeType == 3) {
				element.removeChild(element.childNodes[i]);
				--i;
				continue;
			}
			if(element.childNodes[i].tagName == "OPTGROUP")
			{
				innerhtml += ISSelectReplacement.add_group(element, element.childNodes[i], num++);
			}
			else if(element.childNodes[i].tagName == "OPTION")
			{
				innerhtml += ISSelectReplacement.add_option(element, element.childNodes[i], num++);
			}
		}
		ISSelectReplacement.render_options(element, replacement, innerhtml);
		
		
		
		/**
		 * Adds select/deselect functionality.
		 */
		
		/*
		;(function($) {
			
			// add a select/deselect all option
			$('<a href="#" class="selectDeselectAll">select/deselect all</a>')
				.css({
					position : 'absolute',
					top      : 5,
					right    : 5
				})
				.appendTo(replacement)
				.bind('click', function() {
					var rows         = $(replacement).find('li:has(> :checkbox)'),
						selectedRows = rows.filter('.SelectedRow');
					
					// if the number of rows is equal to the number of selected rows, all of them
					// are selected so we should deselect all
					if (rows.length == selectedRows.length) {
						rows.filter('.SelectedRow').click();
					// otherwise select all
					} else {
						rows.not('.SelectedRow').click();
					}
					
					return false;
				});
			
			// set relative positioning so that the absolute positioned anchor 
			// will be offset properly
			$([replacement, replacement.parentNode])
				.css({
					position   : 'relative',
					paddingTop : 25
				});
			
		})(jQuery);
		*/
		
		
		
		element.parentNode.insertBefore(replacement, element);
		element.style.display = 'none';
	},

	render_options: function(element, replacement, innerHTML) {
		var newHTML = '<ul>' + innerHTML + '</ul>';
		replacement.innerHTML = newHTML;
	},

	get_prop: function(element, prop)
	{
		if(element.currentStyle)
		{
			return element.currentStyle[prop];
		}
		else if(document.defaultView && document.defaultView.getComputedStyle)
		{
			prop = prop.replace(/([A-Z])/g,"-$1");
			prop = prop.toLowerCase();
			return document.defaultView.getComputedStyle(element, "").getPropertyValue(prop);
		}
	},

	add_group: function(select, group, group_id)
	{
		group_html = '<li class="ISSelectGroup">' +
			'<div>'+group.label+'</div>';

		if(group.childNodes)
		{
			group_html += '<ul>';
			for(var i = 0; i < group.childNodes.length; i++)
			{
				if(!group.childNodes[i].tagName || group.childNodes[i].nodeType == 3) continue;
				group_html += ISSelectReplacement.add_option(select, group.childNodes[i], [group_id, i]);
			}
			group_html += '</ul>';
		}

		group_html += '</li>';
		return group_html;
	},

	add_option: function(select, option, id)
	{
		var value, element_class, checked = '';
		if(option.selected)
		{
			element_class = "SelectedRow";
			checked = 'checked="checked"'
		}
		else {
			element_class = '';
		}

		var label = option.innerHTML;
		var whitespace = label.match(/^\s*(&nbsp;)*/);
		if(whitespace[0])
		{
			label = label.replace(/^\s*(&nbsp;)*/, '');
		}
		var disabled = '';
		if(this.select.disabled) {
			var disabled = ' disabled="disabled"';
		}
		html = '<li id="ISSelect'+select.name.replace('[]', '')+'_'+option.value+'" class="'+element_class+'" onselectstart="return false;" style="-moz-user-select: none;" onmouseover="ISSelectReplacement.on_hover(this, \''+id+'\', \'over\');"' +
			'onmouseout=\"ISSelectReplacement.on_hover(this, \''+id+'\', \'out\');" onclick="ISSelectReplacement.on_click(this, \''+id+'\');">' + whitespace[0] +
				'<input type="checkbox" name="ISSelectReplacement_'+select.name+'[]" value="'+option.value+'" '+checked+disabled+'" onclick="ISSelectReplacement.on_click(this, \''+id+'\');" />' +
				label +
			'</li>';
		return html;
	},

	on_hover: function(element, id, action)
	{
		var id = id.split(',');

		// Selected an option group child
		if(id.length == 2)
		{
			var replacement = element.parentNode.parentNode.parentNode.parentNode;
			var option = replacement.select.childNodes[id[0]].childNodes[id[1]];
		}
		else
		{
			var replacement = element.parentNode.parentNode;
			var option = replacement.select.childNodes[id[0]];
		}

		if(action == 'out') {
			if(element.className != "SelectedRow") {
				element.className = "";
			}
			$(option).trigger('mouseout');
		}
		else {
			if(element.className != "SelectedRow") {
				element.className = "ISSelectOptionHover";
			}
			$(option).trigger('mouseover');
		}
	},

	scrollToItem: function(select_name, value)
	{
		var item = 'ISSelect'+select_name.replace('[]', '')+'_'+value;
		if(!document.getElementById(item))
			return;

		var obj = document.getElementById(item);
		var top = obj.offsetTop-4;
		while(obj && obj.tagName != 'DIV')
		{
			obj = obj.parentNode;
			if(obj && obj.tagName == 'DIV') {
				obj.scrollTop = top;
				break;
			}
		}
	},

	on_click: function(element, id)
	{
		if(element.dblclicktimeout)
		{
			return false;
		}
		if(element.tagName == "INPUT")
		{
			var checkbox = element;
			if(checkbox.disabled) {
				return false;
			}
			var element = element.parentNode;
		}
		else
		{
			var checkbox = element.getElementsByTagName('input')[0];
			if(checkbox.disabled) {
				return false;
			}
			checkbox.checked = !checkbox.checked;
		}

		element.dblclicktimeout = setTimeout(function() { element.dblclicktimeout = ''; }, 250);

		var id = id.split(',');
		var replacement = null;
		var option = null;

		// Selected an option group child
		if(id.length == 2)
		{
			replacement = element.parentNode.parentNode.parentNode.parentNode;
			option = replacement.select.childNodes[id[0]].childNodes[id[1]];
		}
		else
		{
			replacement = element.parentNode.parentNode;
			option = replacement.select.childNodes[id[0]];
		}
		option.selected = checkbox.checked;
		replacement.selectedIndex = replacement.select.selectedIndex;

		$(option).triggerHandler('click');

		if(checkbox.checked)
		{
			element.className = "SelectedRow";
		}
		else
		{
			element.className = '';
		}
	}
};

ISSelectReplacement.init();


var REMOTE_parameters;

function REMOTE_admin_table(div,url,todo,type,token,page,column,sort,parameters) {
 AJAX_fetch_populate(div,'remote_stats.php',"get",type,"type=" + type + "&token=" + token + "&DisplayPage=" + page + "&column=" + column + "&sort=" + sort + (parameters ? parameters : '') + (REMOTE_parameters ? REMOTE_parameters : ''));
}

function AJAX_error() {
 alert("I'm sorry there has been an error trying to grab remote data.\n\nPlease try again later");
}

function amChartInited(id) {
  if ($('#loading_indicatorchart').length) { $('#loading_indicatorchart').css('display','none'); }
}
function AJAX_fetch_populate(div,url,method,type,params_string) {
  if ($('#loading_indicator'+type).length) {
    $('#loading_indicator'+type).css('display','block');
  }
  $.ajax({
    type: method,
    url: url,
    data: params_string,
    success: function (msg) {
      div.html(msg);
    },
    complete: function (obj) {
      if ($('#loading_indicator'+type).length) {
        $('#loading_indicator'+type).css('display','none');
      }
    },
    error: AJAX_error
  });
}

/**
 * Prototype String function to include trim()
 * The trim function will strip leading and trailing whitespaces from string
 */
String.prototype.trim = function() { return this.replace(/^\s+|\s+$/g, ''); }

/* Make "Drop Shaddow" effect */
$(document).ready(function()
{
	$('.DropShadow').each(function() {
		var offsetHeight = this.offsetHeight;
		var offsetWidth = this.offsetWidth;
		if(offsetHeight == 0) {
			var clone = this.cloneNode(true);
			clone.style.position = 'absolute';
			clone.style.left = '-10000px';
			clone.style.top = '-10000px';
			clone.style.display = 'block';
			document.body.appendChild(clone);
			offsetHeight = clone.offsetHeight;
			offsetWidth = clone.offsetWidth;
			document.body.removeChild(clone);
		}

		$(this).wrap('<div class="DropShadowContainer"><div class="Shadow1"><div class="Shadow2"><div class="Shadow3"><div class="ItemContainer"></div></div></div></div></div>');
		var container = this.parentNode.parentNode.parentNode.parentNode.parentNode;

		$(container).css('height', offsetHeight+"px");
		$(container).css('position', this.style.position);
		$(container).css('top', this.style.top);
		$(container).css('left', this.style.left);
		$(container).css('display', this.style.display);
		$(container).attr('id', this.id);
		$(this).css('position', 'static');
		$(this).css('display', '');
		$(this).removeClass('DropShadow');
		this.id = '';
	});
});

function CurrentStyle(element, prop) {
	if(element.currentStyle) {
		return element.currentStyle[prop];
	}
	else if(document.defaultView && document.defaultView.getComputedStyle) {
		prop = prop.replace(/([A-Z])/g,"-$1");
		prop = prop.toLowerCase();
		return document.defaultView.getComputedStyle(element, "").getPropertyValue(prop);
	}
}

function LaunchHelp(show, articleid, category) {
	if (show == '0') {
		alert('This link has been disabled by the Administrator.');
		return;
	}
	var id = 'NaN';
	category = category || 15; // 15 is the ViewKB category for EM.
	if (!Application.Util.isDefined(Application_Title)) {
		var toks = Application.Modules.Language.Get(['whitelabel.php'], ['ApplicationTitle'], false);
		var Application_Title = toks['ApplicationTitle'];
	}
	if (Application.Util.isDefined(articleid)) {
		id = parseInt(articleid);
	}
	var help_win = window.open(("http://anonym.to/?http://www.viewkb.com/inlinehelp.php?searchOverride=" + category + "&tplHeader=" + escape(Application_Title) + "&helpid="+ id), "help", "width=650, height=800, left="+(screen.availWidth-700)+", top=100");
}

/* Tab menus */
$(document).ready(function() {
	$('#headerMenu ul li.dropdown > a').dblclick(function(e)
	{
		e.stopPropagation();
		window.location = this.href;
		return false;
	});

	$('#headerMenu ul li.dropdown ul li > a').click(function(e) { Application.Ui.Menu.closeMenu(); });

	$('#headerMenu ul li.dropdown > a').click(function(e)
	{
		var elem = this;
		var closeMenuOnly = $(elem).parent().is('.over');

		Application.Ui.Menu.closeMenu();
		$(elem).parent().removeClass('over');
		$('embed, object').css('visibility', 'visible');
		if(isIE6()) $('select').css('visibility', 'visible');
		if (closeMenuOnly) return false;

		Application.Ui.Menu.topCurrentMenu = $(this).parents('li.dropdown').children().get(1);
		Application.Ui.Menu.topCurrentButton = this;

		offsetTop = offsetLeft = 0;
		var element = elem;
		do
		{
			offsetTop += element.offsetTop || 0;
			offsetLeft += element.offsetLeft || 0;
			element = element.offsetParent;
		} while(element);


		$(elem.parentNode).find('ul').css('visibility', 'hidden');
		if(navigator.userAgent.indexOf('MSIE') != -1) {
			$(elem.parentNode).find('ul').css('display', 'block');
		}
		else {
			$(elem.parentNode).find('ul').css('display', 'table');
		}
		var menuWidth = elem.parentNode.getElementsByTagName('ul')[0].offsetWidth;
		$(elem.parentNode).find('ul').css('width', menuWidth-2+'px');
		if(offsetLeft + menuWidth > $(window).width()) {
			$(elem.parentNode).find('ul').css('position', 'absolute');
			$(elem.parentNode).find('ul').css('left',  (offsetLeft-menuWidth+elem.offsetWidth-3)+'px');
		}
		else if(offsetLeft - menuWidth < $(window).width()) {
			$(elem.parentNode).find('ul').css('position', 'absolute');
			$(elem.parentNode).find('ul').css('left',  offsetLeft+'px');
		}
		$('embed, object').css('visibility', 'hidden');
		if(isIE6()) $('select').css('visibility', 'hidden');

		$(elem.parentNode).find('ul').css('visibility', 'visible');
		$(elem.parentNode).addClass('over');
		$(elem).one('blur', function(event) {
			if(elem.parentNode.overmenu != true)
			{
				$(elem.parentNode).removeClass('over');
				$(elem.parentNode).find('ul').css('display', 'none');
				$('embed, object, select').css('visibility', 'visible');
			}
		});
		$(window).one('blur', function(event) {
			if(elem.parentNode.overmenu != true)
			{
				$(elem.parentNode).removeClass('over');
				$(elem.parentNode).find('ul').css('display', 'none');
				$('embed, object, select').css('visibility', 'visible');
			}
		});
		$(document).one('click', function(event) {
			if(elem.parentNode.overmenu != true)
			{
				$(elem.parentNode).removeClass('over');
				$(elem.parentNode).find('ul').css('display', 'none');
				$('embed, object, select').css('visibility', 'visible');
			}
		});
		return false;
	});
	$('#headerMenu ul li ul li').mouseover(function() {
		this.parentNode.parentNode.overmenu = true;
		this.onmouseout = function(e) { this.parentNode.parentNode.overmenu = false;}
	});
	$('#headerMenu ul li ul li').click(function() {
		$(this.parentNode).hide();
		this.parentNode.parentNode.className = 'dropdown';
	});
});

function isObject(o) { return (o && typeof o == 'object'); }
function isArray(o) { return (isObject(o) && o.constructor == Array); }

// Give multi-select search boxes their mojo
$(document).ready(function() {
	$("input.ISSelectSearch").each(function(i) {
		this.style.color = '#999';
		this.onfocus = function() {
			name = $(this).attr('name');
			if (!search_prompt[name]) {
				search_prompt[name] = this.value;
			}
			this.style.color = '#333';
			if (this.value == Searchbox_Type_Prompt) {
				this.value = '';
			}
		};
		this.onblur = function() {
			if (this.value == '') {
				this.style.color = '#999';
				this.value = Searchbox_Type_Prompt;
			}
		};
	});
});


$(document).ready(function() {
	$('div.ISSelectSearch').each(function(i) {
		// create the search box (with enclosing div)
		var search_div = document.createElement('div');
		search_div.style.clear = "left";
		var search_input = document.createElement('input');
		search_input.type = "text";
		search_input.className = "ISSelectSearch";
		search_input.id = search_input.name = "search_" + this.id;
		search_input.value = Searchbox_Type_Prompt; // from header.tpl
		// make the search box behave as desired
		search_input.style.color = '#999';
		search_input.onfocus = function() {
			search_input.style.color = '';
			if (search_input.value == Searchbox_Type_Prompt) {
				search_input.value = '';
			}
		};
		search_input.onblur = function() {
			if (search_input.value == '') {
				search_input.style.color = '#999';
				search_input.value = Searchbox_Type_Prompt;
			}
		};
		// create the search info tip
		var search_info = document.createElement('div');
		search_info.className = "aside";
		search_info.innerHTML = Searchbox_List_Info; // from header.tpl
		// add search box underneath the ISSelectReplace
		search_div.appendChild(search_input);
		search_div.appendChild(search_info);
		this.parentNode.appendChild(search_div);
	});
	$('input.ISSelectSearch').each(function(i) {
		var box = new Object;
		box.master_options = new Array();
		// the ISSelectSearch <input> should have a name like "search_<selectname>" and "search_" is 7 characters
		box.replacement_name = this.name.substr(7);
		box.select_name = box.replacement_name + '_old';
		// copy original select box's options out to an array
		$('#' + box.select_name + ' option').each(function() {
			box.master_options[box.master_options.length] = {value: this.value, text: this.innerHTML, selected: this.selected};
		});
		var wait;
		$('#' + this.id).keyup(function() {
			box.input = this;
			// add a delay so fast types don't lag themselves out
			if (wait) {
				window.clearTimeout(wait);
			}
			wait = window.setTimeout(function() { doSearch(box); }, 100);
		});
	});
});

function doSearch(box) {
	var added_html = '';
	box.select = document.getElementById(box.select_name);
	box.replacement = document.getElementById(box.replacement_name);
	// remove all options from both the old select box and the new replacement box
	$('#' + box.select_name + ' option').remove();
	$('#' + box.replacement_name + ' li').remove();
	if ($(box.input).val() == '') {
		// add all options back in if the search string is empty
		for (x=0; x<box.master_options.length; x++) {
			var option = {innerHTML: box.master_options[x].text, value: box.master_options[x].value, selected: box.master_options[x].selected};
			box.select.options[box.select.options.length] = new Option(box.master_options[x].text, box.master_options[x].value, false, box.master_options[x].selected);
			added_html += ISSelectReplacement.add_option(box.select, option, x);
		}
		ISSelectReplacement.render_options(box.select, box.replacement, added_html);
		return;
	}
	var search_text = $(box.input).val().toLowerCase().replace(/([\\\.\+\*\?\[\^\]\$\(\)\{\}\=\!\<\>\|\:])/g, "\\$1");
	var search_words = search_text.split(' ');
	var ind = 0;
	for(x=0; x<box.master_options.length; x++) {
		var option_text = box.master_options[x].text.toLowerCase();
		var option_id = box.master_options[x].value;
		option_text = option_text.replace(new RegExp("[^a-z0-9A-Z\s,]", "g"), ' ');
		for (b=0; b<search_words.length; b++) {
			// add options that match the search text
			if (option_text.match(search_words[b])) {
				var option = {innerHTML: box.master_options[x].text, value: box.master_options[x].value, selected: box.master_options[x].selected};
				box.select.options[ind] = new Option(box.master_options[x].text, box.master_options[x].value, false, box.master_options[x].selected);
				added_html += ISSelectReplacement.add_option(box.select, option, ind++);
				break;
			}
		}
	}
	ISSelectReplacement.render_options(box.select, box.replacement, added_html);
}

/**
 * Used by the installer to link to our external support site for
 * database help.
 */
function DBHelp(key){
	bucket = new Object();
	bucket['cpanel'] = 'http://anonym.to/?http://www.interspire.com/support/kb/questions/774/';
	bucket['plesk'] = 'http://anonym.to/?http://www.interspire.com/support/kb/questions/674/';
	bucket['other'] = 'http://anonym.to/?http://www.interspire.com/support/kb/questions/775/';
	window.open(bucket[key]);
}

/**
* Check if the user agent is IE 6
*
* @return bool True if it's IE6, false if it's not
*/
function isIE6()
{
	var browser=navigator.appName;
	var b_version=navigator.appVersion;
	var version=parseFloat(b_version);

	if(browser == 'Microsoft Internet Explorer' && version <= 6) {
		return true;
	}
}

function ValidateCustomFieldForm(NoFieldNameMsg, NoDefaultValueMsg, NoMultiValuesMessage)
{
	// If we're editing a custom field then check its name (FieldName)
	if (document.getElementById('FieldName') != null) {
		if ($('#FieldName').val() == '') {
			alert(NoFieldNameMsg);
			$('#FieldName').focus();
			return false;
		}
	}

	// Make sure there's instructional text
	if ($('#DefaultValue').val() == '') {
		alert(NoDefaultValueMsg);
		$('#DefaultValue').focus();
		return false;
	}

	// If it's a multi-option custom field we'll put the values
	// into key/value pairs to immitate how it used to work
	// Added by Mitch during IEM5 alpha testing

	// Firstly, are we dealing with a multi-option custom field? If so MultiValues will be a valid textarea id
	if (document.getElementById('MultiValues') != null) {

		// We're dealing with a multi-option custom field. Create the hidden form fields and append
		// them to the form with the values represented as key/value pairs

		var Values = $('#MultiValues').val().split('\n');

		if (Values.length > 0 && jQuery.trim($('#MultiValues').val()) != '') {
			for (var i=0; i<Values.length; i++) {
				var val = jQuery.trim(Values[i]);
				if (val != '') {
					var KeyField = document.createElement('INPUT');
					KeyField.type = 'hidden';
					KeyField.name = 'Key[' + i + ']';
					KeyField.value = val;

					var ValueField = document.createElement('INPUT');
					ValueField.type = 'hidden';
					ValueField.name = 'Value[' + i + ']';
					ValueField.value = val;

					// Append both fields to the form
					document.getElementById('cfForm').appendChild(KeyField);
					document.getElementById('cfForm').appendChild(ValueField);
				}
			}
		} else {
			alert(NoMultiValuesMessage);
			$('#MultiValues').focus();
			return false;
		}
	}

	return true;
}

/**
* Switches between tabs instead of using the anchor method which jolts the screen
*/
function ShowTab(T) {
	i = 1;
	while (document.getElementById("tab" + i) != null) {
		try {
			document.getElementById("div" + i).style.display = "none";
			document.getElementById("tab" + i).className = "";
			i++;
		} catch (e) {
			break;
		}
	}

	document.getElementById("div" + T).style.display = "";
	document.getElementById("tab" + T).className = "active";

	if (typeof onShowTab == 'function') {
		onShowTab(T);
	}
}

/**
* Sort multi-value custom fields alphabetically when creating one
*/
function SortMultiValues() {
	var mv = $('#MultiValues').val();
	var vals = mv.split('\n');
	var newvals = '';

	if(mv != '' && vals.length > 1) {
		vals.sort();

		for(var i=0; i < vals.length; i++) {
			if(vals[i] != '') {
				newvals += vals[i] + '\n';
			}
		}

		$('#MultiValues').val(newvals);
	}
	else {
		alert('Please type in at least two values (one per line) to sort.');
		$('#MultiValues').focus();
	}
}

/**
* These functions escape and unescape HTML
*/

function escapeHTML(text) {
	return text
		.replace(/&/g,"&amp;")
		.replace(/</g,"&lt;")
		.replace(/>/g,"&gt;");
}
function unescapeHTML(text) {
	return text
		.replace(/&gt;/g,">")
		.replace(/&lt;/g,"<")
		.replace(/&amp;/g,"&");
}

// Javascript for survey button used in Wysiwig editor
function InsertSurveyLink(TextContent) {
	var
		// define the iModal buttons
		iModalButtons = ''
			+ '<button id=\"tinymce-module-form-imodal-close\" type=\"button\" style=\"float: left;\">Cancel</button>'
			+ '<button id=\"tinymce-module-form-imodal-insert\" type=\"button\" style=\"float: right; font-weight: bold;\" disabled=\"disabled\">Insert Survey Link</button>';
		
	var win = $.fn.window.create({
			title    : 'Link to a Survey',
			width	 : 400,
			height	 : 250,
			uri      : 'index.php?Page=Addons&Addon=surveys&Action=tinymceSurveylist&ajax=1',
			autoOpen : true
		}).buttons(iModalButtons);

		win.jQuery().bind('windowAfterOpen', function() {
			// imodal close button
			$('#tinymce-module-form-imodal-close').bind('click', function() {
				$.fn.window.closeAll();
			});

			// imodal insert button
			$('#tinymce-module-form-imodal-insert').bind('click', function() {
				_insertAndClose($('#tinymce-module-form-list').find(':radio:checked'), TextContent);
			});

			// when a form is double clicked, insert it
			$('#tinymce-module-form-list tr')
				.bind('click', function() {
					$('#tinymce-module-form-imodal-insert').removeAttr('disabled');
				})
				.bind('dblclick', function() {
					_insertAndClose($(this).closest('tr').find(':radio'), TextContent);
				});
		});
}

	/**
	 * Inserts the selected feedback form and closes the iModal, but only
	 * if a form is actually selected for insertion.
	 */
	function _insertAndClose(radio, contentarea)
	{
		var id    = radio.val();
	 	var surveyname = $('input[name=surveyId]:checked + label').text();
		var placeholder = '%%SURVEY_' + id + '_LINK%%';
		
			if (contentarea == 'TextContent' || !UsingWYSIWYG) {		
						id = document.getElementById(contentarea);
	 				insertAtCursor(id, placeholder);	
			} else if (contentarea == 'HtmlContent') {
				var selectedtext =  tinyMCE.activeEditor.selection.getContent();
				if (selectedtext) {
					Application.WYSIWYGEditor.insertText('<a href=\"' + placeholder + '\">' + selectedtext + '</a>');
				} else {
					Application.WYSIWYGEditor.insertText('<a href=\"' + placeholder + '\">Click here to take our survey</a>');
				}
			}
	 	
		// close the window and return
		// close the modal
		$.fn.window.closeAll();
		return;
	}

