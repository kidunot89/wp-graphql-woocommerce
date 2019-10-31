<?php

class ProcessPaymentMutationTest extends \Codeception\TestCase\WPTestCase {

    public function setUp() {
        // before
        parent::setUp();

        // Turn on tax calculations and store shipping countries. Important!
        update_option( 'woocommerce_ship_to_countries', 'all' );
        update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
        update_option( 'woocommerce_tax_round_at_subtotal', 'no' );

        // Enable payment gateway.
        update_option(
            'woocommerce_stripe_settings',
            array(
                'enabled'      => 'yes',
                'title'        => 'Direct bank transfer',
                'description'  => 'Make your payment directly into our bank account. Please use your Order ID as the payment reference. Your order will not be shipped until the funds have cleared in our account.',
                'instructions' => 'Instructions that will be added to the thank you page and emails.',
                'account'      => '',
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

        $actual = graphql( array( 'query' => $mutation, 'variables' => array( 'input' => $input ) ) );
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

        $actual = graphql( array( 'query' => $mutation, 'variables' => array( 'input' => $input ) ) );
    }

    // tests
    public function testProcessPaymentMutationWithStripe() {

    }

}