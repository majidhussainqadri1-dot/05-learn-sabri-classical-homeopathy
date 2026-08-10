<?php
/**
 * Platform dependency, policy and audit adapters.
 *
 * File 05 consumes only public/versioned owner contracts. It never reads
 * File 00 private tables or user-meta implementation details.
 */
defined( 'ABSPATH' ) || exit;

final class LSCH_Dependencies {
	const MIN_SMC_CONTRACT = '1.2.2';

	/** Mandatory contracts. File 00/01/20 remain authoritative owners. */
	public static function missing() {
		$missing = array();

		if (
			! defined( 'SMC_VERSION' ) ||
			! defined( 'SMC_CONTRACT_VERSION' ) ||
			version_compare( (string) SMC_CONTRACT_VERSION, self::MIN_SMC_CONTRACT, '<' ) ||
			! class_exists( 'SMC_Contracts' ) ||
			! is_callable( array( 'SMC_Contracts', 'assertions' ) ) ||
			! function_exists( 'smc_policy' )
		) {
			$missing['file00'] = __( 'File 00 — Sabri Membership Core contract 1.2.2+', 'learn-sabri-classical-homeopathy' );
		}

		if ( ! isset( $missing['file00'] ) && ! self::governing_policy_ready() ) {
			$missing['file00-policy'] = __( 'File 00 — current single-free-tier / Sabri Green / File 26 policy contract', 'learn-sabri-classical-homeopathy' );
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
			return new WP_Error(
				'lsch_missing_dependencies',
				sprintf(
					__( 'File 05 cannot activate until these versioned contracts are available: %s.', 'learn-sabri-classical-homeopathy' ),
					implode( ', ', $missing )
				)
			);
		}
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			return new WP_Error( 'lsch_php_version', __( 'PHP 7.4 or later is required.', 'learn-sabri-classical-homeopathy' ) );
		}
		return true;
	}

	/** Current platform policy from File 00, normalized to the File 05 guardrails. */
	public static function platform_policy() {
		$policy = function_exists( 'smc_policy' ) ? (array) smc_policy() : array();
		$normalized = array(
			'single_free_tier'      => ! empty( $policy['single_free_tier'] ),
			'paid_unlocks_enabled'  => ! empty( $policy['paid_unlocks_enabled'] ),
			'legacy_pricing_enabled'=> ! empty( $policy['legacy_pricing_enabled'] ),
			'donation_optional'     => ! empty( $policy['donation_optional'] ),
			'free_baseline'          => ! empty( $policy['free_baseline'] ),
			'base_services'          => array_values( array_map( 'sanitize_key', (array) ( $policy['base_services'] ?? array() ) ) ),
			'donation_advantage'    =>
				! empty( $policy['donation_affects_rank'] ) ||
				! empty( $policy['donation_affects_entitlement'] ) ||
				! empty( $policy['donation_affects_capability'] ) ||
				! empty( $policy['donation_affects_visibility'] ) ||
				! empty( $policy['donation_affects_support'] ),
			'commission_percent'    => isset( $policy['commission_percent'] ) ? (float) $policy['commission_percent'] : null,
			'brand_primary'         => isset( $policy['brand_primary'] ) ? strtoupper( (string) $policy['brand_primary'] ) : '',
			'numbered_file_max'     => isset( $policy['numbered_file_max'] ) ? absint( $policy['numbered_file_max'] ) : 0,
			'search_discovery_owner'=> isset( $policy['search_discovery_owner'] ) ? sanitize_key( (string) $policy['search_discovery_owner'] ) : '',
		);
		return (array) apply_filters( 'lsch_platform_policy', $normalized, $policy );
	}

	/** Validate central-plan business/design/search invariants at runtime. */
	public static function governing_policy_ready() {
		$policy = self::platform_policy();
		return
			! empty( $policy['single_free_tier'] ) &&
			! empty( $policy['free_baseline'] ) &&
			! empty( $policy['donation_optional'] ) &&
			in_array( 'education', (array) $policy['base_services'], true ) &&
			in_array( 'ai', (array) $policy['base_services'], true ) &&
			empty( $policy['paid_unlocks_enabled'] ) &&
			empty( $policy['legacy_pricing_enabled'] ) &&
			empty( $policy['donation_advantage'] ) &&
			0.0 === (float) $policy['commission_percent'] &&
			'#087A4E' === (string) $policy['brand_primary'] &&
			26 <= (int) $policy['numbered_file_max'] &&
			'file26' === (string) $policy['search_discovery_owner'];
	}

	/**
	 * Return normalized File 00 assertions.
	 *
	 * No File 00 private meta/table key is consumed here. Unknown or malformed
	 * contract state is fail-closed for protected learning actions.
	 */
	public static function claims( $user_id = 0 ) {
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
		$closed = array(
			'contract_version'          => '',
			'user_id'                   => $user_id,
			'status'                    => '',
			'account_type'              => '',
			'account_class'             => '',
			'founder'                   => false,
			'institutional_account'     => false,
			'approved'                  => false,
			'eligible'                  => false,
			'suspended'                 => true,
			'guardian_verified'         => false,
			'identity_verified'         => false,
			'email_verified'            => false,
			'mobile_verified'           => false,
			'professional_verified'     => false,
			'doctor_verified'           => false,
			'approved_membership_types' => array(),
			'entitlements'              => array(),
			'publishing'                => array(),
		);

		if (
			! $user_id ||
			! defined( 'SMC_CONTRACT_VERSION' ) ||
			version_compare( (string) SMC_CONTRACT_VERSION, self::MIN_SMC_CONTRACT, '<' ) ||
			! class_exists( 'SMC_Contracts' ) ||
			! is_callable( array( 'SMC_Contracts', 'assertions' ) )
		) {
			return (array) apply_filters( 'lsch_user_claims', $closed, $user_id );
		}

		try {
			$source = (array) SMC_Contracts::assertions( $user_id );
		} catch ( Throwable $error ) {
			self::audit( 'file00_assertions_failed', array( 'user_id' => $user_id, 'error_class' => get_class( $error ) ) );
			return (array) apply_filters( 'lsch_user_claims', $closed, $user_id );
		}

		$contract = isset( $source['contract_version'] ) ? (string) $source['contract_version'] : '';
		if ( '' === $contract || version_compare( $contract, self::MIN_SMC_CONTRACT, '<' ) ) {
			return (array) apply_filters( 'lsch_user_claims', $closed, $user_id );
		}

		$types = array_values( array_filter( array_map( 'sanitize_key', (array) ( $source['approved_membership_types'] ?? array() ) ) ) );
		$professional = ! empty( $source['professional_verified'] );
		$founder = function_exists( 'smc_is_founder' ) ? (bool) smc_is_founder( $user_id ) : false;

		$claims = array(
			'contract_version'          => $contract,
			'user_id'                   => $user_id,
			'status'                    => sanitize_key( (string) ( $source['status'] ?? '' ) ),
			'account_type'              => sanitize_key( (string) ( $source['membership_type'] ?? '' ) ),
			'account_class'             => sanitize_key( (string) ( $source['account_class'] ?? '' ) ),
			'founder'                   => $founder,
			'institutional_account'     => ! empty( $source['institutional_account'] ),
			'approved'                  => ! empty( $source['approved'] ),
			'eligible'                  => ! empty( $source['eligible'] ),
			'suspended'                 => ! empty( $source['suspended'] ),
			'guardian_verified'         => ! empty( $source['guardian_verified'] ),
			'identity_verified'         => ! empty( $source['identity_documents_current'] ),
			'email_verified'            => ! empty( $source['email_verified'] ),
			'mobile_verified'           => ! empty( $source['phone_verified'] ),
			'professional_verified'     => $professional,
			'doctor_verified'           => $professional && in_array( 'doctor', $types, true ),
			'approved_membership_types' => $types,
			'entitlements'              => (array) ( $source['entitlements'] ?? array() ),
			'publishing'                => (array) ( $source['publishing'] ?? array() ),
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
				printf(
					'<div class="notice notice-error"><p><strong>%1$s</strong> %2$s</p></div>',
					esc_html__( 'File 05 is safely paused.', 'learn-sabri-classical-homeopathy' ),
					esc_html(
						sprintf(
							__( 'Repair or activate compatible owner contracts: %s.', 'learn-sabri-classical-homeopathy' ),
							implode( ', ', LSCH_Dependencies::missing() )
						)
					)
				);
			}
		);
	}

	public static function register_runtime_failure_notice() {
		add_action(
			'admin_notices',
			static function() {
				if ( current_user_can( 'activate_plugins' ) ) {
					echo '<div class="notice notice-error"><p><strong>' .
						esc_html__( 'File 05 is paused because its protected runtime upgrade did not complete.', 'learn-sabri-classical-homeopathy' ) .
						'</strong> ' .
						esc_html__( 'Open Learning → System Check before retrying.', 'learn-sabri-classical-homeopathy' ) .
						'</p></div>';
				}
			}
		);
	}

	/** Publish a privacy-minimized audit event to File 00 and local listeners. */
	public static function audit( $action, array $context = array() ) {
		$context = array_filter(
			$context,
			static function( $value ) {
				return ! is_resource( $value );
			}
		);
		$user_id = isset( $context['user_id'] ) ? absint( $context['user_id'] ) : get_current_user_id();

		if ( class_exists( 'SMC_Security' ) && is_callable( array( 'SMC_Security', 'audit' ) ) ) {
			$smc_context = array(
				'domain'     => 'file05_learning',
				'object_id'  => isset( $context['object_id'] ) ? absint( $context['object_id'] ) : 0,
				'trace_id'   => isset( $context['trace_id'] ) ? sanitize_text_field( (string) $context['trace_id'] ) : '',
				'properties' => $context,
			);
			SMC_Security::audit( 'lsch_' . sanitize_key( $action ), $user_id, $smc_context );
		}

		do_action( 'lsch_audit_event', sanitize_key( $action ), $context );
	}
}
