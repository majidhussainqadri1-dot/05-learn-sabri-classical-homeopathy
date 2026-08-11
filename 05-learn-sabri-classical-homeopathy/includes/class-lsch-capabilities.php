<?php
/** Least-privilege capability model backed by File 00 public assertions. */
defined( 'ABSPATH' ) || exit;

final class LSCH_Capabilities {
	private static $filtering_user_caps = false;
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
		return
			! empty( $claims['doctor_verified'] ) &&
			! empty( $claims['eligible'] ) &&
			empty( $claims['suspended'] );
	}

	public static function can_author( $user_id = 0 ) {
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}
		$claims = LSCH_Dependencies::claims( $user_id );
		if ( empty( $claims['eligible'] ) || ! empty( $claims['suspended'] ) ) {
			return self::is_founder( $user_id );
		}
		$publishing = (array) ( $claims['publishing'] ?? array() );
		return
			self::is_founder( $user_id ) ||
			user_can( $user_id, self::PUBLISH_LESSONS ) ||
			self::verified_doctor( $user_id ) ||
			! empty( $publishing['can_submit_for_review'] );
	}

	public static function can_review( $user_id = 0 ) {
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}
		$claims = LSCH_Dependencies::claims( $user_id );
		return
			self::is_founder( $user_id ) ||
			( empty( $claims['suspended'] ) && ! empty( $claims['eligible'] ) && user_can( $user_id, self::REVIEW_LESSONS ) );
	}

	public static function approved_account( $user_id = 0 ) {
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}
		if ( self::is_founder( $user_id ) ) {
			return true;
		}
		$claims = LSCH_Dependencies::claims( $user_id );
		return ! empty( $claims['approved'] ) && ! empty( $claims['eligible'] ) && empty( $claims['suspended'] );
	}



	private static function active_staff_rows( $user_id ) {
		global $wpdb; $t = LSCH_Database::tables();
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT object_type,object_id,role FROM {$t['staff']} WHERE user_id=%d AND active=1 AND conflict_status='clear' ORDER BY id ASC LIMIT 250", absint( $user_id ) ), ARRAY_A );
		return is_array( $rows ) ? $rows : array();
	}

	private static function grant( array $allcaps, array $capabilities ) {
		foreach ( $capabilities as $capability ) { $allcaps[ $capability ] = true; }
		return $allcaps;
	}

	private static function row_matches( array $row, $role, $type = '', $id = null ) {
		if ( sanitize_key( $row['role'] ) !== sanitize_key( $role ) ) { return false; }
		if ( '' !== $type && sanitize_key( $row['object_type'] ) !== sanitize_key( $type ) ) { return false; }
		return null === $id || absint( $row['object_id'] ) === absint( $id );
	}

	/** Remove File 05 domain capabilities when current File 00 assertions are not usable. */
	public static function filter_user_caps( $allcaps, $caps, $args, $user ) {
		if ( self::$filtering_user_caps || ! $user instanceof WP_User || ! $user->ID ) { return $allcaps; }
		self::$filtering_user_caps = true;
		$claims = LSCH_Dependencies::claims( $user->ID );
		self::$filtering_user_caps = false;
		$domain = self::all();
		$founder = ! empty( $claims['founder'] );
		$allowed = $founder || ( ! empty( $claims['approved'] ) && ! empty( $claims['eligible'] ) && empty( $claims['suspended'] ) && ! empty( $claims['guardian_verified'] ) );
		if ( ! $allowed ) {
			foreach ( $domain as $capability ) {
				if ( self::OPERATE === $capability && ! empty( $allcaps['manage_options'] ) ) { continue; }
				unset( $allcaps[ $capability ] );
			}
			return $allcaps;
		}
		if ( $founder ) { return self::grant( $allcaps, $domain ); }
		$rows = self::active_staff_rows( $user->ID );
		foreach ( $rows as $row ) {
			switch ( sanitize_key( $row['role'] ) ) {
				case 'teacher': $allcaps = self::grant( $allcaps, array( self::TEACH, self::VIEW_ANALYTICS ) ); break;
				case 'assessor': $allcaps = self::grant( $allcaps, array( self::ASSESS ) ); break;
				case 'reviewer': $allcaps = self::grant( $allcaps, array( self::REVIEW_LESSONS ) ); break;
				case 'curriculum_lead':
					if ( 'platform' === sanitize_key( $row['object_type'] ) && 0 === absint( $row['object_id'] ) ) { $allcaps = self::grant( $allcaps, $domain ); }
					break;
			}
		}
		$requested = isset( $args[0] ) ? (string) $args[0] : '';
		$object_id = isset( $args[2] ) ? absint( $args[2] ) : 0;
		if ( $object_id && in_array( $requested, array( 'read_post', 'edit_post' ), true ) ) {
			$type = get_post_type( $object_id );
			foreach ( $rows as $row ) {
				$semantic = LSCH_Content::LESSON === $type ? 'lesson' : ( LSCH_Content::ASSIGNMENT === $type ? 'assignment' : ( LSCH_Content::ASSESSMENT === $type ? 'assessment' : '' ) );
				$scoped = ( 'lesson' === $semantic && self::row_matches( $row, 'teacher', 'lesson', $object_id ) ) ||
					( 'read_post' === $requested && 'lesson' === $semantic && self::row_matches( $row, 'reviewer', 'lesson', $object_id ) ) ||
					( 'read_post' === $requested && in_array( $semantic, array( 'assignment', 'assessment' ), true ) && self::row_matches( $row, 'assessor', $semantic, $object_id ) );
				if ( $scoped ) { foreach ( (array) $caps as $primitive ) { $allcaps[ $primitive ] = true; } break; }
			}
		}
		return $allcaps;
	}

	public static function guardian_gate_passes( $user_id = 0 ) {
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}
		if ( self::is_founder( $user_id ) ) {
			return true;
		}
		$claims = LSCH_Dependencies::claims( $user_id );
		return ! empty( $claims['guardian_verified'] ) && ! empty( $claims['eligible'] ) && empty( $claims['suspended'] );
	}
}
