<?php
/*
ZozoEMA Top Bar
Copyright (C) 2015, Danny van Kooten, hi@dannyvankooten.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 1 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/
namespace ZozoEMA\TopBar;

use Exception;
use EMA4WP_ZozoEMA;
use EMA4WP_Debug_Log;
use EMA4WP_ZozoEMA_Subscriber;
use EMA4WP_List_Data_Mapper;

class Bar {
	/**
	 * @var bool
	 */
	private $success = false;

	/**
	 * @var string
	 */
	private $error_type = '';

	/**
	 * @var bool
	 */
	private $submitted = false;

	/**
	 * Add the hooks
	 */
	public function add_hooks() {
		add_action( 'wp', array( $this, 'init' ) );
	}

	/**
	 * Add template related hooks
	 */
	public function add_template_hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'load_assets' ) );
		add_action( 'wp_head', array( $this, 'output_css'), 90 );
		add_action( 'wp_footer', array( $this, 'output_html' ), 1 );
	}

	/**
	 * @return bool
	 */
	public function init() {

		if( ! $this->should_show_bar() ) {
			return false;
		}

		$this->add_template_hooks();
		$this->listen();
		return true;
	}

	/**
	 * Should the bar be shown?
	 *
	 * @return bool
	 */
	public function should_show_bar() {
	    $options = ematb_get_options();

		// don't show if bar is disabled
		if( ! $options['enabled'] ) {
			return false;
		}

		$show_bar = true;

		if (!empty($options['disable_on_pages'])) {
            $disable_on_pages = explode(',', $options['disable_on_pages']);
            $disable_on_pages = array_map('trim', $disable_on_pages);
            $show_bar = !is_page($disable_on_pages);
		}

		if ($options['disable_after_use'] && isset($_COOKIE['ematb_bar_hidden']) && $_COOKIE['ematb_bar_hidden'] === 'used') {
		    $show_bar = false;
        }

		/**
		 * @deprecated 1.1
		 * @use `ematb_show_bar`
		 */
        $show_bar = apply_filters( 'mctp_show_bar', $show_bar );


		/**
		 * @filter `ematb_show_bar`
		 * @expects boolean
		 *
		 * Set to true if the bar should be loaded for this request, false if not.
		 */
		return apply_filters( 'ematb_show_bar', $show_bar );
	}

	/**
	 * Listens for actions to take
	 */
	public function listen() {

		if( ! isset( $_POST['_ematb'] ) || $_POST['_ematb'] != 1 ) {
            return;
		}

		$options = ematb_get_options();
        $this->success = $this->process();

        if( ! empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest' ) {
            $data = array(
                'message' => $this->get_response_message(),
                'success' => $this->success,
                'redirect_url' => $this->success ? $options['redirect']: '',
            );

            wp_send_json( $data );
            exit;
        }

        if( $this->success ) {
            // should we redirect
            $redirect_url = $options['redirect'];
            if( ! empty( $redirect_url ) ) {
                wp_redirect( $redirect_url );
                exit;
            }
        }

	}

	/**
	 * Process a form submission
	 * @return boolean
	 */
	private function process() {
        $options = ematb_get_options();
		$this->submitted = true;
		$log = $this->get_log();

		/** @var EMA4WP_ZozoEMA_Subscriber $subscriber_data */
		$subscriber = null;
		$result = false;

		if( ! $this->validate() ) {

			if( $log ) {
				$log->info( sprintf( 'Top Bar > Submitted with errors: %s', $this->error_type ) );
			}

			return false;
		}

		/**
		 * Filters the list to which ZozoEMA Top Bar subscribes.
		 *
		 * @param string $list_id
		 */
		$zozoema_list_id = apply_filters( 'ematb_zozoema_list', $options['list'] );

		// check if a ZozoEMA list was given
//		if( empty( $zozoema_list_id ) ) {
//			$this->error_type = 'error';
//
//			if( $log ) {
//				$log->warning( 'Top Bar > No ZozoEMA lists were selected' );
//			}
//
//			return false;
//		}

		$email_address = sanitize_text_field( $_POST['email'] );
		$data = array(
			'EMAIL' => $email_address,
		);

		/**
		 * Filters the data received by ZozoEMA Top Bar, before it is further processed.
		 *
		 * @param $data
		 */
		$data = apply_filters( 'ematb_data', $data );

		/** @ignore */
		$data = apply_filters( 'ematb_merge_vars', $data );
		$email_type = apply_filters( 'ematb_email_type', 'html' );

        $replace_interests = true;

        /**
         * Filters whether interests should be replaced or appended to.
         *
         * @param bool $replace_interests
         */
		$replace_interests = apply_filters( 'ematb_replace_interests', $replace_interests );

		$zozoema = new EMA4WP_ZozoEMA();
		if( class_exists( 'EMA4WP_ZozoEMA_Subscriber' ) ) {

			$mapper = new EMA4WP_List_Data_Mapper( $data, array( $zozoema_list_id ) );
			$map = $mapper->map();

			foreach( $map as $list_id => $subscriber ) {
				$subscriber->email_type = $email_type;
				$subscriber->status = $options['double_optin'] ? 'pending' : 'subscribed';

				// TODO: Add IP address.

				/** @ignore (documented elsewhere) */
				$subscriber = apply_filters( 'ema4wp_subscriber_data', $subscriber );

				/**
				 * Filter subscriber data before it is sent to ZozoEMA. Runs only for ZozoEMA Top Bar requests.
				 *
				 * @param EMA4WP_ZozoEMA_Subscriber
				 */
				$subscriber = apply_filters( 'ematb_subscriber_data', $subscriber );

				$result = $zozoema->list_subscribe( $zozoema_list_id, $subscriber->email_address, $subscriber->to_array(), $options['update_existing'], $replace_interests );
				$result = is_object( $result ) && ! empty( $result->uid );
			}

		} else {
			// for BC with ZozoEMA for WordPress 3.x, override $zozoema var
			$zozoema = ema4wp_get_api();
			unset( $data['EMAIL'] );
			$result = $zozoema->subscribe( $zozoema_list_id, $email_address, $data, $email_type, $options['double_optin'], $options['update_existing'], $replace_interests, $options['send_welcome']);
		}

		// return true if success..
		if( $result ) {

			/**
			 * Fires for every successful sign-up using Top Bar.
			 *
			 * @param string $zozoema_list_id
			 * @param string $email
			 * @param array $data
			 */
			do_action( 'ematb_subscribed', $zozoema_list_id, $email_address, $data );

			// log sign-up attempt
			if( $log ) {
				$log->info( sprintf( 'Top Bar > Successfully subscribed %s', $email_address ) );
			}

			return true;
		}

		// An API error occured... Oh noes!
		if( $zozoema->get_error_code() === 214 ) {
			$this->error_type = 'already_subscribed';

			if( $log ) {
				$log->warning( sprintf( 'Top Bar > %s is already subscribed to the selected list(s)', $email_address ) );
			}
		} else {
			$this->error_type = 'error';

			if( $log ) {
				$log->error( sprintf( 'Top Bar > ZozoEMA API error: %s', $zozoema->get_error_message() ) );
			}
		}

		return false;
	}

	/**
	 * Validate the form submission
	 * @return boolean
	 */
	private function validate() {

		// make sure `email_confirm` field is given but not filled (honeypot)
		if( ! isset( $_POST['email_confirm'] ) || '' !== $_POST['email_confirm'] ) {
			$this->error_type = 'spam';
			return false;
		}

		// make sure `_ematb_timestamp` is at least 1.5 seconds ago
		if( empty( $_POST['_ematb_timestamp'] ) || time() < ( intval( $_POST['_ematb_timestamp'] ) + 1.5 ) ) {
			$this->error_type = 'spam';
			return false;
		}

		// don't work for users without JavaScript (since bar is hidden anyway, must be a bot)
		if( isset( $_POST['_ematb_no_js'] ) ) {
			$this->error_type = 'spam';
			return false;
		}

		// simple user agent check
		$user_agent = ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) ? substr( $_SERVER['HTTP_USER_AGENT'], 0, 254 ) : '';
		if( strlen( $user_agent ) < 2 ) {
			$this->error_type = 'spam';
			return false;
		}

		// check if email is given and valid
		if( empty( $_POST['email'] ) || ! is_string( $_POST['email'] ) || ! is_email( $_POST['email'] ) ) {
			$this->error_type = 'invalid_email';
			return false;
		}

		return apply_filters( 'ematb_validate', true );
	}

	/**
	 * Loads the required scripts & styles
	 */
	public function load_assets() {
        $options = ematb_get_options();
		$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		wp_enqueue_style( 'zozoema-top-bar', $this->asset_url( "/css/bar{$min}.css" ), array(), ZOZOEMA_TOP_BAR_VERSION );
		wp_enqueue_script( 'zozoema-top-bar', $this->asset_url( "/js/script{$min}.js" ), array(), ZOZOEMA_TOP_BAR_VERSION, true );

		$bottom = $options['position'] === 'bottom';

		$data = array(
			'cookieLength' => $options['cookie_length'],
			'icons' => array(
				'hide' => ( $bottom ) ? '&#x25BC;' : '&#x25B2;',
				'show' =>  ( $bottom ) ? '&#x25B2;' : '&#x25BC;'
			),
			'position' => $options['position'],
			'state' => array(
                'submitted' => $this->submitted,
                'success' => $this->success,
            ),
		);

		/**
		 * @filter `ematb_bar_config`
		 * @expects array
		 *
		 * Can be used to filter the following values
		 *  - cookieLength: The length of the cookie
		 *  - icons: Array with `hide` and `show` keys. Holds the hide/show icon strings.
		 */
		$data = apply_filters( 'ematb_bar_config', $data );

		wp_localize_script( 'zozoema-top-bar', 'ematb', $data );
	}

	/**
	 * @return string
	 */
	private function get_css_class() {
        $options = ematb_get_options();
		$classes = array( 'ematb' );

		// add class when bar is sticky
		if ($options['sticky']) {
			$classes[] = 'ematb-sticky';
		}

		// add unique css class for position (bottom|top)
		if ( $options['position']) {
			$classes[] = sprintf( 'ematb-position-%s', $options['position']);
		}

		// add class describing size of the bar
		$classes[] = "ematb-{$options['size']}";

		return join( ' ', $classes );
	}

	/**
	 * Output the CSS settings for the bar
	 */
	public function output_css() {
        $options = ematb_get_options();
		$bar_color = $options[ 'color_bar' ];
		$button_color = $options['color_button'];
		$text_color = $options[ 'color_text' ];
		$button_text_color = $options[ 'color_button_text' ];

		echo '<style type="text/css">' . PHP_EOL;

		if( ! empty( $bar_color) ) {
			echo ".ematb-bar, .ematb-response, .ematb-close { background: {$bar_color} !important; }" . PHP_EOL;
		}

		if( ! empty( $text_color ) ) {
			echo ".ematb-bar, .ematb-label, .ematb-close { color: {$text_color} !important; }" . PHP_EOL;
		}

		if( ! empty( $button_color ) ) {
			echo ".ematb-button { background: {$button_color} !important; border-color: {$button_color} !important; }" . PHP_EOL;
			echo ".ematb-email:focus { outline-color: {$button_color} !important; }" . PHP_EOL;
		}

		if( ! empty( $button_text_color ) ) {
			echo ".ematb-button { color: {$button_text_color} !important; }" . PHP_EOL;
		}

		echo '</style>';
	}


	/**
	 * Output the HTML for the opt-in bar
	 */
	public function output_html() {

		$form_action = apply_filters( 'ematb_form_action', null );
        $options = ematb_get_options();
		?>
		<div id="zozoema-top-bar" class="<?php echo $this->get_css_class(); ?>">
			<!-- ZozoEMA Top Bar v<?php echo ZOZOEMA_TOP_BAR_VERSION; ?> - https://wordpress.org/plugins/zozoema-top-bar/ -->
			<div class="ematb-bar" style="display: none">
				<form method="post" <?php if( is_string( $form_action ) ) { printf( 'action="%s"', esc_attr( $form_action ) ); } ?>>
					<?php do_action( 'ematb_before_label' ); ?>
					<label class="ematb-label" for="zozoema-top-bar__email"><?php echo $options[ 'text_bar' ]; ?></label>
					<?php do_action( 'ematb_before_email_field' ); ?>
					<input type="email" name="email" placeholder="<?php echo esc_attr( $options[ 'text_email_placeholder' ] ); ?>" class="ematb-email" required id="zozoema-top-bar__email" />
					<input type="text"  name="email_confirm" placeholder="Confirm your email" value="" autocomplete="off" tabindex="-1" class="ematb-email-confirm" />
					<?php do_action( 'ematb_before_submit_button' ); ?>
					<input type="submit" value="<?php echo esc_attr( $options['text_button'] ); ?>" class="ematb-button" />
					<?php do_action( 'ematb_after_submit_button' ); ?>
					<input type="hidden" name="_ematb" value="1" />
					<input type="hidden" name="_ematb_no_js" value="1" />
					<input type="hidden" name="_ematb_timestamp" value="<?php echo time(); ?>" />
				</form>
				<?php echo $this->get_response_message_html(); ?>
			</div>
			<!-- / ZozoEMA Top Bar -->
		</div>
		<?php
	}

	/**
	 * @return string
	 */
	protected function get_response_message() {

		if( ! $this->submitted ) {
			return '';
		}

        $options = ematb_get_options();

		if( $this->success ) {
			$message = $options['text_subscribed'];
		} else if( $this->error_type === 'already_subscribed' ) {
			$message = $options['text_already_subscribed'];
		} else if( $this->error_type === 'invalid_email' ) {
			$message = $options['text_invalid_email'];
		} else {
			$message = $options['text_error'];
		}

		return $message;
	}

	protected function get_response_message_html() {
        $message = $this->get_response_message();
        if( empty( $message ) ) {
            return '';
        }

        return sprintf( '<div class="ematb-response"><label class="ematb-response-label">%s</label></div>', $message );
    }



	/**
	 * @param $url
	 *
	 * @return string
	 */
	protected function asset_url( $url ) {
		return plugins_url( '/assets' . $url, ZOZOEMA_TOP_BAR_FILE );
	}

	/**
	 * Returns the debug logger or null, if ZozoEMA for WordPress 3.1 is not installed.
	 *
	 * @return EMA4WP_Debug_Log|null
	 */
	protected function get_log() {

		try {
			$log = ema4wp('log');
		} catch( Exception $e ) {
			return null;
		}

		return $log;
	}

}
