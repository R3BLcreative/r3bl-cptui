(function($) {
	$('#r3blcptui-form').on('submit', function(e){
		e.preventDefault();
		var theform = $(this);
		$('#r3blcptui-submit').prop('disabled', true);

		// Reset fields that previously had errors
		$('.wrap').removeClass('r3blcptui-fld-error');

		var taxs = [];
		$('.repeat-field-group').each(function() {
			var inputs = [];
			$(this).find('input').each(function() {
				var type = $(this).attr('type');
				var id = $(this).attr('id');
				var val = (type == 'text') ? $(this).val() : $(this).prop("checked");
				var input = {[id]: val};
				inputs.push(input);
			});
			taxs.push(inputs);
		});

		var formData = {
			action: 'r3blcptui_validate',
			nonce: $('#_wp_nonce_r3blcptui_validate').val(),
			page: $('#admin_page').val(),
			id: $('#cpt_id').val(),
			title: $('#admin_title').val(),
			slug: $('#cpt_slug').val(),
			singular: $('#cpt_singular').val(),
			plural: $('#cpt_plural').val(),
			position: $('#cpt_position').val(),
			icon: {
				id: $('#cpt_icon_id').val(),
				unicode: $('#cpt_icon_unicode').val(),
				label: $('#cpt_icon_label').val(),
				styles: $('#cpt_icon_styles').val(),
				style: $('#cpt_icon_style').val()
			},
			hierarchical: $('#cpt_hierarchical').prop('checked'),
			search: $('#cpt_search').prop('checked'),
			archive: $('#cpt_archive').prop('checked'),
			public: $('#cpt_public').prop('checked'),
			taxonomies: taxs,
		};

		$.ajax({
			url: r3blcptui_object.ajax_url,
			type: 'post',
			data: formData
		})
		.done(function(r){
			var page = $('#admin_page').val();
			var action = theform.prop('action');
			var redirect = (page == 'edit') ? action : r.data['redirect'];
			window.location.replace(redirect);
			//console.log(r.data['request']);
		})
		.fail(function(r){
			// USER NOTIFICATIONS
			var errTxt = r['responseJSON'].data['txt'];
			var errField = r['responseJSON'].data['field'];
			var notice = '<div class="notice notice-error"><h3>Your submission has errors:</h3><ul class="error-list">';

			errTxt.forEach(txt => {
				notice = notice + '<li>'+txt+'</li>';
				//console.log(txt);
			});
			notice = notice + '</ul></div>';

			$('#r3blcptui-notifications').html(notice);

			// UI NOTIFICATIONS
			errField.forEach(field => {
				$('#'+field).parents('.wrap').addClass('r3blcptui-fld-error');
			});
		})
		.always(function(r){
			$('body, html').animate({scrollTop:0}, 'fast');
			$('#r3blcptui-submit').prop('disabled', false);
		});
	});

	// VALIDATE CPT & TAX SLUG INLINE
	$('#r3blcptui-form').on('blur', '.validate-slug', function(e){
		var slugData = {
			action: 'r3blcptui_validate_inline',
			nonce: $('#_wp_nonce_r3blcptui_validate_inline').val(),
			type: $(this).data('type'),
			validate: $(this).data('validate'),
			slug: $(this).val(),
			page: $('#admin_page').val()
		};

		var thisFld = $(this);

		$.ajax({
			url: r3blcptui_object.ajax_url,
			type: 'post',
			data: slugData
		})
		.done(function(r){
			thisFld.parents('.wrap').removeClass('r3blcptui-fld-error');
			thisFld.siblings('.slug-notifications').removeClass('notice notice-error');
			thisFld.siblings('.slug-notifications').html('');
		})
		.fail(function(r){
			var errs = r['responseJSON'].data;
			var notice = '';
			errs.forEach(txt => {
				notice = notice + txt + '</br>';
				//console.log(txt);
			});
			thisFld.parents('.wrap').addClass('r3blcptui-fld-error');
			thisFld.siblings('.slug-notifications').addClass('notice notice-error');
			thisFld.siblings('.slug-notifications').html(notice);
		})
		.always(function(r){
			// Something
		});
	});
}(jQuery));