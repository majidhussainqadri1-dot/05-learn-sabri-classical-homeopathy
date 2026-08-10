<?php
/** Reliable outbox/inbox and background job helpers. */
defined( 'ABSPATH' ) || exit;

final class LSCH_Events {
	public static function publish( $name, $aggregate_type, $aggregate_id, array $payload ) {
		global $wpdb;
		$t   = LSCH_Database::tables();
		$id  = LSCH_Database::uuid();
		$now = LSCH_Database::now();
		$ok  = $wpdb->insert(
			$t['outbox'],
			array(
				'event_id'        => $id,
				'event_name'      => sanitize_text_field( $name ),
				'aggregate_type'  => sanitize_key( $aggregate_type ),
				'aggregate_id'    => sanitize_text_field( (string) $aggregate_id ),
				'payload_json'    => wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
				'status'          => 'pending',
				'attempts'        => 0,
				'next_attempt_at' => $now,
				'created_at'      => $now,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
		);
		if ( $ok ) {
			/**
			 * Local, post-persistence projection hook. Consumers MUST remain
			 * idempotent and may not treat this hook as canonical event storage.
			 */
			do_action( 'lsch_event_published', $id, sanitize_text_field( $name ), sanitize_key( $aggregate_type ), sanitize_text_field( (string) $aggregate_id ), $payload );
		}
		return $ok ? $id : false;
	}

	public static function consume( $event_id, $event_name, array $payload, callable $handler ) {
		global $wpdb;
		$t = LSCH_Database::tables();
		$event_id = sanitize_text_field( $event_id );
		if ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t['inbox']} WHERE event_id=%s", $event_id ) ) ) {
			return true;
		}
		$result = call_user_func( $handler, $payload );
		if ( is_wp_error( $result ) || false === $result ) {
			return $result;
		}
		return false !== $wpdb->insert( $t['inbox'], array( 'event_id' => $event_id, 'event_name' => sanitize_text_field( $event_name ), 'payload_hash' => hash( 'sha256', wp_json_encode( $payload ) ), 'status' => 'processed', 'received_at' => LSCH_Database::now() ), array( '%s', '%s', '%s', '%s', '%s' ) );
	}

	public static function enqueue( $type, array $payload, $run_after = null, $max_attempts = 5, $key = '' ) {
		global $wpdb;
		$t   = LSCH_Database::tables();
		$key = $key ? sanitize_text_field( $key ) : hash( 'sha256', $type . '|' . wp_json_encode( $payload ) );
		$now = LSCH_Database::now();
		$sql = $wpdb->prepare(
			"INSERT INTO {$t['jobs']} (job_key,job_type,payload_json,status,attempts,max_attempts,run_after,created_at,updated_at) VALUES (%s,%s,%s,'pending',0,%d,%s,%s,%s)
			ON DUPLICATE KEY UPDATE payload_json=VALUES(payload_json),run_after=LEAST(run_after,VALUES(run_after)),updated_at=VALUES(updated_at)",
			$key,
			sanitize_key( $type ),
			wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
			max( 1, absint( $max_attempts ) ),
			$run_after ? gmdate( 'Y-m-d H:i:s', strtotime( $run_after ) ) : $now,
			$now,
			$now
		);
		return false !== $wpdb->query( $sql );
	}

	public static function audit( $action, $object_type, $object_id, array $context = array(), $purpose = '' ) {
		global $wpdb;
		$t = LSCH_Database::tables();
		$trace = LSCH_Database::uuid();
		$wpdb->insert(
			$t['audit'],
			array(
				'trace_id'     => $trace,
				'actor_id'     => get_current_user_id(),
				'action'       => sanitize_key( $action ),
				'object_type'  => sanitize_key( $object_type ),
				'object_id'    => sanitize_text_field( (string) $object_id ),
				'purpose'      => sanitize_key( $purpose ),
				'context_json' => wp_json_encode( $context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
				'created_at'   => LSCH_Database::now(),
			),
			array( '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
		LSCH_Dependencies::audit( $action, array_merge( $context, array( 'object_id' => $object_id, 'trace_id' => $trace ) ) );
		return $trace;
	}
}
