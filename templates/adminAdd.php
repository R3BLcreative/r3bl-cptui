<?
/**
 * @package r3blcptui
 * 
 * Plugin Name: R3BL CPT UI
 * Plugin URI: https://r3blcreative.com
 * Description: Enables a quick and easy UI that creates custom post types.
 * Version: 1.0.0
 * Author: James Cook
 * Author URI: https://r3blcreative.com
 * License: GPL2
 * Text Domain: r3blcptui
 * 
 * Copyright 2018  James Cook  (email : jcook@r3blcreative.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */
?>

<div class="r3bl-admin-title-wrap wrap">
	<div class="dashicons dashicons-plus-alt r3bl-admin-title-icon"></div>
	<h1 class="heading r3bl-admin-title"><? echo get_admin_page_title(); ?></h1>
	<hr class="wp-header-end">
</div>

<p class="r3bl-admin-intro-text">Fill out the required form fields <em>(designated with an * asterisk)</em> below to create a quick and easy <em>"custom post type"</em> that will be immediately accessible in the WordPress Admin menu. Take it one more step and control advanced features like adding custom <em>"taxonomies"</em> and/or turning on some visibility and access settings. <strong>HAPPY CODING!!!</strong></p>

<form id="r3blcptui-form" class="boilerform" method="POST" action="<? echo $_SERVER['REQUEST_URI']; ?>">
	<input type="hidden" id="admin_page" name="admin_page" value="add" />
	<? wp_nonce_field('r3blcptui_validate', '_wp_nonce_r3blcptui_validate'); ?>
	<? wp_nonce_field('r3blcptui_validate_inline', '_wp_nonce_r3blcptui_validate_inline'); ?>
	<div id="r3blcptui-notifications" class="user-notifications"></div>

	<div class="c-admin">

		<!-- \\\\\\\\\\\\\\ TITLE \\\\\\\\\\\\\\\\\ -->
		<div class="wrap c-admin__field c-admin__field--half">
			<label for="admin_title" class="c-label">Admin Title <span>*</span></label>
			<input id="admin_title" class="c-input-field" type="text" name="admin_title" placeholder="CPT" value="<? echo $item['title']; ?>" />
			<p class="c-instructions">
				The reference title for display in the WordPress Admin.
			</p>
		</div>

		<!-- \\\\\\\\\\\\\\ SLUG \\\\\\\\\\\\\\\\\ -->
		<div class="wrap c-admin__field c-admin__field--half">
			<label for="cpt_slug" class="c-label">Slug <span>*</span></label>
			<input id="cpt_slug" class="c-input-field validate-slug" type="text" name="cpt_slug" placeholder="cpt-slug" value="<? echo $item['slug']; ?>" data-type="cpt" />
			<div class="slug-notifications"></div>
			<p class="c-instructions">
				The unique CPT reference key for registering this CPT. Should start with a letter and only contain letters, numbers, dashes, and/or underscores.
			</p>
		</div>
		
		<!-- \\\\\\\\\\\\\\ SINGULAR \\\\\\\\\\\\\\\\\ -->
		<div class="wrap c-admin__field c-admin__field--half">
			<label for="cpt_singular" class="c-label">Singular <span>*</span></label>
			<input id="cpt_singular" class="c-input-field" type="text" name="cpt_singular" placeholder="cpt" value="<? echo $item['singular']; ?>" />
			<p class="c-instructions">
				The singular form of the CPT for creating CPT labels.
			</p>
		</div>
		
		<!-- \\\\\\\\\\\\\\ PLURAL \\\\\\\\\\\\\\\\\ -->
		<div class="wrap c-admin__field c-admin__field--half">
			<label for="cpt_plural" class="c-label">Plural <span>*</span></label>
			<input id="cpt_plural" class="c-input-field" type="text" name="cpt_plural" placeholder="cpts" value="<? echo $item['plural']; ?>" />
			<p class="c-instructions">
				The plural form of the CPT for creating CPT labels.
			</p>
		</div>

		<!-- \\\\\\\\\\\\\\ POSITION \\\\\\\\\\\\\\\\\ -->
		<div class="wrap c-admin__field c-admin__field--half">
			<label for="cpt_position" class="c-label">Menu Position <span>*</span></label>
			<input id="cpt_position" class="c-input-field" type="text" name="cpt_position" placeholder="20" value="<? echo $item['position']; ?>" />
			<p class="c-instructions">
				The admin menu position. Follows the numeric structure outlined in the WP doc <a href="https://developer.wordpress.org/reference/functions/add_menu_page/" target="_blank">here</a>.
			</p>
		</div>

		<!-- \\\\\\\\\\\\\\ ICON \\\\\\\\\\\\\\\\\ -->
		<div class="wrap c-admin__field c-admin__field--half c-admin__field--icon">
			<label for="cpt_icon" class="c-label">Menu Icon <span>*</span></label>
			<div class="c-icon">
				<? if(!empty($item['icon'])) {$icon = json_decode($item['icon'], true);} ?>

				<!-- BUTTON  -->
				<button class="button iconPicker c-icon-picker-btn c-button c-icon-btn" type="button" data-preview="#cpt_icon_preview" data-target="#cpt_icon"><i class="far fa-search-plus"></i></button>

				<!-- PREVIEW -->
				<i id="cpt_icon_preview" class="<? echo $icon['style']; ?>"></i>

				<!-- ID -->
				<input type="hidden" id="cpt_icon_id" name="cpt_icon_id" value="<? echo $icon['id']; ?>" />

				<!-- UNICODE -->
				<input type="hidden" id="cpt_icon_unicode" name="cpt_icon_unicode" value="<? echo $icon['unicode']; ?>" />

				<!-- LABEL -->
				<input type="hidden" id="cpt_icon_label" name="cpt_icon_label" value="<? echo $icon['label']; ?>" />

				<!-- STYLES -->
				<input type="hidden" id="cpt_icon_styles" name="cpt_icon_styles" value="<? echo $icon['styles']; ?>" />

				<!-- STYLE -->
				<input type="hidden" id="cpt_icon_style" name="cpt_icon_style" value="<? echo $icon['style']; ?>" />
			</div>
			<p class="c-instructions">
				The admin menu icon. Use Font Awesome icons via the search button.
			</p>
		</div>

		<!-- \\\\\\\\\\\\\\ HIERARCHICAL \\\\\\\\\\\\\\\\\ -->
		<div class="wrap c-admin__field c-admin__field--qrtr">
			<p class="c-instructions">Do you want this CPT to have parent/child relationships?</p>
			<div class="c-check-field">
				<input id="cpt_hierarchical" class="c-check-field__input" type="checkbox" name="cpt_hierarchical" <? echo $this->checked($item['hierarchical']); ?> />
				<label for="cpt_hierarchical" class="c-check-field__decor" aria-hidden="true" role="presentation"></label>
				<label for="cpt_hierarchical" class="c-check-field__label">Hierarchical</label>
			</div>
		</div>
		
		<!-- \\\\\\\\\\\\\\ SEARCH \\\\\\\\\\\\\\\\\ -->
		<div class="wrap c-admin__field c-admin__field--qrtr">
			<p class="c-instructions">Do you want this CPT to show up in the frontend search results?</p>
			<div class="c-check-field">
				<input id="cpt_search" class="c-check-field__input" type="checkbox" name="cpt_search" <? echo $this->checked($item['search']); ?> />
				<label for="cpt_search" class="c-check-field__decor" aria-hidden="true" role="presentation"></label>
				<label for="cpt_search" class="c-check-field__label">Searchable</label>
			</div>
		</div>

		<!-- \\\\\\\\\\\\\\ ARCHIVE \\\\\\\\\\\\\\\\\ -->
		<div class="wrap c-admin__field c-admin__field--qrtr">
			<p class="c-instructions">Does this CPT need to have a public archive page? Uses slug as the permalink.</p>
			<div class="c-check-field">
				<input id="cpt_archive" class="c-check-field__input" type="checkbox" name="cpt_archive" <? echo $this->checked($item['archive']); ?> />
				<label for="cpt_archive" class="c-check-field__decor" aria-hidden="true" role="presentation"></label>
				<label for="cpt_archive" class="c-check-field__label">Has Archive</label>
			</div>
		</div>
		
		<!-- \\\\\\\\\\\\\\ PUBLIC \\\\\\\\\\\\\\\\\ -->
		<div class="wrap c-admin__field c-admin__field--qrtr">
			<p class="c-instructions">Is this a public CPT that can be viewed on the frontend?</p>
			<div class="c-check-field">
				<input id="cpt_public" class="c-check-field__input" type="checkbox" name="cpt_public" <? echo $this->checked($item['public']); ?> />
				<label for="cpt_public" class="c-check-field__decor" aria-hidden="true" role="presentation"></label>
				<label for="cpt_public" class="c-check-field__label">Public</label>
			</div>
		</div>

		<!-- \\\\\\\\\\\\\\ TAXONOMIES \\\\\\\\\\\\\\\\\ -->
		<div class="c-admin__field c-admin__field--full c-admin__field--repeat taxonomies">
			<label class="c-label">Taxonomies:</label>
			<p class="c-instructions">
				Taxonomies associated with this CPT. Uses slug as the permalink.
			</p>
			<button class="repeat-add c-button c-icon-btn"><span class="dashicons dashicons-plus-alt"></span> Add Taxonomy</button>
			<div class="repeat-taxs"></div>
		</div>

		<div class="c-admin__button">
			<button id="r3blcptui-submit" class="c-button" type="submit">Add CPT</button>
		</div>
	</div>
