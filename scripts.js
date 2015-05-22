jQuery(function($) {

	$("body.tools_page_better-redirect input#redirecting_url").focus();

	$("body.tools_page_better-redirect input#edit_textarea").on('change',function() {
		if ($(this).is(':checked')) $("#htaccess_textarea").removeAttr('readonly');
		else $("#htaccess_textarea").attr('readonly','readonly');
	});

	$("body.tools_page_better-redirect select#dropdown_pages").on('change',function() {
		var redirect_url = $(this).children('option:selected').val();
		$("input#redirect_url").val(redirect_url);
	});

	$("body.tools_page_better-redirect input#add_redirect").click(function() {
		var wp_url = $("input#wp_url").val();
		var url = $("input#redirecting_url").val();
		if (-1 != url.indexOf(wp_url + '/')) {
			url = url.replace(wp_url + '/','^/');
		} else if (-1 == url.indexOf('http'))
			url = '^/' + url;
		var redirect = $("input#redirect_url").val();
		var textarea = $("textarea#htaccess_textarea");
		var line = "\r\n" + 'RedirectMatch 301 "' + url + '" "' + redirect + '"';
		textarea.val(textarea.val() + line);
	});

});