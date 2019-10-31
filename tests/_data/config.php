<?php
/**
 * Disable autoloading while running tests, as the test
 * suite already bootstraps the autoloader and creates
 * fatal errors when the autoloader is loaded twice
 */
define( 'GRAPHQL_DEBUG', true );
define( 'WPGRAPHQL_WOOCOMMERCE_AUTOLOAD', getenv( 'WPGRAPHQL_WOOCOMMERCE_AUTOLOAD' ) );
define( 'STRIPE_API_PUBLISHABLE_KEY', 'pk_test_s52b7rmlnH6ueG0OSdW7hLz6' );
define( 'STRIPE_API_SECRET_KEY', 'sk_test_tw8KnN1JqOGnIJEaJlnaDVMP' );