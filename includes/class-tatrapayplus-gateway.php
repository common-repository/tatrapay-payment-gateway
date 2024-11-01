<?php

use Tatrapayplus\TatrapayplusApiClient\Model\Address;
use Tatrapayplus\TatrapayplusApiClient\Model\Amount;
use Tatrapayplus\TatrapayplusApiClient\Model\AppearanceLogoRequest;
use Tatrapayplus\TatrapayplusApiClient\Model\AppearanceRequest;
use Tatrapayplus\TatrapayplusApiClient\Model\BankTransfer;
use Tatrapayplus\TatrapayplusApiClient\Model\BasePayment;
use Tatrapayplus\TatrapayplusApiClient\Model\CardDetail;
use Tatrapayplus\TatrapayplusApiClient\Model\CardPayStatusStructure;
use Tatrapayplus\TatrapayplusApiClient\Model\ColorAttribute;
use Tatrapayplus\TatrapayplusApiClient\Model\E2e;
use Tatrapayplus\TatrapayplusApiClient\Model\InitiatePaymentRequest;
use Tatrapayplus\TatrapayplusApiClient\Model\ItemDetail;
use Tatrapayplus\TatrapayplusApiClient\Model\ItemDetailLangUnit;
use Tatrapayplus\TatrapayplusApiClient\Model\Order;
use Tatrapayplus\TatrapayplusApiClient\Model\OrderItem;
use Tatrapayplus\TatrapayplusApiClient\Model\PayLater;
use Tatrapayplus\TatrapayplusApiClient\Model\PaymentIntentStatusResponse;
use Tatrapayplus\TatrapayplusApiClient\Model\PaymentMethod;
use Tatrapayplus\TatrapayplusApiClient\Model\RegisterForComfortPayObj;
use Tatrapayplus\TatrapayplusApiClient\Model\SignedCardIdObj;
use Tatrapayplus\TatrapayplusApiClient\Model\UserData;
use Tatrapayplus\TatrapayplusApiClient\TatraPayPlusService;
use Tatrapayplus\TatrapayplusApiClient\WpCurlClient;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once plugin_dir_path( __DIR__ ) . 'tatrapayplus_api_client/vendor/autoload.php';

class Tatrapayplus_Gateway extends WC_Payment_Gateway {
	/**
	 * Field client_id
	 *
	 * @var string
	 */
	public $client_id = '';

	/**
	 * Field client_secret
	 *
	 * @var string
	 */
	public $client_secret = '';

	/**
	 * Field scopes
	 *
	 * @var string
	 */
	public $scopes = 'TATRAPAYPLUS';

	/**
	 * Field enable_debug_log
	 *
	 * @var string
	 */
	public $enable_debug_log = 'no';

	/**
	 * Field available_payment_methods
	 *
	 * @var array
	 */
	public $available_payment_methods = array();

	/**
	 * Field mode
	 *
	 * @var integer
	 */
	public $mode = 1;

	/**
	 * Field enable_comfort_pay
	 *
	 * @var string
	 */
	public $enable_comfort_pay = 'no';


