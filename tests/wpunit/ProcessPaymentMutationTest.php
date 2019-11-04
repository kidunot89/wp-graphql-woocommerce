<?php

class ProcessPaymentMutationTest extends \Codeception\TestCase\WPTestCase {

    public function setUp() {
        // before
        parent::setUp();

        $this->order = $this->getModule('\Helper\Wpunit')->order();
        $this->tax   = $this->getModule('\Helper\Wpunit')->tax_rate();

        // Turn on tax calculations and store shipping countries. Important!
        update_option( 'woocommerce_ship_to_countries', 'all' );
        update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
        update_option( 'woocommerce_tax_round_at_subtotal', 'no' );

        // Turn on guest checkout.
        update_option( 'woocommerce_enable_guest_checkout', 'yes' );

        // Enable payment gateway.
        update_option(
            'woocommerce_stripe_settings',
            array(
                'enabled'                       => 'yes',
                'title'                         => 'Credit Card (Stripe)',
                'description'                   => 'Pay with your credit card via Stripe',
                'webhook'                       => '',
                'testmode'                      => 'yes',
                'test_publishable_key'          => STRIPE_API_PUBLISHABLE_KEY,
                'test_secret_key'               => STRIPE_API_SECRET_KEY,
                'test_webhook_secret'           => '',
                'publishable_key'               => '',
                'secret_key'                    => '',
                'webhook_secret'                => '',
                'inline_cc_form'                => 'no',
                'statement_descriptor'          => '',
                'capture'                       => 'yes',
                'payment_request'               => 'yes',
                'payment_request_button_type'   => 'buy',
                'payment_request_button_theme'  => 'dark',
                'payment_request_button_height' => '44',
                'saved_cards'                   => 'yes',
                'logging'                       => 'no',
            )
        );

        // Additional cart fees.
        add_action(
            'woocommerce_cart_calculate_fees',
            function() {
                $percentage = 0.01;
                $surcharge = ( WC()->cart->cart_contents_total + WC()->cart->shipping_total ) * $percentage;	
                WC()->cart->add_fee( 'Surcharge', $surcharge, true, '' );
            }
        );

        // Create a tax rate.
        $this->tax->create(
            array(
                'country'  => '',
                'state'    => '',
                'rate'     => 20.000,
                'name'     => 'VAT',
                'priority' => '1',
                'compound' => '0',
                'shipping' => '1',
                'class'    => ''
            )
        );
        // Create sample order to be used as a parent order.
        $this->order_id = $this->order->create();
    }

    public function tearDown() {
        // your tear down methods here
        delete_option( 'woocommerce_stripe_settings' );

        // then
        parent::tearDown();
    }

    private function createOrder( $input ) {
        $mutation = '
            mutation ( $input: CheckoutInput! ) {
                checkout( input: $input ) {
                    clientMutationId
                    order {
                        id
                        orderId
                    }
                    result
                }
            }
        ';

        return graphql( array( 'query' => $mutation, 'variables' => array( 'input' => $input ) ) );
    }

    private function processPayment( $input ) {
        $mutation = '
            mutation ( $input: ProcessPaymentInput! ) {
                processPayment( input: $input ) {
                    clientMutationId
                    order {
                        status
                    }
                    customer {
                        id
                    }
                    result
                    message
                }
            }
        ';

        return graphql( array( 'query' => $mutation, 'variables' => array( 'input' => $input ) ) );
    }

    // Creates a stripe token for testing.
    private function createToken() {
        $token = \Stripe\Token::create(
            array(
                'card' => array(
                    'number'    => '4242424242424242',
                    'exp_month' => 10,
                    'exp_year'  => 2020,
                    'cvc'       => '314',
                ),
            )
        );

        // use --debug flag to view.
        codecept_debug( $token );

        return array(
            'tokenId'    => $token->id,
            'last4'       => $token->card->last4,
            'expiryYear'  => $token->card->exp_year,
            'expiryMonth' => $token->card->exp_month,
            'cardType'    => $token->card->brand,
            'extraData'   => array(
                array(
                    'key' => 'fingerprint',
                    'value' => $token->card->fingerprint,
                )
            ),
        );
    }

    // tests
    public function testProcessPaymentMutationWithStripe() {
        $order = \WC()->order_factory::get_order( $this->order_id );
        $order->set_payment_method( 'stripe' );
        $order->save();
        
        /**
         * Assertion One
         * 
         * Test with simple token and pending order
         */
        $input    = array(
            'clientMutationId' => 'some_id',
            'orderId'          => $order->ID,
            'paymentMethod'    => 'stripe',
            'newToken'     => array_merge(
                $this->createToken(),
                array(
                    'type'      => 'CREDIT_CARD',
                    'isDefault' => true,
                )
            ),
        );
        $actual   = $this->processPayment( $input );

        // use --debug flag to view.
        codecept_debug( $actual );

        $expected = array(

        );

        $this->assertEquals( $expected, $actual);
    }

}