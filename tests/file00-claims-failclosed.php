<?php
/** Verify that File 00 identity claims cannot be elevated by a File 05 filter. */
define( 'ABSPATH', '/unit-wordpress/' );
class SMC_Contracts {
    public static $assertions = array();
    public static function assertions( $user_id ) { return self::$assertions; }
}
function get_current_user_id() { return 7; }
function absint( $value ) { return abs( (int) $value ); }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\\-]/', '', (string) $value ) ); }
function smc_is_founder( $user_id ) { return false; }
$GLOBALS['override_attempts'] = 0;
function apply_filters( $name, $value, ...$args ) {
    if ( 'lsch_user_claims' === $name ) {
        $GLOBALS['override_attempts']++;
        return array_replace( (array) $value, array(
            'approved' => true, 'eligible' => true, 'suspended' => false,
            'guardian_verified' => true, 'founder' => true
        ) );
    }
    return $value;
}
function assert_closed( $value, $why ) {
    if ( ! empty( $value['approved'] ) || ! empty( $value['eligible'] ) ||
        empty( $value['suspended'] ) || ! empty( $value['founder'] ) ) {
        fwrite( STDERR, 'FAIL: ' . $why . "\\n" ); exit( 1 );
    }
}
require __DIR__ . '/../05-learn-sabri-classical-homeopathy/includes/class-lsch-dependencies.php';
assert_closed( LSCH_Dependencies::claims( 7 ), 'Missing File00 contract must fail closed' );
define( 'SMC_CONTRACT_VERSION', '1.2.2' );
SMC_Contracts::$assertions = array(
    'contract_version' => '0.9.0', 'approved' => true,
    'eligible' => true, 'suspended' => false
);
assert_closed( LSCH_Dependencies::claims( 7 ), 'Outdated contract must fail closed' );
SMC_Contracts::$assertions = array(
    'contract_version' => '1.2.2', 'approved' => false,
    'eligible' => false, 'suspended' => true, 'guardian_verified' => false
);
assert_closed( LSCH_Dependencies::claims( 7 ), 'Denied canonical identity cannot be promoted' );
SMC_Contracts::$assertions = array(
    'contract_version' => '1.2.2', 'approved' => true,
    'eligible' => true, 'suspended' => false, 'guardian_verified' => true,
    'identity_documents_current' => true
);
$valid = LSCH_Dependencies::claims( 7 );
if ( empty( $valid['approved'] ) || empty( $valid['eligible'] ) ||
    ! empty( $valid['suspended'] ) || empty( $valid['guardian_verified'] ) ||
    ! empty( $valid['founder'] ) || 0 !== $GLOBALS['override_attempts'] ) {
    fwrite( STDERR, "FAIL: Canonical File00 claims changed by local hook.\\n" ); exit( 1 );
}
echo "PASS: File00 claims remain authoritative; unavailable, stale and denied states fail closed.\\n";