</form>

<!-- \\\\\\\\\\\\\\ REPEATER TEMPLATE \\\\\\\\\\\\\\\\\ -->
<script type="text/template" id="repeat-tax-temp">
<div class="repeat-field-group">
	<div class="row-num">{?}</div>
	<div class="wrap repeat-field slug-max-width">
		<input id="cpt_taxonomies_slug_{?}" class="c-input-field validate-slug" type="text" name="cpt_taxonomies_slug_{?}" placeholder="Slug *" data-type="tax" />
		<div class="slug-notifications"></div>
	</div>

	<div class="wrap repeat-field">
		<input id="cpt_taxonomies_singular_{?}" class="c-input-field" type="text" name="cpt_taxonomies_singular_{?}" placeholder="Singular *" />
	</div>

	<div class="wrap repeat-field">
		<input id="cpt_taxonomies_plural_{?}" class="c-input-field" type="text" name="cpt_taxonomies_plural_{?}" placeholder="Plural *" />
	</div>

	<div class="c-check-field wrap repeat-field">
		<input id="cpt_taxonomies_hier_{?}" class="c-check-field__input" type="checkbox" name="cpt_taxonomies_hier_{?}" />
		<label for="cpt_taxonomies_hier_{?}" class="c-check-field__decor" aria-hidden="true" role="presentation"></label>
		<label for="cpt_taxonomies_hier_{?}" class="c-check-field__label">Hierarchical</label>
	</div>

	<button class="repeat-delete c-button c-icon-btn"><span class="dashicons dashicons-no-alt"></span></button>
</div>
</script>