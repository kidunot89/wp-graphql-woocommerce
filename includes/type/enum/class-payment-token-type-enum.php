<?php
/**
 * WPEnum Type - PaymentTokenTypeEnum
 *
 * @package \WPGraphQL\WooCommerce\Type\WPEnum
 * @since   0.3.1
 */

namespace WPGraphQL\WooCommerce\Type\WPEnum;

/**
 * Class Payment_Token
 */
class Payment_Token_Type_Enum {
	/**
	 * Registers type
	 */
	public static function register() {
		$values = array(
			'CREDIT_CARD' => array( 'value' => 'cc' ),
			'ECHECK'      => array( 'value' => 'eCheck' ),
		);

		register_graphql_enum_type(
			'PaymentTokenTypeEnum',
			array(
				'description' => __( 'Payment token type enumeration', 'wp-graphql-woocommerce' ),
				'values'      => $values,
			)
		);
	}
}
