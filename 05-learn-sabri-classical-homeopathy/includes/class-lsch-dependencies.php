<?php
/** Platform dependency and audit adapters. */
defined( 'ABSPATH' ) || exit;

final class LSCH_Dependencies {
	/** Mandatory contracts. File 00/01/20 remain the authoritative owners. */
	public static function missing() {
		$missing = array();
		if ( ! defined( 'SMC_VERSION' ) || ! function_exists( 'smc_user_status' ) ) {
			$missing['file00'] = __( 'File 00 — Sabri Membership Core', 'learn-sabri-classical-homeopathy' );
		}
		if ( ! defined( 'SPF_VERSION' ) ) {
			$missing['file01'] = __( 'File 01 — Sabri Platform Foundation', 'learn-sabri-classical-homeopathy' );
		}
		if ( ! defined( 'SABRI_SHELL_VERSION' ) ) {
			$missing['file20'] = __( 'File 20 — Sabri Unified Application Shell', 'learn-sabri-classical-homeopathy' );
		}
		return (array) apply_filters( 'lsch_missing_dependencies', $missing );
	}

	public static function runtime_ready() {
		return array() === self::missing();
	}

	public static function activation_preflight() {
		$missing = self::missing();
		if ( $missing ) {
			return new WP_Error( 'lsch_missing_dependencies', sprintf( __( 'File 05 cannot activate until these contracts are available: %s.', 'learn-sabri-classical-homeopathy' ), implode( ', ', $missing ) ) );
		}
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			return new WP_Error( 'lsch_php_version', __( 'PHP 7.4 or later is required.', 'learn-sabri-classical-homeopathy' ) );
		}
		return true;
	}

	/** Return File 00 claims without treating role labels as authorization. */
	public static function claims( $user_id = 0 ) {
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
		$claims  = array(
			'user_id'           => $user_id,
			'status'            => $user_id && function_exists( 'smc_user_status' ) ? (string) smc_user_status( $user_id ) : '',
			'account_type'      => $user_id && function_exists( 'smc_profile_value' ) ? (string) smc_profile_value( $user_id, 'account_type' ) : '',
			'founder'           => $user_id && function_exists( 'smc_is_founder' ) ? (bool) smc_is_founder( $user_id ) : false,
			'identity_verified' => $user_id ? (bool) get_user_meta( $user_id, '_smc_identity_verified', true ) : false,
			'doctor_verified'   => $user_id ? (bool) get_user_meta( $user_id, '_smc_doctor_verified', true ) : false,
			'email_verified'    => $user_id ? (bool) get_user_meta( $user_id, '_smc_email_verified', true ) : false,
			'mobile_verified'   => $user_id ? (bool) get_user_meta( $user_id, '_smc_mobile_verified', true ) : false,
			'two_factor'        => $user_id ? (bool) get_user_meta( $user_id, '_smc_2fa_enabled', true ) : false,
			'approval_version'  => $user_id ? absint( get_user_meta( $user_id, '_smc_approval_version', true ) ) : 0,
			'guardian_verified' => $user_id ? (bool) get_user_meta( $user_id, '_smc_guardian_verified', true ) : false,
			'age'               => $user_id ? absint( get_user_meta( $user_id, '_smc_age', true ) ) : 0,
			'gender'            => $user_id ? sanitize_key( (string) get_user_meta( $user_id, '_smc_gender', true ) ) : '',
			'suspended'         => $user_id ? in_array( (string) smc_user_status( $user_id ), array( 'suspended', 'blocked', 'rejected' ), true ) : false,
		);
		return (array) apply_filters( 'lsch_user_claims', $claims, $user_id );
	}

	public static function register_admin_notice() {
		add_action(
			'admin_notices',
			static function() {
				if ( ! current_user_can( 'activate_plugins' ) ) {
					return;
				}
				printf( '<div class="notice notice-error"><p><strong>%1$s</strong> %2$s</p></div>', esc_html__( 'File 05 is safely paused.', 'learn-sabri-classical-homeopathy' ), esc_html( sprintf( __( 'Repair or activate: %s.', 'learn-sabri-classical-homeopathy' ), implode( ', ', LSCH_Dependencies::missing() ) ) ) );
			}
		);
	}

	public static function register_runtime_failure_notice() {
		add_action(
			'admin_notices',
			static function() {
				if ( current_user_can( 'activate_plugins' ) ) {
					echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'File 05 is paused because its schema upgrade did not complete.', 'learn-sabri-classical-homeopathy' ) . '</strong> ' . esc_html__( 'Open Learning → System Check before retrying.', 'learn-sabri-classical-homeopathy' ) . '</p></div>';
				}
			}
		);
	}

	public static function audit( $action, array $context = array() ) {
		$context = array_filter( $context, static function( $value ) { return ! is_resource( $value ); } );
		if ( class_exists( 'SMC_Security' ) && is_callable( array( 'SMC_Security', 'audit' ) ) ) {
			SMC_Security::audit( 'lsch_' . sanitize_key( $action ), isset( $context['user_id'] ) ? absint( $context['user_id'] ) : get_current_user_id(), 'lsch_learning', isset( $context['object_id'] ) ? absint( $context['object_id'] ) : 0, $context );
		}
		do_action( 'lsch_audit_event', sanitize_key( $action ), $context );
	}
}
