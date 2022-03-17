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
	<div class="dashicons dashicons-admin-settings r3bl-admin-title-icon"></div>
	<h1 class="heading r3bl-admin-title"><? echo get_admin_page_title(); ?></h1>
	<hr class="wp-header-end">
</div>

<div class="r3blcptui-table-wrap wrap">
	<? settings_errors(); ?>
	<form method="POST" action="options.php">
		<? 
		settings_fields( 'r3blcptui_settings' );
		do_settings_sections( 'r3blcptui_settings' );
		submit_button();
		?>
	</form>
</div>