	public function __construct() {
		$method_description       = __( 'Everything you need for accepting online payments', 'tatrapay-payment-gateway' );
		$method_description_html  = '<div>' . esc_attr( $method_description ) . '</div>';
		$method_description_html .= '<strong>Redirect URI: ' . esc_attr( WC()->api_request_url( 'wc_gateway_tatrapayplus' ) ) . '</strong>';
		$without_paylater_message = __( 'Pay securely through the tatrapay+ payment gateway with a payment card or bank transfer.', 'tatrapay-payment-gateway' );
		$paylater_message         = __( 'Pay securely using the tatrapay+ payment gateway with a payment card, bank transfer or the Na splátkyTB method.', 'tatrapay-payment-gateway' );

		$this->id                 = 'tatrapayplus';
		$this->has_fields         = false;
		$this->method_title       = __( 'tatrapay+', 'tatrapay-payment-gateway' );
		$this->method_description = $method_description_html;
		$this->supports           = array(
			'products',
			'refunds',
		);

		$this->init_form_fields();
		$this->init_settings();
		$this->schedule_payment_status_task();

		$this->update_option( 'supports', $this->supports );
		$this->title                     = $this->get_option( 'title' );
		$this->description               = $this->get_option( 'description' );
		$this->client_id                 = $this->get_option( 'client_id' );
		$this->client_secret             = $this->get_option( 'client_secret' );
		$this->enable_debug_log          = $this->get_option( 'enable_debug_log' );
		$this->available_payment_methods = $this->get_option( 'available_payment_methods', array() );
		$this->mode                      = $this->get_option( 'mode' );
		$this->button_theme              = $this->get_option( 'button_theme' );
		$this->enable_comfort_pay        = $this->get_option( 'enable_comfort_pay', 'no' );
		if ( $this->enable_comfort_pay === 'yes' ) {
			array_push( $this->supports, 'tokenization', 'add_payment_method', );
		}
		if ( empty( $this->description ) ) {
			$is_paylater_supported = $this->is_paylater_available();
			if ( $is_paylater_supported ) {
				$this->description = $paylater_message;
			} else {
				$this->description = $without_paylater_message;
			}
		}

		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			array(
				$this,
				'process_admin_options',
			)
		);
		add_action( 'admin_notices', array( $this, 'display_errors' ) );
		add_filter(
			'woocommerce_generate_image_with_preview_html',
			array(
				$this,
				'generate_image_with_preview_html',
			)
		);
		add_filter(
			'woocommerce_generate_color_guide_html',
			array(
				$this,
				'generate_color_guide_html',
			)
		);
		add_filter(
			'woocommerce_generate_comfort_pay_file_html',
			array(
				$this,
				'generate_comfort_pay_file_html',
			)
		);
		add_action( 'woocommerce_api_wc_gateway_tatrapayplus', array( $this, 'check_response_from_gateway' ) );
	}

	public function init_form_fields() {
		$base_fields = array(
			'title'                => array(
				'title'       => __( 'Title', 'tatrapay-payment-gateway' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'tatrapay-payment-gateway' ),
				'default'     => __( 'tatrapay+', 'tatrapay-payment-gateway' ),
			),
			'mode'                 => array(
				'title'   => __( 'Mode', 'tatrapay-payment-gateway' ),
				'type'    => 'select',
				'options' => array(
					0 => 'Production',
					1 => 'Sandbox',
				),
				'default' => 1,
			),
			'client_id'            => array(
				'title'       => __( 'Client ID', 'tatrapay-payment-gateway' ),
				'type'        => 'password',
				'description' => __( 'Client ID from developers portal https://developer.tatrabanka.sk/', 'tatrapay-payment-gateway' ),
			),
			'client_secret'        => array(
				'title'       => __( 'Client secret', 'tatrapay-payment-gateway' ),
				'type'        => 'password',
				'description' => __( 'Client secret from developers portal https://developer.tatrabanka.sk/', 'tatrapay-payment-gateway' ),
			),
			'enable_comfort_pay'   => array(
				'title'       => __( 'ComfortPay', 'tatrapay-payment-gateway' ),
				'label'       => __( 'Save user payment card during the payment.', 'tatrapay-payment-gateway' ),
				'type'        => 'checkbox',
				'description' => __( 'To set up Comfortpay, it is necessary for the merchant to have concluded a contract on the operation of the Comfortpay service.', 'tatrapay-payment-gateway' ),
				'default'     => 'no',
			),
			'description'          => array(
				'title'       => __( 'Description', 'tatrapay-payment-gateway' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout. Keep empty to use default description.', 'tatrapay-payment-gateway' ),
				'default'     => '',
			),
			'logo'                 => array(
				'title'       => __( 'Company logo', 'tatrapay-payment-gateway' ),
				'description' => __( 'Shown in payment gateway', 'tatrapay-payment-gateway' ),
				'type'        => 'image_with_preview',
			),
			'theme'                => array(
				'title'       => __( 'Default theme', 'tatrapay-payment-gateway' ),
				'description' => __( 'Payment gateway color theme.', 'tatrapay-payment-gateway' ),
				'type'        => 'select',
				'options'     => array(
					'SYSTEM' => 'System',
					'DARK'   => 'Dark',
					'LIGHT'  => 'Light',
				),
			),
			'button_theme'         => array(
				'title'       => __( 'Button theme', 'tatrapay-payment-gateway' ),
				'description' => __( 'This controls the color of PayLater button on product detail and catalog. Button is show for products with a price higher than €100 up to €30,000 (according to current conditions).', 'tatrapay-payment-gateway' ),
				'type'        => 'select',
				'options'     => array(
					'light'    => 'Dark',
					'dark'     => 'Light',
					'disabled' => __( 'Disabled', 'tatrapay-payment-gateway' ),
				),
			),
			'color_guide'          => array(
				'title' => __( 'Color guide', 'tatrapay-payment-gateway' ),
				'type'  => 'color_guide',
			),
			'tint_on_accent_dark'  => array(
				'title' => __( 'Payment button text color - dark mode', 'tatrapay-payment-gateway' ),
				'type'  => 'color',
			),
			'tint_accent_dark'     => array(
				'title' => __( 'Payment button color - dark mode', 'tatrapay-payment-gateway' ),
				'type'  => 'color',
			),
			'surface_accent_dark'  => array(
				'title' => __( 'Action text color - dark mode', 'tatrapay-payment-gateway' ),
				'type'  => 'color',
			),
			'tint_on_accent_light' => array(
				'title' => __( 'Payment button text color - light mode', 'tatrapay-payment-gateway' ),
				'type'  => 'color',
			),

			'tint_accent_light'    => array(
				'title' => __( 'Payment button color - light mode', 'tatrapay-payment-gateway' ),
				'type'  => 'color',
			),
			'surface_accent_light' => array(
				'title' => __( 'Action text color - light mode', 'tatrapay-payment-gateway' ),
				'type'  => 'color',
			),
			'enable_debug_log'     => array(
				'title'       => __( 'Enable debug logs', 'tatrapay-payment-gateway' ),
				'type'        => 'checkbox',
				'description' => __( 'Show response from gateway in order notes.', 'tatrapay-payment-gateway' ),
				'default'     => 'no',
			),
		);
		$this->form_fields = $base_fields;
	}

	/**
	 * Generate Button HTML.
	 *
	 * @access public
	 *
	 * @param mixed $key
	 * @param mixed $data
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function generate_image_with_preview_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults  = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'type'              => 'file',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
		);

		$data         = wp_parse_args( $data, $defaults );
		$data['type'] = 'file';

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?><?php echo $this->get_tooltip_html( $data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
			</th>
			<td class="forminp">
				<fieldset style="display: flex;align-items: center;">
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span>
					</legend>
					<?php if ( $this->get_option( $key ) ) { ?>
						<img style="height: 50px;"
							src="<?php echo esc_attr( $this->get_option( $key ) ); ?>"
							alt="<?php echo wp_kses_post( $data['title'] ); ?>" loading="lazy">
					<?php } ?>
					<input class="input-text regular-input <?php echo esc_attr( $data['class'] ); ?>"
							type="<?php echo esc_attr( $data['type'] ); ?>" name="<?php echo esc_attr( $field_key ); ?>"
							id="<?php echo esc_attr( $field_key ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>"
							value="<?php echo esc_attr( $this->get_option( $key ) ); ?>"
							placeholder="<?php echo esc_attr( $data['placeholder'] ); ?>" <?php disabled( $data['disabled'], true ); ?> <?php echo $this->get_custom_attribute_html( $data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> />
				</fieldset>
				<?php echo $this->get_description_html( $data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}

	public function generate_color_guide_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults  = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'type'              => 'file',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
		);

		$data         = wp_parse_args( $data, $defaults );
		$data['type'] = 'file';

		if ( get_locale() === 'sk_SK' ) {
			$color_guide_img_name = 'color_guide_sk.png';
		} else {
			$color_guide_img_name = 'color_guide_en.png';
		}
		$guide_url  = plugin_dir_url( __DIR__ ) . 'plugin_assets/images/' . $color_guide_img_name . '?v=' . TATRAPAYPLUS_VERSION;
		$guide_text = __( 'Color guide', 'tatrapay-payment-gateway' );
		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<a target="_blank"
					href="<?php echo esc_attr( $guide_url ); ?>"><?php echo wp_kses_post( $guide_text ); ?></a>
			</th>
		</tr>
		<?php

		return ob_get_clean();
	}

	public function generate_comfort_pay_file_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults  = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'type'              => 'file',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
		);

		$data         = wp_parse_args( $data, $defaults );
		$data['type'] = 'file';

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?><?php echo $this->get_tooltip_html( $data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span>
					</legend>
					<?php if ( $this->get_option( $key ) ) { ?>
						<div style="font-weight: bold;">Public key already saved in database.</div><br>
					<?php } ?>
					<input class="input-text regular-input <?php echo esc_attr( $data['class'] ); ?>"
							type="<?php echo esc_attr( $data['type'] ); ?>" name="<?php echo esc_attr( $field_key ); ?>"
							id="<?php echo esc_attr( $field_key ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>"
							value="<?php echo esc_attr( $this->get_option( $key ) ); ?>"
							placeholder="<?php echo esc_attr( $data['placeholder'] ); ?>" <?php disabled( $data['disabled'], true ); ?> <?php echo $this->get_custom_attribute_html( $data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> />
					<?php echo $this->get_description_html( $data );// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}

	public function is_paylater_available() {
		$is_available = false;
		if ( ! ( $this->client_id && $this->client_secret ) ) {
			return $is_available;
		}

		try {
			$available_methods_currencies = $this->get_or_set_cached_available_payment_methods();
		} catch ( Exception $e ) {
			$available_methods_currencies = array();
		}
		if ( WC()->cart && 0 < $this->get_order_total() ) {
			$is_available = TatraPayPlusService::is_currency_supported_for_specific_methods(
				$this->get_order_total(),
				get_woocommerce_currency(),
				$available_methods_currencies,
				array( PaymentMethod::PAY_LATER )
			);
		}

		return $is_available;
	}

	public function is_available() {
		$is_available = parent::is_available();
		if ( ! ( $this->client_id && $this->client_secret ) ) {
			$is_available = false;
		}

		if ( $is_available ) {
			try {
				$available_methods_currencies = $this->get_or_set_cached_available_payment_methods();
			} catch ( Exception $e ) {
				$available_methods_currencies = array();
			}
			if ( WC()->cart && 0 < $this->get_order_total() ) {
				$is_available = TatraPayPlusService::is_currency_supported_for_specific_methods(
					$this->get_order_total(),
					get_woocommerce_currency(),
					$available_methods_currencies,
					array( PaymentMethod::CARD_PAY, PaymentMethod::BANK_TRANSFER, PaymentMethod::PAY_LATER )
				);
			}
		}

		return $is_available;
	}

	public function payment_fields() {
		$description = $this->get_description();
		if ( $description ) {
			// directly from woocommerce https://github.com/woocommerce/woocommerce/blob/9.2.3/plugins/woocommerce/includes/abstracts/abstract-wc-payment-gateway.php#L467 .
			// KSES is ran within get_description, but not here since there may be custom HTML returned by extensions.
			echo wpautop( wptexturize( $description ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		if ( $this->supports( 'add_payment_method' ) && is_checkout() ) {
			$this->saved_payment_methods();
			$this->save_payment_method_checkbox();
		}
	}

	public function saved_payment_methods() {
		$html = '<ul class="woocommerce-SavedPaymentMethods wc-saved-payment-methods" data-count="' . esc_attr( count( $this->get_tokens() ) ) . '">';

		foreach ( $this->get_tokens() as $token ) {
			$html .= $this->get_saved_payment_method_option_html( $token );
		}

		$html .= '</ul>';

		echo apply_filters( 'wc_payment_gateway_form_saved_payment_methods_html', $html, $this ); // @codingStandardsIgnoreLine
	}

	public function save_card_token_from_comfort_pay( CardPayStatusStructure $status_obj ) {
		if ( is_null( $status_obj ) || is_null( $status_obj->getComfortPay() ) ) {
			return;
		}
		if ( is_null( $status_obj->getComfortPay()->getCid() ) ) {
			return;
		}
		$token = new WC_Payment_Token_CC();
		$token->set_token( $status_obj->getComfortPay()->getCid() );
		$token->set_gateway_id( $this->id );
		$token->set_card_type( 'card' );
		$token->set_last4( substr( $status_obj->getMaskedCardNumber(), - 4 ) );
		$token->set_user_id( get_current_user_id() );
		$token->set_expiry_month( '12' );
		$token->set_expiry_year( '2025' );
		$token->save();
	}

	/**
	 * Process refund.
	 *
	 * If the gateway declares 'refunds' support, this will allow it to refund.
	 * a passed in amount.
	 *
	 * @param int        $order_id Order ID.
	 * @param float|null $amount Refund amount.
	 * @param string     $reason Refund reason.
	 *
	 * @return bool|WP_Error True or false based on success, or a WP_Error object.
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order        = wc_get_order( $order_id );
		$access_token = $this->retrieve_access_token();

		$config = Tatrapayplus\TatrapayplusApiClient\Configuration::getDefaultConfiguration( $this->mode )->setAccessToken( $access_token );

		$api_instance = new Tatrapayplus\TatrapayplusApiClient\Api\TatraPayPlusAPIApi( $config, new WpCurlClient( $this->enable_debug_log ) );
		try {
			$data = new Tatrapayplus\TatrapayplusApiClient\Model\CardPayUpdateInstruction(
				array(
					'operation_type' => Tatrapayplus\TatrapayplusApiClient\Model\CardPayUpdateInstruction::OPERATION_TYPE_CHARGEBACK,
					'amount'         => $this->round( $amount, $order ),
				)
			);
			$api_instance->updatePaymentIntent( $order->get_transaction_id(), $data );
		} catch ( Exception $e ) {
			return new WP_Error( 'error', 'Unable to refund, response: ' . $e->getResponseBody() );
		}

		return true;
	}

	/*
	Refund only paid orders with method CARD_PAY
	*/
	public function can_refund_order( $order ) {
		$refund_supported = $order && $this->supports( 'refunds' );
		$order_is_paid    = $order->get_date_paid();
		$is_refundable    = $refund_supported && $order_is_paid;
		if ( $is_refundable ) {
			$access_token  = $this->retrieve_access_token();
			$response      = TatraPayPlusService::check_payment_status( new WpCurlClient( $this->enable_debug_log ), $access_token, $order->get_transaction_id(), $this->mode );
			$is_refundable = $response && $response['object']->getSelectedPaymentMethod() === PaymentMethod::CARD_PAY;
		}

		return $is_refundable;
	}

	/**
	 * Get gateway icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		$icon_html = '';
		$icons     = $this->get_icons( $this->available_payment_methods );
		foreach ( $icons as &$icon ) {
			$icon_html .= '<img style="max-height: 30px; margin-left: 5px; width: auto;" src="' . WC_HTTPS::force_https_url( $icon['src'] ) . '" alt="' . esc_attr( $icon['alt'] ) . '" />';
		}

		return apply_filters( 'woocommerce_gateway_icon', $icon_html, $this->id );
	}

	public static function get_icons( $available_methods_currencies ) {
		if ( WC()->cart ) {
			$total = WC()->cart->total;
		} else {
			$total = 0;
		}
		$icons = TatraPayPlusService::get_icons( get_woocommerce_currency(), $total, $available_methods_currencies );

		foreach ( $icons as &$icon ) {
			$icon['src'] = plugin_dir_url( __DIR__ ) . 'plugin_assets/images/' . $icon['src'] . '?v=' . TATRAPAYPLUS_VERSION;
		}

		return $icons;
	}

	public function process_admin_options() {
		$existing_file_url = $this->get_option( 'logo' );
		$result            = parent::process_admin_options();
		$mode              = $this->get_option( 'mode' );
		$client            = new WpCurlClient( $this->get_option( 'enable_debug_log' ) );
		try {
			$access_token = TatraPayPlusService::retrieve_access_token_with_credentials(
				$client,
				$this->get_option( 'client_id' ),
				$this->get_option( 'client_secret' ),
				$mode,
				$this->scopes,
			);
			$this->get_or_set_cached_available_payment_methods( true );
		} catch ( \Tatrapayplus\TatrapayplusApiClient\ApiException $e ) {
			$access_token = null;
			wc_get_logger()->log( 'warning', print_r( $e, true ) );
			$response_body_json = \Tatrapayplus\TatrapayplusApiClient\Api\TatraPayPlusAPIApi::json_decode( $e->getResponseBody() );
			$this->add_error( __( 'Unable to connect with tatrapay+, please check your credentials.', 'tatrapay-payment-gateway' ) . ' [' . $response_body_json->error . ']' );
		} catch ( Exception $e ) {
			$access_token = null;
			wc_get_logger()->log( 'warning', print_r( $e, true ) );
			$this->add_error( __( 'Unable to connect with tatrapay+, please check your credentials.', 'tatrapay-payment-gateway' ) );
		}

		$this->save_icon_to_disk( $existing_file_url );

		$config = Tatrapayplus\TatrapayplusApiClient\Configuration::getDefaultConfiguration( $mode )->setAccessToken( $access_token );

		$api_instance = new Tatrapayplus\TatrapayplusApiClient\Api\TatraPayPlusAPIApi( $config, $client );
		$image_url    = $this->get_option( 'logo' );

		if ( $access_token ) {
			if ( ! empty( $image_url ) ) {
				try {
					if ( ! function_exists( 'WP_Filesystem' ) ) {
						require_once ABSPATH . 'wp-admin/includes/file.php';
					}
					WP_Filesystem();
					global $wp_filesystem;
					$image_content = $wp_filesystem->get_contents( $image_url );
					if ( $image_content === false ) {
						$this->add_error( __( 'Unable to set appearance logo.', 'tatrapay-payment-gateway' ) );
					} else {
						$base64_image = base64_encode( $image_content );
						$api_instance->setLogo(
							new AppearanceLogoRequest(
								array(
									'logo_image' => $base64_image,
								)
							)
						);
					}
				} catch ( \Tatrapayplus\TatrapayplusApiClient\ApiException $e ) {
					$response_body_json = \Tatrapayplus\TatrapayplusApiClient\Api\TatraPayPlusAPIApi::json_decode( $e->getResponseBody() );
					$this->add_error( __( 'Unable to set appearance logo.', 'tatrapay-payment-gateway' ) . ' [' . $response_body_json->error . ']' );
				} catch ( Exception $e ) {
					$this->add_error( __( 'Unable to set appearance logo.', 'tatrapay-payment-gateway' ) );
				}
			}
			try {
				$api_instance->setAppearance(
					new AppearanceRequest(
						array(
							'theme'          => $this->get_option( 'theme' ),
							'tint_on_accent' => new ColorAttribute(
								array(
									'color_dark_mode'  => empty( $this->get_option( 'tint_on_accent_dark' ) ) ? null : $this->get_option( 'tint_on_accent_dark' ),
									'color_light_mode' => empty( $this->get_option( 'tint_on_accent_light' ) ) ? null : $this->get_option( 'tint_on_accent_light' ),
								)
							),
							'tint_accent'    => new ColorAttribute(
								array(
									'color_dark_mode'  => empty( $this->get_option( 'tint_accent_dark' ) ) ? null : $this->get_option( 'tint_accent_dark' ),
									'color_light_mode' => empty( $this->get_option( 'tint_accent_light' ) ) ? null : $this->get_option( 'tint_accent_light' ),
								)
							),
							'surface_accent' => new ColorAttribute(
								array(
									'color_dark_mode'  => empty( $this->get_option( 'surface_accent_dark' ) ) ? null : $this->get_option( 'surface_accent_dark' ),
									'color_light_mode' => empty( $this->get_option( 'surface_accent_light' ) ) ? null : $this->get_option( 'surface_accent_light' ),
								)
							),
						)
					)
				);
			} catch ( \Tatrapayplus\TatrapayplusApiClient\ApiException $e ) {
				$response_body_json = \Tatrapayplus\TatrapayplusApiClient\Api\TatraPayPlusAPIApi::json_decode( $e->getResponseBody() );
				$this->add_error( __( 'Unable to set appearance colors.', 'tatrapay-payment-gateway' ) . ' [' . $response_body_json->error . ']' );
			} catch ( Exception $e ) {
				$this->add_error( __( 'Unable to set appearance colors.', 'tatrapay-payment-gateway' ) );
			}
		}

		return $result;
	}

	public function save_icon_to_disk( $existing_file_url ) {
		$logo_id = 'woocommerce_' . $this->id . '_logo';
		// accessed inside process_admin_options.
		if ( isset( $_FILES[ $logo_id ] ) && ! empty( $_FILES[ $logo_id ]['name'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$uploaded_file = $_FILES[ $logo_id ]; // phpcs:ignore WordPress.Security.NonceVerification.Missing

			// Handle file upload.
			if ( ! function_exists( 'wp_handle_upload' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}
			$upload = wp_handle_upload( $uploaded_file, array( 'test_form' => false ) );

			if ( isset( $upload['file'] ) ) {
				$file_url = $upload['url'];

				// Save the file URL or path in the options.
				$this->update_option( 'logo', $file_url );
			} else {
				// Handle upload error.
				$error = isset( $upload['error'] ) ? $upload['error'] : __( 'File upload failed', 'tatrapay-payment-gateway' );
				$this->add_error( $error );
				$this->update_option( 'logo', $existing_file_url );
			}
		} else {
			$this->update_option( 'logo', $existing_file_url );
		}
	}

	public function get_or_set_cached_available_payment_methods( $force_set = false ) {
		$available_payment_methods = $this->get_option( 'available_payment_methods' );
		if ( empty( $available_payment_methods ) || $force_set ) {
			$mode                      = $this->get_option( 'mode' );
			$client                    = new WpCurlClient( $this->enable_debug_log );
			$access_token              = TatraPayPlusService::retrieve_access_token_with_credentials(
				$client,
				$this->get_option( 'client_id' ),
				$this->get_option( 'client_secret' ),
				$mode,
				$this->scopes,
			);
			$available_payment_methods = TatraPayPlusService::get_available_payment_methods( $client, $access_token, $mode );
			$this->update_option( 'available_payment_methods', $available_payment_methods );
		}

		return $available_payment_methods;
	}

	public function process_payment( $order_id ) {
		global $woocommerce;
		$order = wc_get_order( $order_id );

		try {
			$result = $this->create_payment(
				$this->retrieve_access_token(),
				$order,
			);
			$order->set_transaction_id( $result->getPaymentId() );
			$order->save();
			$woocommerce->cart->empty_cart();

			return array(
				'result'   => 'success',
				'redirect' => $result->getTatraPayPlusUrl(),
			);
		} catch ( Exception $e ) {
			$message = (string) $e->getMessage();
			if ( method_exists( $e, 'getResponseBody' ) ) {
				$message .= $e->getResponseBody();
			}
			$message = esc_html( $message );
			$order->add_order_note( $message );
			$order->update_status( 'failed' );

			try {
				$response_body_json = \Tatrapayplus\TatrapayplusApiClient\Api\TatraPayPlusAPIApi::json_decode( $e->getResponseBody() );
				$user_message       = 'tatrapay+: ' . $response_body_json->errorCode . ' ' . $response_body_json->errorDescription;

				if ( $response_body_json->errorDescription == 'Invalid Redirect-URI' ) {
					$redirect_uri_message = __( 'Please add this Redirect-URI to developers portal:', 'tatrapay-payment-gateway' );
					$user_message        .= '<br>' . $redirect_uri_message . ' ' . WC()->api_request_url( 'wc_gateway_tatrapayplus' );
				}
			} catch ( Exception $e ) {
				$user_message = $message;
			}

			wc_add_notice( $user_message, 'error' );

			return array(
				'result'   => 'failure',
				'redirect' => $this->get_return_url( $order ),
				'messages' => $user_message,
			);
		}
	}

	public function create_payment( string $access_token, WC_Order $order ) {
		$config       = Tatrapayplus\TatrapayplusApiClient\Configuration::getDefaultConfiguration( $this->mode )->setAccessToken( $access_token );
		$api_instance = new Tatrapayplus\TatrapayplusApiClient\Api\TatraPayPlusAPIApi( $config, new WpCurlClient( $this->enable_debug_log ) );

		$base_payment = new BasePayment(
			array(
				'instructed_amount' => new Amount(
					array(
						'amount_value' => $this->round( $order->get_total(), $order ),
						'currency'     => get_woocommerce_currency(),
					)
				),
				'end_to_end'        => new E2e(
					array(
						'variable_symbol' => TatraPayPlusService::limit_length( $order->get_order_number(), 10 ),
					)
				),
			)
		);

		$user_data = new UserData(
			array(
				'first_name' => TatraPayPlusService::limit_length( $order->get_billing_first_name(), 30 ),
				'last_name'  => TatraPayPlusService::limit_length( $order->get_billing_last_name(), 30 ),
				'email'      => TatraPayPlusService::limit_length( $order->get_billing_email(), 50 ),
			)
		);

		$pay_later = new PayLater(
			array(
				'order' => new Order(
					array(
						'order_no'    => $order->get_order_number(),
						'order_items' => $this->get_order_items( $order ),
					)
				),
			)
		);

		$bank_transfer   = new BankTransfer();
		$billing_address = new Address(
			array(
				'street_name'     => TatraPayPlusService::limit_length( $order->get_billing_address_1(), 40 ),
				'building_number' => TatraPayPlusService::limit_length( $order->get_billing_address_2(), 10 ),
				'town_name'       => TatraPayPlusService::limit_length( $order->get_billing_city(), 35 ),
				'post_code'       => TatraPayPlusService::limit_length( wc_format_postcode( $order->get_billing_postcode(), $order->get_billing_country() ), 10 ),
				'country'         => TatraPayPlusService::limit_length( $order->get_billing_country(), 2 ),
			)
		);
		if ( $order->get_shipping_postcode() ) {
			$shipping_address = new Address(
				array(
					'street_name'     => TatraPayPlusService::limit_length( $order->get_shipping_address_1(), 40 ),
					'building_number' => TatraPayPlusService::limit_length( $order->get_shipping_address_2(), 10 ),
					'town_name'       => TatraPayPlusService::limit_length( $order->get_shipping_city(), 35 ),
					'post_code'       => TatraPayPlusService::limit_length( wc_format_postcode( $order->get_shipping_postcode(), $order->get_shipping_country() ), 10 ),
					'country'         => TatraPayPlusService::limit_length( $order->get_shipping_country(), 2 ),
				)
			);
		} else {
			$shipping_address = new Address(
				array(
					'street_name'     => $billing_address->getStreetName(),
					'building_number' => $billing_address->getBuildingNumber(),
					'town_name'       => $billing_address->getTownName(),
					'post_code'       => $billing_address->getPostCode(),
					'country'         => $billing_address->getCountry(),
				)
			);
		}

		$card_holder      = TatraPayPlusService::limit_length( TatraPayPlusService::remove_diacritics( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(), 45 ) );
		$card_detail      = new CardDetail(
			array(
				'card_holder'      => $card_holder,
				'billing_address'  => $billing_address,
				'shipping_address' => $shipping_address,
			)
		);
		$comfort_pay_data = $this->get_comfort_pay_data( $order );
		if ( $comfort_pay_data ) {
			$card_detail->setComfortPay( $comfort_pay_data );
		}

		$initiate_payment_request = new InitiatePaymentRequest(
			array(
				'base_payment'  => $base_payment,
				'bank_transfer' => $bank_transfer,
				'user_data'     => $user_data,
				'card_detail'   => $card_detail,
				'pay_later'     => $pay_later,
			)
		);
		if ( get_locale() === 'sk_SK' ) {
			$accept_language = 'sk';
		} else {
			$accept_language = 'en';
		}
		$redirect_uri = WC()->api_request_url( 'wc_gateway_tatrapayplus' );
		if ( $card_detail->getComfortPay() instanceof SignedCardIdObj ) {
			$preferred_method = PaymentMethod::CARD_PAY;
		} else {
			$preferred_method = null;
		}
		$note   = '';
		$result = $api_instance->initiatePayment( $redirect_uri, $initiate_payment_request, $preferred_method, $accept_language );
		if ( $this->enable_debug_log === 'yes' ) {
			$raw_content_str = print_r( (string) $result['response']->getBody(), true );
			$note           .= 'Response from server: ' . $raw_content_str;
		}
		if ( ! empty( $note ) ) {
			$order->add_order_note( $note );
		}

		return $result['object'];
	}

	/**
	 * Round prices.
	 *
	 * @param double   $price Price to round.
	 * @param WC_Order $order Order object.
	 *
	 * @return double
	 */
	protected function round( $price, $order ) {
		$precision = 2;

		if ( ! $this->currency_has_decimals( $order->get_currency() ) ) {
			$precision = 0;
		}

		if ( ! is_numeric( $price ) ) {
			$price = floatval( $price );
		}

		return round( $price, $precision, PHP_ROUND_HALF_UP );
	}

	/**
	 * Check if currency has decimals.
	 *
	 * @param string $currency Currency to check.
	 *
	 * @return bool
	 */
	protected function currency_has_decimals( $currency ) {
		if ( in_array( $currency, array( 'HUF', 'JPY', 'TWD' ), true ) ) {
			return false;
		}

		return true;
	}

	public function get_order_items( WC_Order $order ) {
		$order_items = array_values( $order->get_items( array( 'line_item', 'fee' ) ) );

		$order_items     = array_map(
			function ( $item ) use ( $order ) {
				$product          = $item->get_product();
				$tatra_order_item = new OrderItem(
					array(
						'quantity'         => $item->get_quantity(),
						'total_item_price' => $order->get_line_total( $item, true ),
						'item_detail'      => new ItemDetail(
							array(
								'item_detail_sk' => new ItemDetailLangUnit(
									array(
										'item_name' => TatraPayPlusService::limit_length( $item->get_name(), 255 ),
									)
								),
							)
						),
					)
				);
				if ( ! is_null( $product ) ) {
					$parsed_url = wp_parse_url( $product->get_permalink() );
					$clean_url  = $parsed_url['scheme'] . '://' . $parsed_url['host'] . $parsed_url['path'];
					$tatra_order_item->setItemInfoUrl( $clean_url );
				}

				return $tatra_order_item;
			},
			$order_items
		);
		$shipping_method = $order->get_shipping_method();
		if ( $shipping_method ) {
			$shipping_price = $order->get_shipping_total() + $order->get_shipping_tax();
			$shipping_price = static::round( $shipping_price, $order );
			$order_items[]  = new OrderItem(
				array(
					'quantity'         => 1,
					'total_item_price' => $shipping_price,
					'item_detail'      => new ItemDetail(
						array(
							'item_detail_sk' => new ItemDetailLangUnit(
								array(
									'item_name' => TatraPayPlusService::limit_length( $shipping_method, 255 ),
								)
							),
						)
					),
				)
			);
		}

		return $order_items;
	}

	public function get_comfort_pay_data( WC_Order $order ) {
		if ( $this->enable_comfort_pay !== 'yes' ) {
			return null;
		}

		$token_id = $this->get_token_from_form();
		$token    = WC_Payment_Tokens::get( $token_id );

		if ( $token ) {
			$public_key_path = plugin_dir_path( __DIR__ ) . 'plugin_assets/public_key/ECID_PUBLIC_KEY_2023.txt';

			if ( ! function_exists( 'WP_Filesystem' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}
			WP_Filesystem();
			global $wp_filesystem;
			$public_key_content = $wp_filesystem->get_contents( $public_key_path );

			if ( ! $public_key_content ) {
				return null;
			}
			$signed_card_id = TatraPayPlusService::generate_signed_card_id_from_cid( $token->get_token(), $public_key_content );
			if ( empty( $signed_card_id ) ) {
				return null;
			}

			return new SignedCardIdObj(
				array(
					'signed_card_id' => $signed_card_id,
				)
			);
		} elseif ( $this->is_comfort_pay_enabled() ) {
			return new RegisterForComfortPayObj(
				array(
					'register_for_comfort_pay' => true,
				)
			);
		}

		return null;
	}

	public function get_token_from_form() {
		// nonce already checked by woo in process_checkout.
		if ( isset( $_POST[ 'wc-' . $this->id . '-payment-token' ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$token_value = sanitize_text_field( wp_unslash( $_POST[ 'wc-' . $this->id . '-payment-token' ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( ! empty( $token_value ) ) {
				return $token_value;
			}
		}

		return null;
	}

	public function is_comfort_pay_enabled() {
		// nonce already checked by woo in process_checkout.
		if ( isset( $_POST[ 'wc-' . $this->id . '-new-payment-method' ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$new_method_value = sanitize_text_field( wp_unslash( $_POST[ 'wc-' . $this->id . '-new-payment-method' ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( ! empty( $new_method_value ) ) {
				return true;
			}
		}

		return false;
	}

	public function retrieve_access_token() {
		return TatraPayPlusService::retrieve_access_token_with_credentials( new WpCurlClient( $this->enable_debug_log ), $this->client_id, $this->client_secret, $this->mode, $this->scopes );
	}

	public static function check_response_from_gateway() {
		// redirected here from payment gateway, unable to include nonce.
		if ( ! empty( $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['paymentID'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$payment_id = sanitize_text_field( wp_unslash( $_GET['paymentID'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			} elseif ( isset( $_GET['paymentId'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$payment_id = sanitize_text_field( wp_unslash( $_GET['paymentId'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			} else {
				$payment_id = null;
			}
			if ( isset( $payment_id ) ) {
				try {
					$order           = static::get_order_by_transaction_id( $payment_id );
					$payment_gateway = wc_get_payment_gateway_by_order( $order );
					$access_token    = $payment_gateway->retrieve_access_token();
					$mode            = $payment_gateway->mode;
					$response        = TatraPayPlusService::check_payment_status( new WpCurlClient( $payment_gateway->enable_debug_log ), $access_token, $payment_id, $mode );
					if ( $order ) {
						$payment_gateway->process_status( $order, $response['object'], $response['response'] );
						wp_safe_redirect( $payment_gateway->get_return_url( $order ) );
						exit;
					}
				} catch ( Exception $e ) {
					wp_safe_redirect( '/' );
					exit;
				}
			}
		}
		wp_safe_redirect( '/' );
		exit;
	}

	public static function get_order_by_transaction_id( $transaction_id ): ?WC_Order {
		$query  = new WC_Order_Query( array( 'transaction_id' => $transaction_id ) );
		$orders = $query->get_orders();

		return reset( $orders );
	}

	public function process_status( WC_Order $order, PaymentIntentStatusResponse $status_response, $response ) {
		$status_obj     = $status_response->getStatus();
		$status_updated = false;
		if ( ! is_null( $status_obj ) ) {
			$status_text = $status_obj->getStatus();
			if ( in_array( $status_text, $status_obj->getAcceptedStatuses(), true ) ) {
				$status_updated = true;
				$order->payment_complete();
				$order->add_order_note( $status_response->getSelectedPaymentMethod() );
			} elseif ( in_array( $status_text, $status_obj->getRejectedStatuses(), true ) ) {
				$status_updated = true;
				$order->update_status( 'cancelled', __( 'Payment failed', 'tatrapay-payment-gateway' ) );
			}
			if ( $status_obj instanceof CardPayStatusStructure ) {
				$this->save_card_token_from_comfort_pay( $status_obj );
			}
		}
		if ( ! $status_updated ) {
			$order->update_status( 'pending' );
		}
		if ( $this->enable_debug_log === 'yes' ) {
			$raw_content_str = print_r( (string) $response->getBody(), true );
			$note            = 'Response from server: ' . $raw_content_str;
			$order->add_order_note( $note );
		}
		$order->save();
	}

	public function schedule_payment_status_task() {
		// Schedule a recurring action.
		if ( ! as_next_scheduled_action( $this->id . '_check_status' ) ) {
			as_schedule_recurring_action( time(), 60, $this->id . '_check_status' );
		}
	}

	public function check_status_function() {
		$args   = array(
			'status'         => 'wc-pending',
			'payment_method' => $this->id,
			'limit'          => - 1, // Retrieve all orders.
		);
		$query  = new WC_Order_Query( $args );
		$orders = $query->get_orders();
		foreach ( $orders as $order ) {
			try {
				if ( empty( $order->get_transaction_id() ) ) {
					continue;
				}
				$access_token = $this->retrieve_access_token();
				$response     = TatraPayPlusService::check_payment_status( new WpCurlClient( $this->enable_debug_log ), $access_token, $order->get_transaction_id(), $this->mode );
				$this->process_status( $order, $response['object'], $response['response'] );
			} catch ( Exception $e ) {
				// ignore error if status of order can't be retrieved.
			}
		}
	}
}
