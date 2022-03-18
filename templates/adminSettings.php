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
<div id="poststuff" class="wrap">
	<div class="r3bl-admin-title-wrap">
		<div class="dashicons dashicons-admin-settings r3bl-admin-title-icon"></div>
		<h1 class="heading r3bl-admin-title"><? echo get_admin_page_title(); ?></h1>
	</div>

	<? settings_errors(); ?>
	<form method="post" action="options.php" novalidate="novalidate">
		<div class="acf-columns-2">
			<div class="acf-column-1">
				<div id="acf_after_title-sortables" class="meta-box-sortables"></div>
				<? 
				settings_fields( 'r3blcptui_settings' );
				do_settings_sections( 'r3blcptui_settings' );
				?>
			</div>
			<div class="acf-column-2">
				<div id="side-sortables" class="meta-box-sortables">
					<div id="submitdiv" class="postbox ">
						<div class="postbox-header">
							<h2 class="hndle">Actions</h2>
						</div>
						<div class="inside">
        			<div id="major-publishing-actions">
            		<div id="publishing-action">
									<? submit_button(null, 'primary', 'submit', false, null); ?>
								</div>
		            <div class="clear"></div>
    		    </div>
        	</div>
				</div>
			</div>
		</div>
	</form>
</div>