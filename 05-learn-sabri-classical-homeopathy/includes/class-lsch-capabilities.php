<?php
/** Least-privilege capability model. */
defined( 'ABSPATH' ) || exit;

final class LSCH_Capabilities {
	const MANAGE_CURRICULUM = 'lsch_manage_curriculum';
	const PUBLISH_LESSONS   = 'lsch_publish_lessons';
	const REVIEW_LESSONS    = 'lsch_review_lessons';
	const TEACH              = 'lsch_teach_courses';
	const ASSESS             = 'lsch_assess_learning';
	const OPERATE            = 'lsch_operate_learning';
	const VIEW_ANALYTICS     = 'lsch_view_learning_analytics';

	public static function all() {
		$caps = array( self::MANAGE_CURRICULUM, self::PUBLISH_LESSONS, self::REVIEW_LESSONS, self::TEACH, self::ASSESS, self::OPERATE, self::VIEW_ANALYTICS );
		foreach ( array( 'lsch_program', 'lsch_course', 'lsch_book', 'lsch_lesson', 'lsch_assessment', 'lsch_assignment', 'lsch_cohort' ) as $type ) {
			$caps = array_merge( $caps, array_values( self::post_type_caps( $type, $type . 's' ) ) );
		}
		return array_values( array_unique( $caps ) );
	}

	public static function post_type_caps( $singular, $plural ) {
		return array(
			'edit_post' => "edit_{$singular}", 'read_post' => "read_{$singular}", 'delete_post' => "delete_{$singular}",
			'edit_posts' => "edit_{$plural}", 'edit_others_posts' => "edit_others_{$plural}", 'publish_posts' => "publish_{$plural}",
			'read_private_posts' => "read_private_{$plural}", 'delete_posts' => "delete_{$plural}", 'delete_private_posts' => "delete_private_{$plural}",
			'delete_published_posts' => "delete_published_{$plural}", 'delete_others_posts' => "delete_others_{$plural}",
			'edit_private_posts' => "edit_private_{$plural}", 'edit_published_posts' => "edit_published_{$plural}", 'create_posts' => "create_{$plural}",
		);
	}

	public static function install() {
		$administrator = get_role( 'administrator' );
		if ( ! $administrator ) {
			throw new RuntimeException( 'Administrator role unavailable.' );
		}
		foreach ( self::all() as $capability ) {
			$administrator->add_cap( $capability );
		}
		$editor = get_role( 'editor' );
		if ( $editor ) {
			$editor->remove_cap( self::MANAGE_CURRICULUM );
			$editor->remove_cap( self::OPERATE );
		}
	}

	public static function remove_legacy_caps() {
		if ( ! function_exists( 'wp_roles' ) ) {
			return;
		}
		foreach ( array_keys( (array) wp_roles()->roles ) as $role_name ) {
			$role = get_role( $role_name );
			if ( $role ) {
				$role->remove_cap( 'manage_sabri_learning' );
				$role->remove_cap( 'slc_manage_learning' );
				$role->remove_cap( 'slc_review_lessons' );
			}
		}
	}

	public static function is_founder( $user_id = 0 ) {
		$claims = LSCH_Dependencies::claims( $user_id );
		return ! empty( $claims['founder'] );
	}

	public static function verified_doctor( $user_id = 0 ) {
		$claims = LSCH_Dependencies::claims( $user_id );
		return ! empty( $claims['doctor_verified'] ) && ! empty( $claims['identity_verified'] ) && ! empty( $claims['email_verified'] ) && ! empty( $claims['mobile_verified'] ) && ! empty( $claims['two_factor'] ) && $claims['approval_version'] >= 1 && in_array( $claims['status'], array( 'approved', 'verified' ), true ) && empty( $claims['suspended'] );
	}

	public static function can_author( $user_id = 0 ) {
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
		return $user_id && ( self::is_founder( $user_id ) || user_can( $user_id, self::PUBLISH_LESSONS ) || self::verified_doctor( $user_id ) );
	}

	public static function can_review( $user_id = 0 ) {
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
		return $user_id && ( self::is_founder( $user_id ) || user_can( $user_id, self::REVIEW_LESSONS ) );
	}

	public static function approved_account( $user_id = 0 ) {
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}
		if ( user_can( $user_id, 'manage_options' ) || self::is_founder( $user_id ) ) {
			return true;
		}
		$claims = LSCH_Dependencies::claims( $user_id );
		return in_array( $claims['status'], array( 'approved', 'verified' ), true ) && ! empty( $claims['identity_verified'] ) && ! empty( $claims['email_verified'] ) && ! empty( $claims['mobile_verified'] ) && empty( $claims['suspended'] );
	}

	public static function guardian_gate_passes( $user_id = 0 ) {
		$claims = LSCH_Dependencies::claims( $user_id );
		$age    = absint( $claims['age'] );
		if ( ! $age ) {
			return true;
		}
		$threshold = 'female' === $claims['gender'] ? 12 : 15;
		if ( $age < $threshold || $age < 18 ) {
			return ! empty( $claims['guardian_verified'] );
		}
		return true;
	}
}
