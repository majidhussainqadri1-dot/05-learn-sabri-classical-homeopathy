<?php
/**
 * File 05 v12: at most five formative checks per lesson.
 *
 * This is not a summative assessment, credential or clinical decision. Expected
 * answers are never emitted by the read endpoint or embedded in lesson HTML.
 */
defined( 'ABSPATH' ) || exit;

final class LSCH_Quick_Checks {
	private static function invalid( $reason ) {
		return new WP_Error( 'lsch_quick_checks_invalid', __( 'The lesson quick checks require editorial repair.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409, 'reason' => $reason ) );
	}

	/** Normalize the complete private blueprint; reject rather than silently truncate. */
	public static function normalize( $raw ) {
		if ( '' === trim( (string) $raw ) ) { return array(); }
		if ( ! is_string( $raw ) || strlen( $raw ) > 25000 ) { return self::invalid( 'length' ); }
		$items = json_decode( $raw, true );
		if ( ! is_array( $items ) || array_values( $items ) !== $items || count( $items ) > 5 ) { return self::invalid( 'list_or_count' ); }
		$normalized = array();
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) || ! isset( $item['question'], $item['options'], $item['correct'] ) || ! is_string( $item['question'] ) || ! is_array( $item['options'] ) || ! is_scalar( $item['correct'] ) ) { return self::invalid( 'shape' ); }
			$question = sanitize_text_field( $item['question'] );
			if ( strlen( $question ) < 3 || strlen( $question ) > 600 || count( $item['options'] ) < 2 || count( $item['options'] ) > 5 ) { return self::invalid( 'question_or_options' ); }
			$options = array();
			foreach ( $item['options'] as $key => $label ) {
				if ( ! is_scalar( $label ) || ! is_scalar( $key ) || strlen( (string) $key ) > 20 ) { return self::invalid( 'option_type' ); }
				$text = sanitize_text_field( (string) $label );
				if ( '' === $text || strlen( $text ) > 400 ) { return self::invalid( 'option_text' ); }
				$options[ (string) $key ] = $text;
			}
			$correct = (string) $item['correct'];
			if ( ! array_key_exists( $correct, $options ) ) { return self::invalid( 'answer_key' ); }
			$explanation = isset( $item['explanation'] ) && is_scalar( $item['explanation'] ) ? sanitize_textarea_field( (string) $item['explanation'] ) : '';
			if ( strlen( $explanation ) > 1200 ) { return self::invalid( 'explanation_length' ); }
			$normalized[] = array(
				'question' => $question, 'options' => $options, 'correct' => $correct, 'explanation' => $explanation,
			);
		}
		return $normalized;
	}

	/** A read-only learner projection; correct answers and explanations stay server-side. */
	public static function questions( $lesson_id ) {
		$items = self::normalize( (string) get_post_meta( absint( $lesson_id ), '_lsch_quick_checks', true ) );
		if ( is_wp_error( $items ) ) { return $items; }
		$public = array();
		foreach ( $items as $index => $item ) {
			$options = array();
			foreach ( $item['options'] as $key => $label ) {
				$options[] = array( 'key' => (string) $key, 'label' => $label );
			}
			$public[] = array( 'index' => $index, 'question' => $item['question'], 'options' => $options );
		}
		return $public;
	}

	/** Formative feedback after submission; never changes course completion. */
	public static function grade( $lesson_id, array $answers ) {
		$items = self::normalize( (string) get_post_meta( absint( $lesson_id ), '_lsch_quick_checks', true ) );
		if ( is_wp_error( $items ) ) { return $items; }
		if ( ! $items ) { return new WP_Error( 'lsch_quick_checks_empty', __( 'No approved quick checks exist for this lesson.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) ); }
		if ( count( $answers ) > 5 ) { return new WP_Error( 'lsch_quick_checks_answers_invalid', __( 'Too many quick-check responses.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) ); }
		$correct = 0;
		$review = array();
		foreach ( $items as $index => $item ) {
			$submitted = $answers[ $index ] ?? '';
			if ( ! is_scalar( $submitted ) ) { return new WP_Error( 'lsch_quick_checks_answers_invalid', __( 'A quick-check response must be a single choice.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) ); }
			$okay = hash_equals( $item['correct'], (string) $submitted );
			if ( $okay ) { $correct++; }
			$review[] = array( 'index' => $index, 'correct' => $okay, 'explanation' => $item['explanation'] );
		}
		return array(
			'lesson_id' => absint( $lesson_id ),
			'total' => count( $items ),
			'correct' => $correct,
			'percent' => round( 100 * $correct / count( $items ), 2 ),
			'review' => $review,
			'credential_credit' => false,
		);
	}
}
