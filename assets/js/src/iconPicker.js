(function($) {
	'use strict';

	$.fn.iconPicker = function () {
		return this.each(async function() {
			var version = '5.15.4';
			var access = await getToken();
			var token = access.access_token;
			var r = await getIcons(token);
			var icons = r.data.release.icons;
			var allIcons = icons;
			// console.log(icons);

			var button = $(this),
				offsetTop,
				offsetLeft;

			button.on('click.iconPicker', function(e) {
				offsetTop = $(e.currentTarget).offset().top;
				offsetLeft = $(e.currentTarget).offset().left;
				createPopup(button);
			});

			function createPopup(button) {

				var target = button.data('target'),
					preview = $(button.data('preview')),
					popup  = $('<div class="icon-picker-container">' +
						'<div class="icon-picker-control"></div>' +
						'<div class="icon-picker-list"></div>' +
					'</div>').css({
						'top':  offsetTop + 40,
						'left': offsetLeft
					}),
					list = popup.find('.icon-picker-list');

				for(var i in icons) {
					if(icons.hasOwnProperty(i)) {
						var label = icons[i].label;
						var unicode = icons[i].unicode;
						var id = icons[i].id;
						var styles = icons[i].styles;
						var style = (styles == 'brands') ? 'fab' : 'far';
						list.append('<div class="icon-picker-icon" data-icon="'+label+'"><a href="#" data-id="'+id+'" data-unicode="'+unicode+'" data-label="'+label+'" data-styles="'+styles+'" title="'+unicode+'"><i class="'+style+' fa-'+id+'"></i></a></div>');
					}
				}

				$('a', list).on('click', function(e) {
					e.preventDefault();
					var id = $(this).data('id');
					var unicode = $(this).data('unicode');
					var label = $(this).data('label');
					var styles = $(this).data('styles');
					var style = (styles == 'brands') ? 'fab' : 'far';

					$(target+'_id').val(id);
					$(target+'_unicode').val(unicode);
					$(target+'_label').val(label);
					$(target+'_styles').val(styles);
					$(target+'_style').val(style+' fa-'+id);

					preview
						.addClass(style)
						.addClass('fa-'+id);
					removePopup();
				});

				var control = popup.find('.icon-picker-control');

				control.html('<a data-direction="back" href="#">' +
					'<span class="far fa-angle-left"></span></a>' +
					'<input type="text" class="" placeholder="Search" />' +
					'<a data-direction="forward" href="#"><span class="far fa-angle-right"></span></a>'
				);

				$('a', control).on('click', function (e) {
					e.preventDefault();
					if($(this).data('direction') === 'back') {
						$('div:gt(' + (icons.length - 31) + ')', list).prependTo(list);
					}else{
						$('div:lt(30)', list).appendTo(list);
					}
				});

				popup.appendTo('body').show();

				$('input', control).on('keyup', async function(e) {
					var search = $(this).val();
					if(search === '') {
						icons = allIcons;
					}else{
						var r = await searchIcons(token, search);
						icons = r.data.search;
					}

					list.html('');
					for(var i in icons) {
						if(icons.hasOwnProperty(i)) {
							var label = icons[i].label;
							var unicode = icons[i].unicode;
							var id = icons[i].id;
							var styles = icons[i].styles;
							var style = (styles == 'brands') ? 'fab' : 'far';
							list.append('<div class="icon-picker-icon" data-icon="'+label+'"><a href="#" data-id="'+id+'" data-unicode="'+unicode+'" data-label="'+label+'" data-styles="'+styles+'" title="'+unicode+'"><i class="'+style+' fa-'+id+'"></i></a></div>');
						}
					}
				});

				$(document).on('mouseup.iconPicker', function(e) {
					if(!popup.is(e.target) && popup.has(e.target).length === 0) {
						removePopup();
					}
				} );
			}

			function removePopup() {
				$('.icon-picker-container').remove();
				$(document).off('.iconPicker');
			}

			async function searchIcons(token, s) {
				return $.ajax({
					url: 'https://api.fontawesome.com',
					headers: {"Authorization": "Bearer " + token},
					contentType: 'application/json',
					dataType: 'json',
					data: {
						"query": "query{search(version: \""+version+"\", query: \""+s+"\", first: 150){id label styles unicode}}"
					},
				});
			}

			async function getIcons(token) {
				return $.ajax({
					url: 'https://api.fontawesome.com',
					headers: {"Authorization": "Bearer " + token},
					contentType: 'application/json',
					dataType: 'json',
					data: {
						"query": "query{release(version: \""+version+"\"){icons{id label styles unicode}}}"
					},
				});
			}

			function getToken() {
				// Get access token with API Token
				return $.ajax({
					url: 'https://api.fontawesome.com/token',
					type: 'post',
					dataType: 'json',
					headers: {
						"Authorization": "Bearer D16F819E-5DA9-43D5-8586-4275FABF25BA"
					}
				});
			}
		});
	};

	$(function() {
		$('.iconPicker').iconPicker();
	});
}(jQuery));
