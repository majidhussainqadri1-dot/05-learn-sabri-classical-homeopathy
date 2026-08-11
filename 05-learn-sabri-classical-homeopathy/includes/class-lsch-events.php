<?php
/** Reliable outbox/inbox and background job helpers. */
defined( 'ABSPATH' ) || exit;

final class LSCH_Events {
	private static $request_failures = array();
	private static $transaction_buffering = false;
	private static $deferred_events = array();
	private static $deferred_audits = array();

	public static function begin_transaction_buffer() { self::$transaction_buffering = true; self::$deferred_events = array(); self::$deferred_audits = array(); }
	public static function discard_transaction_buffer() { self::$transaction_buffering = false; self::$deferred_events = array(); self::$deferred_audits = array(); }
	public static function flush_transaction_buffer() {
		$events = self::$deferred_events; $audits = self::$deferred_audits; self::discard_transaction_buffer();
		foreach ( $events as $item ) { try { do_action( 'lsch_event_published', $item['id'], $item['name'], $item['aggregate_type'], $item['aggregate_id'], $item['payload'] ); } catch ( Throwable $e ) { error_log( 'File05 post-commit event projection failed.' ); } }
		foreach ( $audits as $item ) { try { LSCH_Dependencies::audit( $item['action'], $item['context'] ); } catch ( Throwable $e ) { error_log( 'File05 post-commit audit forwarding failed.' ); } }
	}

	public static function reset_request_integrity() { self::$request_failures = array(); }
	public static function request_integrity_error() { return self::$request_failures ? self::$request_failures[0] : ''; }
	private static function mark_request_failure( $code ) { self::$request_failures[] = sanitize_key( $code ); }
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
		if ( ! $ok ) { self::mark_request_failure( 'outbox_persist_failed' ); }
		if ( $ok ) {
			$item = array( 'id' => $id, 'name' => sanitize_text_field( $name ), 'aggregate_type' => sanitize_key( $aggregate_type ), 'aggregate_id' => sanitize_text_field( (string) $aggregate_id ), 'payload' => $payload );
			if ( self::$transaction_buffering ) { self::$deferred_events[] = $item; } else { do_action( 'lsch_event_published', $item['id'], $item['name'], $item['aggregate_type'], $item['aggregate_id'], $item['payload'] ); }
		}
		return $ok ? $id : false;
	}

	public static function consume( $event_id, $event_name, array $payload, callable $handler ) {
		global $wpdb; $t = LSCH_Database::tables();
		$event_id = sanitize_text_field( $event_id );
		$payload_hash = hash( 'sha256', wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
		$lock_name = 'lsch:inbox:' . substr( hash( 'sha256', $event_id ), 0, 48 );
		if ( 1 !== (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s,3)', $lock_name ) ) ) { return false; }
		try {
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['inbox']} WHERE event_id=%s LIMIT 1", $event_id ), ARRAY_A );
			if ( $row && ! hash_equals( (string) $row['payload_hash'], $payload_hash ) ) { self::audit( 'inbox_payload_conflict', 'event', $event_id, array( 'event_name' => $event_name ), 'reliability' ); return false; }
			if ( $row && 'processed' === $row['status'] ) { return true; }
			$now = LSCH_Database::now();
			$sql = $wpdb->prepare( "INSERT INTO {$t['inbox']} (event_id,event_name,payload_hash,status,received_at) VALUES (%s,%s,%s,'processing',%s) ON DUPLICATE KEY UPDATE event_name=VALUES(event_name),payload_hash=VALUES(payload_hash),status='processing',received_at=VALUES(received_at)", $event_id, sanitize_text_field( $event_name ), $payload_hash, $now );
			if ( false === $wpdb->query( $sql ) ) { return false; }
			$result = call_user_func( $handler, $payload );
			if ( is_wp_error( $result ) || false === $result ) { $wpdb->update( $t['inbox'], array( 'status' => 'failed', 'received_at' => LSCH_Database::now() ), array( 'event_id' => $event_id ), array( '%s', '%s' ), array( '%s' ) ); return $result; }
			$updated = $wpdb->update( $t['inbox'], array( 'status' => 'processed', 'received_at' => LSCH_Database::now() ), array( 'event_id' => $event_id, 'payload_hash' => $payload_hash ), array( '%s', '%s' ), array( '%s', '%s' ) );
			return 1 === $updated || (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t['inbox']} WHERE event_id=%s AND status='processed'", $event_id ) );
		} finally {
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );
		}
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
		$queued = false !== $wpdb->query( $sql );
		if ( ! $queued ) { self::mark_request_failure( 'job_persist_failed' ); }
		return $queued;
	}

	public static function audit( $action, $object_type, $object_id, array $context = array(), $purpose = '' ) {
		global $wpdb;
		$t = LSCH_Database::tables();
		$trace = LSCH_Database::uuid();
		$context['request_trace_id'] = LSCH_Policy::request_id();
		$audit_ok = $wpdb->insert(
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
		if ( ! $audit_ok ) { self::mark_request_failure( 'audit_persist_failed' ); }
		if ( $audit_ok ) { $forward = array( 'action' => $action, 'context' => array_merge( $context, array( 'object_id' => $object_id, 'trace_id' => $context['request_trace_id'], 'audit_event_id' => $trace ) ) ); if ( self::$transaction_buffering ) { self::$deferred_audits[] = $forward; } else { LSCH_Dependencies::audit( $forward['action'], $forward['context'] ); } }
		return $audit_ok ? $trace : false;
	}
}
