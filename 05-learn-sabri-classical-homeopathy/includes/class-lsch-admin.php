<?php
/** Administrative governance, review and system-check surfaces. */
defined( 'ABSPATH' ) || exit;

final class LSCH_Admin {
	public function hooks() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_post_lsch_run_repair', array( $this, 'run_repair' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'add_meta_boxes', array( $this, 'meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_governance_meta' ), 10, 2 );
		add_filter( 'manage_' . LSCH_Content::LESSON . '_posts_columns', array( $this, 'lesson_columns' ) );
		add_action( 'manage_' . LSCH_Content::LESSON . '_posts_custom_column', array( $this, 'lesson_column' ), 10, 2 );
	}

	public function menu() {
		add_menu_page( __( 'Learning', 'learn-sabri-classical-homeopathy' ), __( 'Learning', 'learn-sabri-classical-homeopathy' ), LSCH_Capabilities::MANAGE_CURRICULUM, 'lsch-learning', array( $this, 'overview' ), 'dashicons-welcome-learn-more', 29 );
		add_submenu_page( 'lsch-learning', __( 'Overview', 'learn-sabri-classical-homeopathy' ), __( 'Overview', 'learn-sabri-classical-homeopathy' ), LSCH_Capabilities::MANAGE_CURRICULUM, 'lsch-learning', array( $this, 'overview' ) );
		add_submenu_page( 'lsch-learning', __( 'Programs', 'learn-sabri-classical-homeopathy' ), __( 'Programs', 'learn-sabri-classical-homeopathy' ), LSCH_Capabilities::MANAGE_CURRICULUM, 'edit.php?post_type=' . LSCH_Content::PROGRAM );
		add_submenu_page( 'lsch-learning', __( 'Courses', 'learn-sabri-classical-homeopathy' ), __( 'Courses', 'learn-sabri-classical-homeopathy' ), LSCH_Capabilities::MANAGE_CURRICULUM, 'edit.php?post_type=' . LSCH_Content::COURSE );
		add_submenu_page( 'lsch-learning', __( 'Books', 'learn-sabri-classical-homeopathy' ), __( 'Books', 'learn-sabri-classical-homeopathy' ), LSCH_Capabilities::MANAGE_CURRICULUM, 'edit.php?post_type=' . LSCH_Content::BOOK );
		add_submenu_page( 'lsch-learning', __( 'Lessons', 'learn-sabri-classical-homeopathy' ), __( 'Lessons', 'learn-sabri-classical-homeopathy' ), LSCH_Capabilities::REVIEW_LESSONS, 'edit.php?post_type=' . LSCH_Content::LESSON );
		add_submenu_page( 'lsch-learning', __( 'Assessments', 'learn-sabri-classical-homeopathy' ), __( 'Assessments', 'learn-sabri-classical-homeopathy' ), LSCH_Capabilities::ASSESS, 'edit.php?post_type=' . LSCH_Content::ASSESSMENT );
		add_submenu_page( 'lsch-learning', __( 'Assignments', 'learn-sabri-classical-homeopathy' ), __( 'Assignments', 'learn-sabri-classical-homeopathy' ), LSCH_Capabilities::ASSESS, 'edit.php?post_type=' . LSCH_Content::ASSIGNMENT );
		add_submenu_page( 'lsch-learning', __( 'System Check', 'learn-sabri-classical-homeopathy' ), __( 'System Check', 'learn-sabri-classical-homeopathy' ), LSCH_Capabilities::OPERATE, 'lsch-system-check', array( $this, 'system_check' ) );
	}


	public function meta_boxes() {
		foreach ( array( LSCH_Content::PROGRAM, LSCH_Content::COURSE, LSCH_Content::BOOK, LSCH_Content::LESSON, LSCH_Content::ASSESSMENT, LSCH_Content::ASSIGNMENT, LSCH_Content::COHORT ) as $type ) {
			add_meta_box( 'lsch-governance', __( 'Learning governance and structure', 'learn-sabri-classical-homeopathy' ), array( $this, 'governance_meta_box' ), $type, 'normal', 'high' );
		}
	}

	public function governance_meta_box( $post ) {
		wp_nonce_field( 'lsch_save_governance_' . $post->ID, 'lsch_governance_nonce' );
		$fields = array(
			'_lsch_access' => array( 'label' => __( 'Access', 'learn-sabri-classical-homeopathy' ), 'type' => 'select', 'options' => array( 'public' => 'Public', 'account' => 'Approved account', 'restricted' => 'Restricted' ) ),
			'_lsch_language' => array( 'label' => __( 'Language / locale', 'learn-sabri-classical-homeopathy' ), 'type' => 'text' ),
			'_lsch_format' => array( 'label' => __( 'Format', 'learn-sabri-classical-homeopathy' ), 'type' => 'text' ),
			'_lsch_duration' => array( 'label' => __( 'Duration / study time', 'learn-sabri-classical-homeopathy' ), 'type' => 'text' ),
			'_lsch_program_id' => array( 'label' => __( 'Program ID', 'learn-sabri-classical-homeopathy' ), 'type' => 'number' ),
			'_lsch_course_id' => array( 'label' => __( 'Course ID', 'learn-sabri-classical-homeopathy' ), 'type' => 'number' ),
			'_lsch_book_id' => array( 'label' => __( 'Book ID', 'learn-sabri-classical-homeopathy' ), 'type' => 'number' ),
			'_lsch_lesson_id' => array( 'label' => __( 'Lesson ID', 'learn-sabri-classical-homeopathy' ), 'type' => 'number' ),
			'_lsch_teacher_id' => array( 'label' => __( 'Teacher ID', 'learn-sabri-classical-homeopathy' ), 'type' => 'number' ),
			'_lsch_reviewer_id' => array( 'label' => __( 'Independent reviewer ID', 'learn-sabri-classical-homeopathy' ), 'type' => 'number' ),
			'_lsch_objectives' => array( 'label' => __( 'Learning objectives', 'learn-sabri-classical-homeopathy' ), 'type' => 'textarea' ),
			'_lsch_prerequisites' => array( 'label' => __( 'Prerequisite IDs / rules', 'learn-sabri-classical-homeopathy' ), 'type' => 'textarea' ),
			'_lsch_equivalence' => array( 'label' => __( 'Equivalence rules', 'learn-sabri-classical-homeopathy' ), 'type' => 'textarea' ),
			'_lsch_key_terms' => array( 'label' => __( 'Key terms', 'learn-sabri-classical-homeopathy' ), 'type' => 'textarea' ),
			'_lsch_examples' => array( 'label' => __( 'Examples', 'learn-sabri-classical-homeopathy' ), 'type' => 'textarea' ),
			'_lsch_sources' => array( 'label' => __( 'Sources and references', 'learn-sabri-classical-homeopathy' ), 'type' => 'textarea' ),
			'_lsch_accessibility' => array( 'label' => __( 'Accessibility evidence / transcript / alt-text notes', 'learn-sabri-classical-homeopathy' ), 'type' => 'textarea' ),
			'_lsch_safety' => array( 'label' => __( 'Educational and medical safety statement', 'learn-sabri-classical-homeopathy' ), 'type' => 'textarea' ),
			'_lsch_required_components' => array( 'label' => __( 'Required components JSON', 'learn-sabri-classical-homeopathy' ), 'type' => 'textarea' ),
			'_lsch_questions' => array( 'label' => __( 'Assessment questions JSON', 'learn-sabri-classical-homeopathy' ), 'type' => 'textarea' ),
			'_lsch_blueprint' => array( 'label' => __( 'Assessment blueprint JSON', 'learn-sabri-classical-homeopathy' ), 'type' => 'textarea' ),
			'_lsch_rubric' => array( 'label' => __( 'Assignment rubric', 'learn-sabri-classical-homeopathy' ), 'type' => 'textarea' ),
			'_lsch_pass_mark' => array( 'label' => __( 'Pass mark (%)', 'learn-sabri-classical-homeopathy' ), 'type' => 'number' ),
			'_lsch_max_attempts' => array( 'label' => __( 'Maximum attempts', 'learn-sabri-classical-homeopathy' ), 'type' => 'number' ),
			'_lsch_time_limit' => array( 'label' => __( 'Time limit (minutes)', 'learn-sabri-classical-homeopathy' ), 'type' => 'number' ),
			'_lsch_required' => array( 'label' => __( 'Required record (1/0)', 'learn-sabri-classical-homeopathy' ), 'type' => 'number' ),
			'_lsch_certificate_jurisdiction' => array( 'label' => __( 'Certificate jurisdiction', 'learn-sabri-classical-homeopathy' ), 'type' => 'text' ),
			'_lsch_certificate_wording' => array( 'label' => __( 'Certificate jurisdiction wording', 'learn-sabri-classical-homeopathy' ), 'type' => 'textarea' ),
			'_lsch_certificate_wording_approved' => array( 'label' => __( 'Certificate wording approved (1/0)', 'learn-sabri-classical-homeopathy' ), 'type' => 'number' ),
		);
		echo '<div class="lsch-governance-fields">';
		foreach ( $fields as $key => $field ) {
			$value = get_post_meta( $post->ID, $key, true ); echo '<p><label for="' . esc_attr( $key ) . '"><strong>' . esc_html( $field['label'] ) . '</strong></label><br>';
			if ( 'textarea' === $field['type'] ) { echo '<textarea class="widefat" rows="4" id="' . esc_attr( $key ) . '" name="lsch_meta[' . esc_attr( $key ) . ']">' . esc_textarea( $value ) . '</textarea>'; }
			elseif ( 'select' === $field['type'] ) { echo '<select id="' . esc_attr( $key ) . '" name="lsch_meta[' . esc_attr( $key ) . ']">'; foreach ( $field['options'] as $option => $label ) { echo '<option value="' . esc_attr( $option ) . '" ' . selected( $value, $option, false ) . '>' . esc_html( $label ) . '</option>'; } echo '</select>'; }
			else { echo '<input class="widefat" type="' . esc_attr( $field['type'] ) . '" id="' . esc_attr( $key ) . '" name="lsch_meta[' . esc_attr( $key ) . ']" value="' . esc_attr( $value ) . '">'; }
			echo '</p>';
		}
		echo '</div>';
	}

	public function save_governance_meta( $post_id, $post ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || ! LSCH_Content::object_type( $post_id ) || empty( $_POST['lsch_governance_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lsch_governance_nonce'] ) ), 'lsch_save_governance_' . $post_id ) || ! LSCH_Policy::can_use_learning_actions() || ! current_user_can( 'edit_post', $post_id ) ) { return; }
		$input = isset( $_POST['lsch_meta'] ) && is_array( $_POST['lsch_meta'] ) ? wp_unslash( $_POST['lsch_meta'] ) : array();
		$integer_keys = array( '_lsch_program_id', '_lsch_course_id', '_lsch_book_id', '_lsch_lesson_id', '_lsch_teacher_id', '_lsch_reviewer_id', '_lsch_pass_mark', '_lsch_max_attempts', '_lsch_time_limit', '_lsch_required', '_lsch_certificate_wording_approved' );
		$json_keys = array( '_lsch_required_components', '_lsch_questions', '_lsch_blueprint' );
		$allowed_keys = array( '_lsch_access', '_lsch_language', '_lsch_format', '_lsch_duration', '_lsch_program_id', '_lsch_course_id', '_lsch_book_id', '_lsch_lesson_id', '_lsch_teacher_id', '_lsch_reviewer_id', '_lsch_objectives', '_lsch_prerequisites', '_lsch_equivalence', '_lsch_key_terms', '_lsch_examples', '_lsch_sources', '_lsch_accessibility', '_lsch_safety', '_lsch_required_components', '_lsch_questions', '_lsch_blueprint', '_lsch_rubric', '_lsch_pass_mark', '_lsch_max_attempts', '_lsch_time_limit', '_lsch_required', '_lsch_certificate_jurisdiction', '_lsch_certificate_wording', '_lsch_certificate_wording_approved' );
		$changed = false;
		foreach ( $input as $key => $value ) {
			$key = sanitize_key( $key ); if ( 0 !== strpos( $key, '_lsch_' ) || ! in_array( $key, $allowed_keys, true ) ) { continue; }
			if ( '_lsch_access' === $key ) { $value = sanitize_key( $value ); $value = in_array( $value, array( 'public', 'account', 'restricted' ), true ) ? $value : 'public'; }
			elseif ( in_array( $key, $integer_keys, true ) ) {
				$value = absint( $value );
				if ( '_lsch_pass_mark' === $key && ( $value < 1 || $value > 100 ) ) { continue; }
				if ( '_lsch_max_attempts' === $key && ( $value < 1 || $value > 50 ) ) { continue; }
				if ( '_lsch_time_limit' === $key && $value > 1440 ) { continue; }
				if ( in_array( $key, array( '_lsch_required', '_lsch_certificate_wording_approved' ), true ) && $value > 1 ) { continue; }
			}
			elseif ( in_array( $key, $json_keys, true ) ) {
				$decoded = json_decode( (string) $value, true ); if ( ! is_array( $decoded ) ) { continue; }
				if ( '_lsch_required_components' === $key ) { $normalized = array_values( array_unique( array_map( 'sanitize_key', $decoded ) ) ); if ( ! $normalized || array_diff( $normalized, array( 'content', 'assessment', 'assignment' ) ) ) { continue; } $decoded = $normalized; }
				if ( '_lsch_questions' === $key && count( $decoded ) > 200 ) { continue; }
				$value = wp_json_encode( $decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); if ( false === $value || strlen( $value ) > 1048576 ) { continue; }
			}
			else { $value = sanitize_textarea_field( $value ); }
			$current_value = get_post_meta( $post_id, $key, true );
			if ( (string) $current_value === (string) $value ) { continue; }
			update_post_meta( $post_id, $key, $value );
			$changed = true;
		}
		if ( $changed ) { LSCH_Content::bump_version( $post_id, 'governance_meta' ); }
		elseif ( ! get_post_meta( $post_id, '_lsch_version', true ) ) { update_post_meta( $post_id, '_lsch_version', 1 ); }
	}

	public function assets( $hook ) {
		if ( false !== strpos( $hook, 'lsch' ) || false !== strpos( $hook, LSCH_Content::LESSON ) ) {
			wp_enqueue_style( 'lsch-admin', LSCH_URL . 'assets/css/admin.css', array(), LSCH_VERSION );
		}
	}

	public function overview() {
		if ( ! current_user_can( LSCH_Capabilities::MANAGE_CURRICULUM ) ) { wp_die( esc_html__( 'Access denied.', 'learn-sabri-classical-homeopathy' ) ); }
		$counts = array();
		foreach ( array( LSCH_Content::PROGRAM, LSCH_Content::COURSE, LSCH_Content::BOOK, LSCH_Content::LESSON, LSCH_Content::ASSESSMENT, LSCH_Content::ASSIGNMENT ) as $type ) {
			$obj = wp_count_posts( $type ); $counts[ $type ] = array( 'publish' => isset( $obj->publish ) ? absint( $obj->publish ) : 0, 'draft' => isset( $obj->draft ) ? absint( $obj->draft ) : 0, 'pending' => isset( $obj->pending ) ? absint( $obj->pending ) : 0 );
		}
		$health = LSCH_Operations::system_check();
		?>
		<div class="wrap lsch-admin"><h1><?php esc_html_e( 'Learn Sabri Classical Homeopathy', 'learn-sabri-classical-homeopathy' ); ?></h1><p><?php esc_html_e( 'Canonical learning governance for curriculum, courses, lessons, assessments, assignments, progress, and completion records.', 'learn-sabri-classical-homeopathy' ); ?></p><div class="lsch-admin-grid"><?php foreach ( $counts as $type => $count ) : ?><section><h2><?php echo esc_html( get_post_type_object( $type )->labels->name ); ?></h2><p><strong><?php echo absint( $count['publish'] ); ?></strong> <?php esc_html_e( 'published', 'learn-sabri-classical-homeopathy' ); ?> · <strong><?php echo absint( $count['draft'] ); ?></strong> <?php esc_html_e( 'draft', 'learn-sabri-classical-homeopathy' ); ?> · <strong><?php echo absint( $count['pending'] ); ?></strong> <?php esc_html_e( 'pending', 'learn-sabri-classical-homeopathy' ); ?></p></section><?php endforeach; ?></div><section class="lsch-admin-panel"><h2><?php esc_html_e( 'Current release truth', 'learn-sabri-classical-homeopathy' ); ?></h2><ul><li><?php printf( esc_html__( 'Runtime version: %s', 'learn-sabri-classical-homeopathy' ), esc_html( LSCH_VERSION ) ); ?></li><li><?php printf( esc_html__( 'Schema version: %s', 'learn-sabri-classical-homeopathy' ), absint( LSCH_SCHEMA_VERSION ) ); ?></li><li><?php printf( esc_html__( 'Access model: %s', 'learn-sabri-classical-homeopathy' ), esc_html( LSCH_Policy::access_model() ) ); ?></li><li><?php printf( esc_html__( 'System health: %s', 'learn-sabri-classical-homeopathy' ), esc_html( $health['status'] ) ); ?></li></ul><p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=lsch-system-check' ) ); ?>"><?php esc_html_e( 'Open System Check', 'learn-sabri-classical-homeopathy' ); ?></a></p></section></div>
		<?php
	}
	public function system_check() {
		$can_repair = LSCH_Policy::can_use_learning_actions() && current_user_can( LSCH_Capabilities::OPERATE );
		$read_only_break_glass = current_user_can( 'manage_options' );
		if ( ! $can_repair && ! $read_only_break_glass ) { wp_die( esc_html__( 'Access denied.', 'learn-sabri-classical-homeopathy' ) ); }
		$report = LSCH_Operations::system_check();
		?>
		<div class="wrap lsch-admin"><h1><?php esc_html_e( 'File 05 System Check', 'learn-sabri-classical-homeopathy' ); ?></h1><p><?php esc_html_e( 'Read-first diagnostics. No companion module is modified.', 'learn-sabri-classical-homeopathy' ); ?></p><table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Check', 'learn-sabri-classical-homeopathy' ); ?></th><th><?php esc_html_e( 'Status', 'learn-sabri-classical-homeopathy' ); ?></th><th><?php esc_html_e( 'Detail', 'learn-sabri-classical-homeopathy' ); ?></th></tr></thead><tbody><?php foreach ( $report['checks'] as $name => $check ) : ?><tr><td><?php echo esc_html( $name ); ?></td><td><strong><?php echo esc_html( $check['status'] ); ?></strong></td><td><?php echo esc_html( $check['detail'] ); ?></td></tr><?php endforeach; ?></tbody></table>
		<?php if ( $can_repair ) : ?>
		<h2><?php esc_html_e( 'Safe repair', 'learn-sabri-classical-homeopathy' ); ?></h2><form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post"><?php wp_nonce_field( 'lsch_run_repair' ); ?><input type="hidden" name="action" value="lsch_run_repair"><p><label><?php esc_html_e( 'Reason / incident reference', 'learn-sabri-classical-homeopathy' ); ?><br><textarea name="reason" rows="3" class="large-text" required></textarea></label></p><p><label><input type="checkbox" name="dry_run" value="1" checked> <?php esc_html_e( 'Dry run only', 'learn-sabri-classical-homeopathy' ); ?></label></p><p><label><input type="checkbox" name="confirm" value="1"> <?php esc_html_e( 'I explicitly confirm a non-dry owner-scoped repair after verified step-up and reversible backup.', 'learn-sabri-classical-homeopathy' ); ?></label></p><button class="button"><?php esc_html_e( 'Run owner-scoped reconciliation', 'learn-sabri-classical-homeopathy' ); ?></button></form>
		<?php else : ?><p><strong><?php esc_html_e( 'Read-only break-glass view: repair actions are unavailable until current File 00 eligibility and a named File 05 operator grant are both valid.', 'learn-sabri-classical-homeopathy' ); ?></strong></p><?php endif; ?></div>
		<?php
	}
	public function run_repair() {
		if ( ! LSCH_Policy::can_use_learning_actions() || ! current_user_can( LSCH_Capabilities::OPERATE ) ) { wp_die( esc_html__( 'Access denied.', 'learn-sabri-classical-homeopathy' ) ); }
		check_admin_referer( 'lsch_run_repair' );
		$reason = isset( $_POST['reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['reason'] ) ) : '';
		if ( strlen( trim( $reason ) ) < 12 ) { wp_die( esc_html__( 'A substantive repair reason or incident reference is required.', 'learn-sabri-classical-homeopathy' ), '', array( 'response' => 400 ) ); }
		$dry = ! empty( $_POST['dry_run'] );
		$confirmed = ! empty( $_POST['confirm'] );
		$actor_id = get_current_user_id();
		$step_up = $dry ? true : ( true === apply_filters( 'lsch_repair_step_up_verified', false, $actor_id, $reason ) );
		$backup = $dry ? true : ( true === apply_filters( 'lsch_repair_backup_verified', false, $actor_id, $reason ) );
		if ( ! $dry && ( ! $confirmed || ! $step_up || ! $backup ) ) { wp_die( esc_html__( 'Non-dry repair requires explicit confirmation, verified step-up authorization and a verified reversible backup.', 'learn-sabri-classical-homeopathy' ), '', array( 'response' => 403 ) ); }
		$result = LSCH_Operations::repair( $dry );
		LSCH_Events::audit( 'system_repair', 'system', 'file05', array( 'dry_run' => $dry, 'confirmed' => $confirmed, 'step_up_verified' => $step_up, 'backup_verified' => $backup, 'reason' => $reason, 'result' => $result ), 'operations' );
		wp_safe_redirect( add_query_arg( 'repair', $dry ? 'dry-run-complete' : 'complete', admin_url( 'admin.php?page=lsch-system-check' ) ) ); exit;
	}




	public function lesson_columns( $columns ) { $columns['lsch_version'] = __( 'Learning version', 'learn-sabri-classical-homeopathy' ); $columns['lsch_access'] = __( 'Access', 'learn-sabri-classical-homeopathy' ); $columns['lsch_governance'] = __( 'Governance', 'learn-sabri-classical-homeopathy' ); return $columns; }
	public function lesson_column( $column, $post_id ) { if ( 'lsch_version' === $column ) { echo absint( LSCH_Content::version( $post_id ) ); } elseif ( 'lsch_access' === $column ) { echo esc_html( LSCH_Content::access( $post_id ) ); } elseif ( 'lsch_governance' === $column ) { echo esc_html( get_post_meta( $post_id, '_lsch_reviewer', true ) ? __( 'Reviewer recorded', 'learn-sabri-classical-homeopathy' ) : __( 'Reviewer missing', 'learn-sabri-classical-homeopathy' ) ); } }
}
