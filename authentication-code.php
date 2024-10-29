<?php
/*
 * Plugin Name: Authentication Code
 * Plugin URI: https://wordpress.org/plugins/authentication-code/
 * Description: Adds an authentication field to your login form for better security.
 * Version: 1.2.1
 * Author: Mitch
 * Author URI: https://profiles.wordpress.org/lowest
 * Text Domain: authcode
 * Domain Path:
 * Network:
 * License: GPL-2.0+
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
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

if ( ! defined( 'ABSPATH' ) ) exit;

add_action('admin_menu', 'authcode_menu');
function authcode_menu() {
    add_submenu_page(
        'tools.php',
        'Authentication Code',
        'Authentication Code',
        'manage_options',
        'authcode',
        'authcode_page' );
}

add_action('admin_init', 'authcode_register_settings');
function authcode_register_settings() {
    register_setting('authcode_settings', 'authcode_settings', 'authcode_settings_validate');
}

function authcode_settings_validate($options) {
    if(isset($options['code'])) {
        if(!empty($options['code'])) {
			if(strlen($options['code']) > 20) {
				add_settings_error('authcode_settings', 'authcode_code', 'Authentication code cannot be longer than 20 characters.', $type = 'error');
				return false;
			} elseif(strlen($options['code']) < 3) {
				add_settings_error('authcode_settings', 'authcode_code', 'Authentication code cannot be shorter than 3 characters.', $type = 'error');
				return false;
			} else {
				add_settings_error('authcode_settings', 'authcode_code', 'Settings saved.', $type = 'updated');
			}
		} else {
			add_settings_error('authcode_settings', 'authcode_code', 'Authentication code has been disabled.', $type = 'updated');
		}
    }

    return $options;
}

add_action('admin_notices', 'authcode_admin_notices');
function authcode_admin_notices() {
	settings_errors();
}

function authcode_page() {
?>
<div class="wrap">
	<h1>Authentication Code</h1>
	<p><?php echo __('Make sure to write down your authentication code. Leave the field blank to disable.', 'authcode') ?></p>
	<form method="post" action="options.php">
	<?php
    settings_fields( 'authcode_settings' );
    do_settings_sections( __FILE__ );

    $options = get_option( 'authcode_settings' );
	?>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
						<label for="auth_field"><?php echo __('Authentication Code', 'authcode') ?></label>
					</th>
					<td>
						<input type="text" name="authcode_settings[code]" id="auth_field" autocomplete="off" value="<?php echo (isset($options['code']) && $options['code'] != '') ? $options['code'] : ''; ?>" min="3" max="20" />
					</td>
				</tr>
			</tbody>
		</table>
		<?php submit_button(); ?>
	</form>
</div>
<?php
}
function do_authcode() {
	$options = get_option( 'authcode_settings' );

	if(!empty($options['code'])) {
	add_filter( 'login_form', function() {
		printf(
			'<p class="login-authenticate">
				<label for="auth_key">%s</label>
				<input type="text" name="authcode_auth_key" 
						id="authcode_auth_key" class="input" 
						value="" size="20" autocomplete="off" />
			</p>',
			esc_html__( 'Authentication Code', 'authcode' )
		);
	} );

	add_filter( 'authenticate', function( $user ) {
		$options = get_option( 'authcode_settings' );

		$submit_code = filter_input( INPUT_POST, 'authcode_auth_key',
			FILTER_SANITIZE_STRING );
			
		if ( is_wp_error( $user ) ) {
			return $user;
		}

		$is_valid_auth_code = ! empty( $options['code'] ) 
			&& ( $options['code'] === $submit_code );
			
		if( ! $is_valid_auth_code )
			$user = new WP_Error(
				'invalid_auth_code',
				sprintf(
					'<strong>%s</strong>: %s',
					esc_html__( 'ERROR', 'authcode' ),
					esc_html__( 'Authentication code is invalid.', 'authcode' )
				)
			); 

		return $user;

	}, 100 );

	add_action( 'login_head', function() { echo '<style type="text/css">div#login{padding: 4% 0 0;}</style>'; });
	
	}
}

do_authcode();

add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), function($link) {
	return array_merge( $link, array('<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=2VYPRGME8QELC" target="_blank" rel="noopener noreferrer">Donate</a>') );
} );