(function($) {
  /**
	 * * Taxonomies Repeater Form Elements
	 */
	var container = $('.repeat-taxs');
	var i = $('.repeat-taxs .repeat-field-group').size() + 1;

	// ADD ITEM TRIGGER
	$('.repeat-add').on('click', function(e){
		e.preventDefault();
		var template = $('#repeat-tax-temp').html();
		template = template.replace(/{\?}/g, i); 	// {?} => iterated placeholder
		template = template.replace(/\{[^\?\}]*\}/g, ""); 	// {valuePlaceholder} => ""

		$(template).appendTo(container);
		i++;
		return false;
	});

	// DELETE ITEM TRIGGER
	$('#r3blcptui-form').on('click', '.repeat-delete', function(e){
		e.preventDefault();
		$(this).parents('.repeat-field-group').remove();
		i--;
		var x = 1;
		$('.repeat-field-group').each(function() {
			$(this).find('.row-num').html(x);
			x++;
		});
		return false;
	});

	// COLOR PICKER
	$('#color-picker').spectrum({
		type: "text",
		showPalette: false,
		togglePaletteOnly: true,
		hideAfterPaletteSelect: true,
		showInput: true,
		showInitial: true
	});
}(jQuery));