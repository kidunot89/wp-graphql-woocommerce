<?php
/**
 * WPInputObjectType - PaymentTokenInput
 *
 * @package \WPGraphQL\WooCommerce\Type\WPInputObject
 * @since   0.3.1
 */

namespace WPGraphQL\WooCommerce\Type\WPInputObject;

/**
 * Class Payment_Token_Input
 */
class Payment_Token_Input {
	/**
	 * Registers type
	 */
	public static function register() {
		register_graphql_input_type(
			'PaymentTokenInput',
			array(
				'description' => __( 'Payment Token data.', 'wp-graphql-woocommerce' ),
				'fields'      => array(
					'type'        => array(
						'type' => array( 'non_null' => 'PaymentTokenTypeEnum' ),
					),
					'token_id'    => array(
						'type' => array( 'non_null' => 'String' ),
					),
					'last4'       => array(
						'type' => array( 'non_null' => 'Int' ),
					),
					'expiryYear'  => array(
						'type' => 'Int',
					),
					'expiryMonth' => array(
						'type' => 'Int',
					),
					'cardType'    => array(
						'type' => 'String',
					),
					'isDefault'   => array(
						'type' => 'Boolean',
					),
					'extraData'   => array(
						'type' => array( 'list_of' => 'MetaDataInput' ),
					),
				),
			)
		);
	}
}
