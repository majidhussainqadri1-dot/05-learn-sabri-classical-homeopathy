<?php
/**
 * Runtime dependency contract.
 *
 * @package SabriLearning
 */

defined( 'ABSPATH' ) || exit;

final class SLC_Dependencies {
	/**
	 * Return missing mandatory contracts.
	 *
	 * File 00 is the sole membership and verification authority. File 01 owns
	 * the platform Learn page. File 20 owns the global application shell.
	 *
	 * @return array<string,string>
	 */
	public static function missing() {
		$missing = array();

		if ( ! defined( 'SMC_VERSION' ) || ! function_exists( 'smc_get_profile' ) || ! function_exists( 'smc_user_status' ) || ! function_exists( 'smc_profile_value' ) || ! class_exists( 'SMC_Security' ) ) {
			$missing['file00'] = __( 'File 00 — Sabri Membership Core 1.0.1 or later', 'sabri-learning' );
		}

		if ( ! defined( 'SPF_VERSION' ) ) {
			$missing['file01'] = __( 'File 01 — Sabri Platform Foundation', 'sabri-learning' );
		}

		if ( ! defined( 'SABRI_SHELL_VERSION' ) ) {
			$missing['file20'] = __( 'File 20 — Sabri Unified Application Shell 1.0.0 or later', 'sabri-learning' );
		}

		return $missing;
	}

	/**
	 * Whether all mandatory contracts are available.
	 *
	 * @return bool
	 */
	public static function ready() {
		return array() === self::missing();
	}

	/**
	 * Validate activation preconditions and the Foundation-owned Learn page.
	 *
	 * @return true|WP_Error
	 */
	public static function activation_preflight() {
		$missing = self::missing();
		if ( $missing ) {
			return new WP_Error(
				'slc_missing_dependencies',
				sprintf(
					/* translators: %s: comma-separated dependency names. */
					__( 'File 05 cannot activate because these mandatory dependencies are unavailable: %s.', 'sabri-learning' ),
					implode( ', ', $missing )
				)
			);
		}

		$pages   = (array) get_option( 'spf_page_map', array() );
		$learn_id = isset( $pages['learn'] ) ? absint( $pages['learn'] ) : 0;
		if ( ! $learn_id || 'page' !== get_post_type( $learn_id ) || 'trash' === get_post_status( $learn_id ) ) {
			return new WP_Error(
				'slc_missing_learn_page',
				__( 'The File 01 Foundation-owned Learn page is missing or invalid. Repair File 01 before activating File 05.', 'sabri-learning' )
			);
		}

		return true;
	}

	/**
	 * Display an administrator-visible failure without running partial features.
	 */
	public static function register_failure_notice() {
		add_action(
			'admin_notices',
			static function() {
				if ( ! current_user_can( 'activate_plugins' ) ) {
					return;
				}
				$missing = SLC_Dependencies::missing();
				if ( ! $missing ) {
					return;
				}
				printf(
					'<div class="notice notice-error"><p><strong>%1$s</strong> %2$s</p></div>',
					esc_html__( 'Learn Sabri Classical Homeopathy is safely paused.', 'sabri-learning' ),
					esc_html( sprintf( __( 'Activate or repair: %s.', 'sabri-learning' ), implode( ', ', $missing ) ) )
				);
			}
		);
	}

	/** Display a migration/runtime failure without exposing sensitive detail. */
	public static function register_runtime_failure_notice() {
		add_action(
			'admin_notices',
			static function() {
				if ( ! current_user_can( 'activate_plugins' ) ) {
					return;
				}
				$failure = (array) get_option( 'slc_runtime_failure', array() );
				if ( empty( $failure['error'] ) ) {
					return;
				}
				echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'File 05 is safely paused because its database migration did not complete.', 'sabri-learning' ) . '</strong> ' . esc_html__( 'Review the recorded failure before retrying.', 'sabri-learning' ) . '</p></div>';
			}
		);
	}

	/**
	 * Record a File 05 security or workflow event in File 00 when supported.
	 *
	 * @param string $action Action key.
	 * @param array  $context Event context.
	 */
	public static function audit( $action, array $context = array() ) {
		if ( class_exists( 'SMC_Security' ) && is_callable( array( 'SMC_Security', 'audit' ) ) ) {
			$subject = isset( $context['user_id'] ) ? absint( $context['user_id'] ) : ( isset( $context['author_id'] ) ? absint( $context['author_id'] ) : 0 );
			$object  = isset( $context['lesson_id'] ) ? absint( $context['lesson_id'] ) : 0;
			SMC_Security::audit( 'slc_' . sanitize_key( $action ), $subject, $object ? 'slc_lesson' : 'slc_system', $object, $context );
		}
	}
}
