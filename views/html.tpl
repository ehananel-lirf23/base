<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><{$_site.title|escape}><{if !empty($_site.subtitle) && !is_array($_site.subtitle)}> - <{$_site.subtitle|escape}><{/if}><{if !empty($_site.detail)}> - <{$_site.detail|escape}><{/if}></title>
<meta name="Keywords" content="" />
<meta name="Description" content="" />
<link rel="shortcut icon" href="<{'static/icons/qq.ico'|url}>" />
<link rel="Bookmark" href="<{'static/icons/qq.ico'|url}>" />
<script type="text/javascript" src="<{'static/js/jquery-1.8.3.min.js'|url}>"></script>
<script type="text/javascript">jQuery.noConflict();</script>
<script type="text/javascript" src="<{'static/js/noty/jquery.noty.packaged.min.js'|url}>"></script>
<script type="text/javascript" src="<{'static/js/noty/themes/default.js'|url}>"></script>
<script type="text/javascript" src="<{'static/js/common.js'|url}>"></script>
<script type="text/javascript">
(function($){
	//修复IE6/7，noty遮罩无法显示完全问题
	/*
	$.notyRenderer.createModalFor = function (notification) {
		if ($('.noty_modal').length == 0) {
			var $div = $('<div/>').addClass('noty_modal').data('noty_modal_count', 0).css(notification.options.theme.modal.css).prependTo($('body')).fadeIn('fast');
			var isIE = !!window.ActiveXObject;var isIE6 = isIE && !window.XMLHttpRequest;var isIE8 = isIE && !!document.documentMode && (document.documentMode == 8);var isIE7 = isIE && !isIE6 && !isIE8;
			if (isIE6 || isIE7) { //ie6 | ie7 | ie8 not in standards mode
				$div.css({width:$(document).width(),height:$(document).height(),position:'absolute',zIndex:-1});
				$(window).on('resize',function(){
					$div.is(':visible') && $div.css({width:$(document).width(),height:$(document).height()});
				});
			}
		}
	};*/
})(jQuery);
</script>