<?php
/**
 * Regression: the educational practice auto-grader must never turn a structured
 * response or malformed authored rubric into a successful scalar match.
 * Runs without WordPress on PHP 7.4+; only the private scoring routine is called.
 */
error_reporting( E_ALL );
set_error_handler(
	static function ( $severity, $message ) {
		if ( error_reporting() & $severity ) {
			throw new ErrorException( $message, 0, $severity );
		}
		return false;
	}
);
define( 'ABSPATH', __DIR__ . '/' );
function sanitize_key( $value ) {
	return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) );
}
function sanitize_text_field( $value ) {
	return trim( strip_tags( (string) $value ) );
}
require_once __DIR__ . '/../05-learn-sabri-classical-homeopathy/includes/class-lsch-future18.php';

$method = new ReflectionMethod( 'LSCH_Future18', 'auto_score' );
$method->setAccessible( true );
$score = static function ( $blueprint, $answers ) use ( $method ) {
	return $method->invoke( null, $blueprint, $answers );
};
$assert = static function ( $ok, $description ) {
	if ( ! $ok ) {
		throw new RuntimeException( 'Future-18 score safety: ' . $description );
	}
};
$scalar = array( 'criteria' => array( array( 'key' => 'answer', 'expected' => 'yes', 'weight' => 2 ) ) );
$valid = $score( $scalar, array( 'answer' => 'YES' ) );
$assert( false === $valid['manual'] && 100.0 === (float) $valid['score'], 'valid scalar answer must score 100' );
$wrong = $score( $scalar, array( 'answer' => 'no' ) );
$assert( false === $wrong['manual'] && 0.0 === (float) $wrong['score'], 'wrong scalar answer must score zero' );
$choices = $score( array( 'criteria' => array( array( 'key' => 'answer', 'expected' => array( 'yes', 'sure' ) ) ), array( 'answer' => 'SURE' ) );
$assert( false === $choices['manual'] && 100.0 === (float) $choices['score'], 'valid multi-choice scalar answer must still work' );
$array_attack = $score( array( 'criteria' => array( array( 'key' => 'answer', 'expected' => 'Array' ) ) ), array( 'answer' => array( 'ungraded' ) ) );
$assert( true === $array_attack['manual'] && 0.0 === (float) $array_attack['score'], 'array answer must never stringify to Array and earn 100' );
$nested_rubric = $score( array( 'criteria' => array( array( 'key' => 'answer', 'expected' => array( array( 'nested' ) ) ) ) ), array( 'answer' => 'Array' ) );
$assert( true === $nested_rubric['manual'] && 0.0 === (float) $nested_rubric['score'], 'nested expected values must not earn automatic marks' );
$invalid_key = $score( array( 'criteria' => array( array( 'key' => array( 'answer' ), 'expected' => 'yes' ) ) ), array( 'answer' => 'yes' ) );
$assert( true === $invalid_key['manual'], 'malformed key must be marked for manual review' );
$invalid_weight = $score( array( 'criteria' => array( array( 'key' => 'answer', 'expected' => 'yes', 'weight' => array( 2 ) ) ) ), array( 'answer' => 'yes' ) );
$assert( true === $invalid_weight['manual'], 'invalid weight must not auto-grade' );
$no_rubric = $score( array(), array( 'answer' => 'yes' ) );
$assert( true === $no_rubric['manual'], 'missing rubric requires manual grading' );
echo "PASS: Future-18 scalar grading and malformed/structured response fail-closed tests\n";
