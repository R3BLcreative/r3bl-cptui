(function($) {
	'use strict';

	$.fn.iconPicker = function () {
		return this.each(async function() {

			var r = await getIcons();
			var icons = r.data.release.icons;
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

				var target = $(button.data('target')),
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
						list.append('<div class="icon-picker-icon" data-icon="' + label + '"><a href="#" data-styles="'+styles+'" data-icon="' + id + '" title="' + unicode + '"><i class="'+style+' fa-' + id + '"></i></a></div>');
					}
				}

				$('a', list).on('click', function(e) {
					e.preventDefault();
					var title = $(this).attr('title');
					var icon = $(this).data('icon');
					target.val('\\' + title);
					preview
						.prop('class', 'far')
						.addClass('fa-' + icon);
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

				$('input', control).on('keyup', function(e) {
					var search = $(this).val();
					if(search === '') {
						$('div:lt(30)', list).show();
					}else{
						$('div', list).each(function() {
							if($(this).data('icon').toLowerCase().indexOf(search.toLowerCase()) !== -1) {
								$(this).show();
							}else{
								$(this).hide();
							}
						});
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

			async function getIcons() {
				var access = await getToken();
				var token = access.access_token;

				return $.ajax({
					url: 'https://api.fontawesome.com',
					headers: {"Authorization": "Bearer " + token},
					contentType: 'application/json',
					dataType: 'json',
					data: {
						"query": "query{release(version: \"5.15.4\"){icons{id label styles unicode}}}"
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
