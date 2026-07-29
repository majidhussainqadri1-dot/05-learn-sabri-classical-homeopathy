<?php
/**
 * File 00-authoritative permissions.
 *
 * @package SabriLearning
 */

defined( 'ABSPATH' ) || exit;

final class SLC_Permissions {
	const CAP_MANAGE = 'slc_manage_learning';
	const CAP_REVIEW = 'slc_review_lessons';

	/** Return the authoritative founder user ID. */
	public static function founder_id() {
		global $wpdb;
		return (int) $wpdb->get_var(
			"SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key='_smc_official_founder' AND meta_value NOT IN ('','0') ORDER BY user_id ASC LIMIT 1" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);
	}

	/** Whether the user is the File 00 official Founder. */
	public static function is_founder( $user_id = 0 ) {
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
		return $user_id && function_exists( 'smc_is_founder' ) && smc_is_founder( $user_id );
	}

	/**
	 * Whether File 00 currently regards a user as an eligible verified doctor.
	 * No role-only or File 03 fallback is permitted.
	 */
	public static function is_verified_doctor( $user_id = 0 ) {
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
		if ( ! $user_id || ! SLC_Dependencies::ready() ) {
			return false;
		}

		$profile = smc_get_profile( $user_id );
		$status  = smc_user_status( $user_id );
		return is_array( $profile )
			&& 'sabri_doctor' === ( isset( $profile['account_type'] ) ? $profile['account_type'] : '' )
			&& in_array( $status, array( 'approved', 'verified' ), true )
			&& (bool) get_user_meta( $user_id, '_smc_identity_verified', true )
			&& (bool) get_user_meta( $user_id, '_smc_doctor_verified', true )
			&& (bool) get_user_meta( $user_id, '_smc_email_verified', true )
			&& (bool) get_user_meta( $user_id, '_smc_mobile_verified', true )
			&& (bool) get_user_meta( $user_id, '_smc_2fa_enabled', true )
			&& (int) get_user_meta( $user_id, '_smc_approval_version', true ) >= 1;
	}

	/** Whether a user may submit via the governed front-end form. */
	public static function can_submit( $user_id = 0 ) {
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
		return $user_id && ( self::is_founder( $user_id ) || user_can( $user_id, self::CAP_MANAGE ) || self::is_verified_doctor( $user_id ) );
	}

	/** Initial publication status. */
	public static function initial_status( $user_id = 0 ) {
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
		return self::is_founder( $user_id ) || user_can( $user_id, self::CAP_MANAGE ) ? 'publish' : 'pending';
	}

	/** Public author label. */
	public static function label( $user_id ) {
		if ( self::is_founder( $user_id ) ) {
			return __( 'Verified Founder', 'sabri-learning' );
		}
		if ( self::is_verified_doctor( $user_id ) ) {
			return __( 'Verified Doctor Contributor', 'sabri-learning' );
		}
		return __( 'Former Contributor', 'sabri-learning' );
	}

	/** Public profile URL without depending on File 03. */
	public static function profile_url( $user_id ) {
		$user_id = absint( $user_id );
		if ( function_exists( 'smc_page_url' ) ) {
			$url = smc_page_url( 'sabri_profile', '/member-profile/' );
			return add_query_arg( 'member', $user_id, $url );
		}
		return get_author_posts_url( $user_id );
	}

	/** Custom post type capability map. */
	public static function post_type_caps( $singular, $plural ) {
		return array(
			'edit_post'              => "edit_{$singular}",
			'read_post'              => "read_{$singular}",
			'delete_post'            => "delete_{$singular}",
			'edit_posts'             => "edit_{$plural}",
			'edit_others_posts'      => "edit_others_{$plural}",
			'publish_posts'          => "publish_{$plural}",
			'read_private_posts'     => "read_private_{$plural}",
			'delete_posts'           => "delete_{$plural}",
			'delete_private_posts'   => "delete_private_{$plural}",
			'delete_published_posts' => "delete_published_{$plural}",
			'delete_others_posts'    => "delete_others_{$plural}",
			'edit_private_posts'     => "edit_private_{$plural}",
			'edit_published_posts'   => "edit_published_{$plural}",
			'create_posts'           => "create_{$plural}",
		);
	}

	/** All manager capabilities assigned only by activation/migration. */
	public static function manager_caps() {
		$caps = array( self::CAP_MANAGE, self::CAP_REVIEW );
		foreach ( array( array( 'slc_book', 'slc_books' ), array( 'slc_lesson', 'slc_lessons' ) ) as $pair ) {
			$caps = array_merge( $caps, array_values( self::post_type_caps( $pair[0], $pair[1] ) ) );
		}
		return array_values( array_unique( $caps ) );
	}

	/** Grant manager capabilities to administrators only. */
	public static function install_caps() {
		if ( function_exists( 'wp_roles' ) ) {
			$roles = wp_roles();
			foreach ( array_keys( (array) $roles->roles ) as $role_name ) {
				$role = get_role( $role_name );
				if ( $role ) {
					$role->remove_cap( 'manage_sabri_learning' );
				}
			}
		}
		$admin = get_role( 'administrator' );
		if ( ! $admin ) {
			throw new RuntimeException( 'The WordPress administrator role is unavailable.' );
		}
		foreach ( self::manager_caps() as $cap ) {
			$admin->add_cap( $cap );
		}
	}
}
