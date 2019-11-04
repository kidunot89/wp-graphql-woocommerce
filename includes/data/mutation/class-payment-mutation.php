<?php
/**
 * Defines helper functions for processing order payment.
 *
 * @package WPGraphQL\WooCommerce\Data\Mutation
 * @since 0.3.1
 */

namespace WPGraphQL\WooCommerce\Data\Mutation;

use GraphQL\Error\UserError;

/**
 * Class Payment_Mutation
 */
class Payment_Mutation {
	/**
	 * Adds new payment token.
	 *
	 * @param string $payment_gateway_id  Payment Gateway ID.
	 * @param array  $payment_token       Payment token data.
	 *
	 * @throws UserError If payment token data invalid.
	 * @return false|WC_Payment_Token_CC
	 */
	public static function add_new_payment_token( $payment_gateway_id, $payment_token ) {
		if ( empty( $payment_token['type'] ) ) {
			throw new UserError( __( 'Token type must be provided', 'wp-graphql-woocommerce' ) );
		}
		switch ( $payment_token['type'] ) {
			case 'cc':
				// Get payment gateway token class.
				$class = apply_filters(
					"graphql_{$payment_gateway_id}_tokenization_class",
					'\WC_Payment_Token_CC',
					$payment_gateway_id,
					$payment_token['type']
				);

				$token = new $class();
				$token->set_token( $payment_token['tokenId'] );
				$token->set_gateway_id( $payment_gateway_id );
				$token->set_last4( $payment_token['last4'] );
				$token->set_expiry_year( $payment_token['expiryYear'] );
				$token->set_expiry_month( $payment_token['expiryMonth'] );
				$token->set_card_type( $payment_token['cardType'] );
				break;
			case 'eCheck':
				// Get payment gateway token class.
				$class = apply_filters(
					"graphql_{$payment_gateway_id}_tokenization_class",
					'\WC_Payment_Token_eCheck',
					$payment_gateway_id,
					$payment_token['type']
				);

				$token = new $class();
				$token->set_token( $payment_token['tokenId'] );
				$token->set_gateway_id( $payment_gateway_id );
				$token->set_last4( $payment_token['last4'] );
				break;
		}

		$token->set_user_id( get_current_user_id() );
		$token->set_default( $payment_token['isDefault'] );

		// Add meta data.
		if ( ! empty( $payment_token['extraData'] ) ) {
			foreach ( $payment_token['extraData'] as $meta ) {
				$key   = $meta['key'];
				$value = $meta['value'];
				if ( is_callable( array( $token, "set_{$key}" ) ) ) {
					$token->set_{$key}( $value );
				}
			}
		}

		if ( ! $token->validate() ) {
			return false;
		}

		if ( ! $token->save() ) {
			return false;
		}

		return $token;
	}

	/**
	 * Checks if payment gateway is available.
	 *
	 * @param string $payment_gateway_id  Payment Gateway ID.
	 *
	 * @throws UserError If payment gateway is not found or unavailable.
	 */
	public static function validate_gateway( $payment_gateway_id ) {
		$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
		if ( ! isset( $available_gateways[ $payment_gateway_id ] ) ) {
			if ( ! isset( WC()->payment_gateways->payment_gateways()[ $payment_gateway_id ] ) ) {
				throw new UserError(
					sprintf(
						/* translators: %s: payment gateway ID */
						__( 'The %s payment gateway could not be found', 'wp-graphql-woocommerce' ),
						$payment_gateway_id
					)
				);
			}
			throw new UserError(
				sprintf(
					/* translators: %s: payment gateway ID */
					__( 'The %s payment gateway is unavailable', 'wp-graphql-woocommerce' ),
					$payment_gateway_id
				)
			);
		}

		return $available_gateways[ $payment_gateway_id ];
	}

	/**
	 * Process an order that does require payment.
	 *
	 * @param int    $order_id       Order ID.
	 * @param string $payment_method Payment method.
	 * @param int    $token_id       WooCommerce Token ID.
	 * @throws UserError Invalid token.
	 *
	 * @return array
	 */
	public static function process_order_payment( $order_id, $payment_method, $token_id ) {
		$gateway = self::validate_gateway( $payment_method );

		// Validate token.
		$token = \WC_Payment_Tokens::get( $token_id );
		if ( $token->get_user_id() !== get_current_user_id() ) {
			throw new UserError(
				__(
					'Please make sure your card details have been entered correctly and that your browser supports JavaScript.',
					'wp-graphql-woocommerce'
				)
			);
		}

		$wc_token_key           = "wc-{$payment_method}-payment-token";
		$_POST[ $wc_token_key ] = $token->get_id();

		// Store Order ID in session so it can be re-used after payment failure.
		WC()->session->set( 'order_awaiting_payment', $order_id );

		// Process Payment.
		return $gateway->process_payment( $order_id );
	}

	/**
	 * Process an order that does require payment.
	 *
	 * @param int    $order_id       Order ID.
	 * @param string $payment_method Payment method.
	 * @param int    $token_id       New token ID.
	 *
	 * @return array.
	 */
	public static function process_order_payment_with_token( $order_id, $payment_method, $token_id ) {
		$gateway                            = self::validate_gateway( $payment_method );
		$_POST[ "{$payment_method}_token" ] = $token_id;

		// Store Order ID in session so it can be re-used after payment failure.
		WC()->session->set( 'order_awaiting_payment', $order_id );

		// Process Payment.
		return $gateway->process_payment( $order_id );
	}
}
