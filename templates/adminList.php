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
	<div class="dashicons dashicons-list-view r3bl-admin-title-icon"></div>
	<h1 class="heading r3bl-admin-title"><? echo get_admin_page_title(); ?></h1>
	<a href="<? echo admin_url('admin.php'); ?>?page=r3blcptui-add" class="page-title-action r3bl-admin-add-btn">Add New</a>
</div>

<div class="r3blcptui-table-wrap wrap">
	<?
	if($this->wp_list_table->get_counts() > 0) {
		$this->wp_list_table->views();
	}
	?>
	<form id="r3blcptui-list-table" method="post">
  	<input type="hidden" name="page" value="r3blcptui-list" />
		<?
		$this->wp_list_table->prepare_items();
		$this->wp_list_table->display();
		?>
	</form>
</div>