<?php
/**
 * Mutation - processPayment
 *
 * Registers mutation for processing order payments.
 *
 * @package WPGraphQL\WooCommerce\Mutation
 * @since 0.3.1
 */

namespace WPGraphQL\WooCommerce\Mutation;

use GraphQL\Error\UserError;
use GraphQL\Type\Definition\ResolveInfo;
use WPGraphQL\AppContext;
use WPGraphQL\WooCommerce\Data\Mutation\Payment_Mutation;
use WPGraphQL\WooCommerce\Model\Order;
use WPGraphQL\WooCommerce\Model\Customer;

/**
 * Class Process_Payment
 */
class Process_Payment {
	/**
	 * Registers mutation
	 */
	public static function register_mutation() {
		register_graphql_mutation(
			'processPayment',
			array(
				'inputFields'         => self::get_input_fields(),
				'outputFields'        => self::get_output_fields(),
				'mutateAndGetPayload' => self::mutate_and_get_payload(),
			)
		);
	}

	/**
	 * Defines the mutation input field configuration
	 *
	 * @return array
	 */
	public static function get_input_fields() {
		return array(
			'orderId'       => array(
				'type'        => array( 'non_null' => 'ID' ),
				'description' => __( 'ID of order being paid for.', 'wp-graphql-woocommerce' ),
			),
			'paymentMethod' => array(
				'type'        => array( 'non_null' => 'String' ),
				'description' => __( 'ID/slug of payment method being used.', 'wp-graphql-woocommerce' ),
			),
			'tokenId'       => array(
				'type'        => 'Int',
				'description' => __( 'ID for existing user payment method token', 'wp-graphql-woocommerce' ),
			),
			'newToken'      => array(
				'type'        => 'PaymentTokenInput',
				'description' => __( 'The payment token being used to pay for the order', 'wp-graphql-woocommerce' ),
			),
		);
	}

	/**
	 * Defines the mutation output field configuration
	 *
	 * @return array
	 */
	public static function get_output_fields() {
		return array(
			'order'    => array(
				'type'    => 'Order',
				'resolve' => function( $payload ) {
					return new Order( $payload['order_id'] );
				},
			),
			'customer' => array(
				'type'    => 'Customer',
				'resolve' => function() {
					return is_user_logged_in() ? new Customer( get_current_user_id() ) : null;
				},
			),
			'result'   => array(
				'type'    => 'String',
				'resolve' => function( $payload ) {
					return $payload['result'];
				},
			),
			'message'  => array(
				'type'    => 'String',
				'resolve' => function( $payload ) {
					return $payload['message'];
				},
			),
		);
	}

	/**
	 * Defines the mutation data modification closure.
	 *
	 * @return callable
	 */
	public static function mutate_and_get_payload() {
		return function( $input, AppContext $context, ResolveInfo $info ) {
			if ( empty( $input['orderId'] ) ) {
				throw new UserError( __( 'No order ID provided.', 'wp-graphql-woocommerce' ) );
			}
			$order = \WC()->order_factory::get_order( $input['orderId'] );
			if ( empty( $input['paymentMethod'] ) ) {
				throw new UserError( __( 'No payment method provided.', 'wp-graphql-woocommerce' ) );
			}
			$payment_gateway_id = $input['paymentMethod'];
			$gateway            = Payment_Mutation::validate_gateway( $payment_gateway_id );
			if ( ! in_array( 'tokenization', $gateway->supports, true ) ) {
				throw new UserError( __( 'This payment method doesn\'t support tokenization.', 'wp-graphql-woocommerce' ) );
			}

			if ( empty( $input['tokenId'] ) && empty( $input['newToken'] ) ) {
				throw new UserError( __( 'A WooCommerce token ID or new token information must be provided.', 'wp-graphql-woocommerce' ) );
			}

			if ( ! empty( $input['newToken'] ) ) {
				$new_token = Payment_Mutation::add_new_payment_token( $payment_gateway_id, $input['newToken'] );
				if ( ! $new_token ) {
					throw new UserError( __( 'Payment token invalid, Please check input and try again.', 'wp-graphql-woocommerce' ) );
				}
				$results = Payment_Mutation::process_order_payment_with_token(
					$order->ID,
					$payment_gateway_id,
					$new_token->get_token()
				);
			}

			if ( ! empty( $input['tokenId'] ) ) {
				$results = Payment_Mutation::process_order_payment( $order->ID, $payment_gateway_id, $input['tokenId'] );
			}

			return array_merge( $results, array( 'order_id' => $order->ID ) );
		};
	}
}
