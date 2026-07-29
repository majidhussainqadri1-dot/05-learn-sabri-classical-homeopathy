<?php
/** Independent moderation and immutable audit history. */

defined( 'ABSPATH' ) || exit;

final class SLC_Admin {
	public function hooks() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_post_slc_review_lesson', array( $this, 'review' ) );
		add_action( 'admin_post_slc_withdraw_consent', array( $this, 'withdraw_consent' ) );
		add_action( 'admin_notices', array( $this, 'notice' ) );
	}

	public function menu() {
		add_menu_page( __( 'Learning Management', 'sabri-learning' ), __( 'Learning Management', 'sabri-learning' ), SLC_Permissions::CAP_REVIEW, 'sabri-learning', array( $this, 'dashboard' ), 'dashicons-welcome-learn-more', 29 );
		add_submenu_page( 'sabri-learning', __( 'Lesson Moderation', 'sabri-learning' ), __( 'Lesson Moderation', 'sabri-learning' ), SLC_Permissions::CAP_REVIEW, 'sabri-learning', array( $this, 'dashboard' ) );
		add_submenu_page( 'sabri-learning', __( 'Learning Books', 'sabri-learning' ), __( 'Books', 'sabri-learning' ), SLC_Permissions::CAP_MANAGE, 'edit.php?post_type=' . SLC_Content::BOOK );
		add_submenu_page( 'sabri-learning', __( 'All Lessons', 'sabri-learning' ), __( 'All Lessons', 'sabri-learning' ), SLC_Permissions::CAP_MANAGE, 'edit.php?post_type=' . SLC_Content::LESSON );
	}

	public function dashboard() {
		$this->guard();
		$view = isset( $_GET['lesson_state'] ) ? sanitize_key( wp_unslash( $_GET['lesson_state'] ) ) : 'submitted';
		if ( ! in_array( $view, array( 'submitted', 'published', 'rejected', 'hidden' ), true ) ) {
			$view = 'submitted';
		}
		$lessons = get_posts(
			array(
				'post_type'      => SLC_Content::LESSON,
				'post_status'    => array( 'pending', 'publish', 'draft', 'private' ),
				'posts_per_page' => 100,
				'orderby'        => 'modified',
				'order'          => 'DESC',
				'meta_key'       => '_slc_workflow_state',
				'meta_value'     => $view,
				'no_found_rows'  => true,
			)
		);
		?>
		<div class="wrap slc-admin">
			<h1><?php esc_html_e( 'Learning Management', 'sabri-learning' ); ?></h1>
			<p><?php esc_html_e( 'Review educational scope, references, American English, copyright, patient privacy, author eligibility, and medical safety.', 'sabri-learning' ); ?></p>
			<nav><?php foreach ( array( 'submitted' => __( 'Submitted', 'sabri-learning' ), 'published' => __( 'Published', 'sabri-learning' ), 'rejected' => __( 'Rejected', 'sabri-learning' ), 'hidden' => __( 'Hidden', 'sabri-learning' ) ) as $key => $label ) : ?><a class="<?php echo $view === $key ? 'is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => 'sabri-learning', 'lesson_state' => $key ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $label ); ?></a><?php endforeach; ?></nav>
			<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Lesson', 'sabri-learning' ); ?></th><th><?php esc_html_e( 'Classification', 'sabri-learning' ); ?></th><th><?php esc_html_e( 'Author', 'sabri-learning' ); ?></th><th><?php esc_html_e( 'Review', 'sabri-learning' ); ?></th></tr></thead><tbody>
			<?php if ( $lessons ) : foreach ( $lessons as $lesson ) : ?>
				<tr><td><strong><?php echo esc_html( $lesson->post_title ); ?></strong><p><?php echo esc_html( $lesson->post_excerpt ); ?></p><details><summary><?php esc_html_e( 'Review full lesson', 'sabri-learning' ); ?></summary><div class="slc-admin-content"><?php echo wp_kses_post( wpautop( $lesson->post_content ) ); ?><h4><?php esc_html_e( 'References', 'sabri-learning' ); ?></h4><?php echo nl2br( esc_html( get_post_meta( $lesson->ID, '_slc_references', true ) ) ); ?></div></details></td>
				<td><?php echo esc_html( SLC_Content::term( $lesson->ID, SLC_Content::TOPIC ) ); ?><br><?php echo esc_html( SLC_Content::term( $lesson->ID, SLC_Content::LEVEL ) ); ?><?php if ( 'patient-case-learning' === SLC_Content::term( $lesson->ID, SLC_Content::TOPIC, 'slug' ) ) : ?><br><strong><?php echo esc_html( $this->consent_status( $lesson->ID ) ); ?></strong><?php echo $this->withdrawal_form( $lesson->ID ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php endif; ?></td>
				<td><?php echo esc_html( get_the_author_meta( 'display_name', $lesson->post_author ) ); ?><br><?php echo esc_html( SLC_Permissions::label( $lesson->post_author ) ); ?></td>
				<td><?php echo $this->form( $lesson ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td></tr>
			<?php endforeach; else : ?><tr><td colspan="4"><?php esc_html_e( 'No lessons in this view.', 'sabri-learning' ); ?></td></tr><?php endif; ?>
			</tbody></table>
		</div>
		<?php
	}

	private function form( $lesson ) {
		$state   = (string) get_post_meta( $lesson->ID, '_slc_workflow_state', true );
		$version = max( 1, absint( get_post_meta( $lesson->ID, '_slc_row_version', true ) ) );
		$actions = array();
		if ( 'submitted' === $state ) {
			$actions = array( 'approve' => __( 'Approve and publish', 'sabri-learning' ), 'reject' => __( 'Reject to draft', 'sabri-learning' ), 'hide' => __( 'Hide lesson', 'sabri-learning' ) );
		} elseif ( 'published' === $state ) {
			$actions = array( 'hide' => __( 'Hide lesson', 'sabri-learning' ) );
		}
		if ( ! $actions ) {
			return '<em>' . esc_html__( 'No direct transition is available.', 'sabri-learning' ) . '</em>';
		}
		ob_start();
		?><form class="slc-review" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post"><input type="hidden" name="action" value="slc_review_lesson"><input type="hidden" name="lesson_id" value="<?php echo absint( $lesson->ID ); ?>"><input type="hidden" name="row_version" value="<?php echo absint( $version ); ?>"><?php wp_nonce_field( 'slc_review_' . $lesson->ID ); ?><select name="review_action"><?php foreach ( $actions as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select><textarea name="note" rows="3" placeholder="<?php esc_attr_e( 'Required for rejection or hiding; recommended for approval', 'sabri-learning' ); ?>"></textarea><button class="button button-primary" type="submit"><?php esc_html_e( 'Apply', 'sabri-learning' ); ?></button></form><?php
		return ob_get_clean();
	}

	public function review() {
		$this->guard();
		$id       = isset( $_POST['lesson_id'] ) ? absint( $_POST['lesson_id'] ) : 0;
		$expected = isset( $_POST['row_version'] ) ? absint( $_POST['row_version'] ) : 0;
		check_admin_referer( 'slc_review_' . $id );

		if ( ! SLC_Database::allow( 'review:' . get_current_user_id(), 60, HOUR_IN_SECONDS ) ) {
			wp_die( esc_html__( 'The moderation action limit has been reached.', 'sabri-learning' ), '', array( 'response' => 429 ) );
		}

		$lesson = get_post( $id );
		$action = isset( $_POST['review_action'] ) ? sanitize_key( wp_unslash( $_POST['review_action'] ) ) : '';
		$note   = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';
		if ( ! $lesson || SLC_Content::LESSON !== $lesson->post_type || ! in_array( $action, array( 'approve', 'reject', 'hide' ), true ) ) {
			wp_die( esc_html__( 'Invalid review request.', 'sabri-learning' ), '', array( 'response' => 400 ) );
		}
		if ( (int) $lesson->post_author === get_current_user_id() ) {
			wp_die( esc_html__( 'A lesson author cannot review their own submission.', 'sabri-learning' ), '', array( 'response' => 409 ) );
		}
		if ( in_array( $action, array( 'reject', 'hide' ), true ) && '' === trim( $note ) ) {
			wp_die( esc_html__( 'A review note is required for rejection or hiding.', 'sabri-learning' ), '', array( 'response' => 400 ) );
		}

		$from = (string) get_post_meta( $id, '_slc_workflow_state', true );
		$map  = array(
			'submitted' => array( 'approve' => array( 'publish', 'published' ), 'reject' => array( 'draft', 'rejected' ), 'hide' => array( 'private', 'hidden' ) ),
			'published' => array( 'hide' => array( 'private', 'hidden' ) ),
		);
		if ( empty( $map[ $from ][ $action ] ) ) {
			wp_die( esc_html__( 'This workflow transition is not permitted.', 'sabri-learning' ), '', array( 'response' => 409 ) );
		}
		if ( 'approve' === $action ) {
			$this->validate_approval( $lesson );
		}

		$current = max( 1, absint( get_post_meta( $id, '_slc_row_version', true ) ) );
		if ( $expected !== $current || ! update_post_meta( $id, '_slc_row_version', $current + 1, $current ) ) {
			wp_die( esc_html__( 'This lesson changed while it was being reviewed. Reload the moderation screen.', 'sabri-learning' ), '', array( 'response' => 409 ) );
		}

		list( $post_status, $to ) = $map[ $from ][ $action ];
		$result = wp_update_post( array( 'ID' => $id, 'post_status' => $post_status ), true );
		if ( is_wp_error( $result ) ) {
			update_post_meta( $id, '_slc_row_version', $current, $current + 1 );
			wp_die( esc_html( $result->get_error_message() ), '', array( 'response' => 500 ) );
		}
		update_post_meta( $id, '_slc_workflow_state', $to );
		update_post_meta( $id, '_slc_review_note', $note );
		update_post_meta( $id, '_slc_reviewed_by', get_current_user_id() );
		update_post_meta( $id, '_slc_reviewed_at', current_time( 'mysql', true ) );
		self::audit( $id, $action, $note, $from, $to );
		SLC_Dependencies::audit( 'review_' . $action, array( 'lesson_id' => $id, 'from' => $from, 'to' => $to ) );
		wp_safe_redirect( admin_url( 'admin.php?page=sabri-learning&lesson_state=' . rawurlencode( $to ) ) );
		exit;
	}

	private function validate_approval( $lesson ) {
		$id    = $lesson->ID;
		$topic = SLC_Content::term( $id, SLC_Content::TOPIC, 'slug' );
		$level = SLC_Content::term( $id, SLC_Content::LEVEL, 'slug' );
		if ( ! SLC_Content::allowed( $topic, SLC_Content::TOPIC ) || ! SLC_Content::allowed( $level, SLC_Content::LEVEL ) ) {
			wp_die( esc_html__( 'Topic or learning level is incomplete.', 'sabri-learning' ), '', array( 'response' => 400 ) );
		}
		foreach ( array( '_slc_objectives', '_slc_references', '_slc_medical_notice_version' ) as $meta ) {
			if ( '' === trim( (string) get_post_meta( $id, $meta, true ) ) ) {
				wp_die( esc_html__( 'Required lesson governance metadata is incomplete.', 'sabri-learning' ), '', array( 'response' => 400 ) );
			}
		}
		if ( ! SLC_Permissions::is_founder( $lesson->post_author ) && ! user_can( $lesson->post_author, SLC_Permissions::CAP_MANAGE ) && ! SLC_Permissions::is_verified_doctor( $lesson->post_author ) ) {
			wp_die( esc_html__( 'The author is no longer eligible under File 00.', 'sabri-learning' ), '', array( 'response' => 400 ) );
		}
		if ( 'patient-case-learning' === $topic && ! $this->has_valid_consent( $id ) ) {
			wp_die( esc_html__( 'A current, non-withdrawn patient-consent record is required.', 'sabri-learning' ), '', array( 'response' => 400 ) );
		}
	}

	private function has_valid_consent( $lesson_id ) {
		global $wpdb;
		return SLC_Database::valid_consent( $lesson_id );
	}

	private function consent_status( $lesson_id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT id,withdrawn_at FROM {$wpdb->prefix}slc_consents WHERE lesson_id=%d ORDER BY id DESC LIMIT 1", absint( $lesson_id ) ), ARRAY_A );
		if ( ! $row ) {
			return 'Missing consent record';
		}
		return empty( $row['withdrawn_at'] ) ? 'Valid consent record' : 'Consent withdrawn';
	}

	private function withdrawal_form( $lesson_id ) {
		if ( ! $this->has_valid_consent( $lesson_id ) ) {
			return '';
		}
		ob_start();
		?><form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" class="slc-consent-withdraw"><input type="hidden" name="action" value="slc_withdraw_consent"><input type="hidden" name="lesson_id" value="<?php echo absint( $lesson_id ); ?>"><?php wp_nonce_field( 'slc_withdraw_consent_' . $lesson_id ); ?><label><span class="screen-reader-text"><?php esc_html_e( 'Withdrawal note', 'sabri-learning' ); ?></span><input name="note" required maxlength="500" placeholder="<?php esc_attr_e( 'Withdrawal reason or request reference', 'sabri-learning' ); ?>"></label><button class="button" type="submit"><?php esc_html_e( 'Withdraw consent and hide', 'sabri-learning' ); ?></button></form><?php
		return ob_get_clean();
	}

	public function withdraw_consent() {
		$this->guard();
		$id = isset( $_POST['lesson_id'] ) ? absint( $_POST['lesson_id'] ) : 0;
		check_admin_referer( 'slc_withdraw_consent_' . $id );
		$note = isset( $_POST['note'] ) ? sanitize_text_field( wp_unslash( $_POST['note'] ) ) : '';
		if ( ! $id || '' === $note || SLC_Content::LESSON !== get_post_type( $id ) ) {
			wp_die( esc_html__( 'A valid lesson and withdrawal reference are required.', 'sabri-learning' ), '', array( 'response' => 400 ) );
		}
		global $wpdb;
		if ( ! SLC_Database::valid_consent( $id ) ) {
			wp_die( esc_html__( 'No active consent record was available to withdraw.', 'sabri-learning' ), '', array( 'response' => 409 ) );
		}
		$from        = (string) get_post_meta( $id, '_slc_workflow_state', true );
		$old_status  = get_post_status( $id );
		$result      = wp_update_post( array( 'ID' => $id, 'post_status' => 'private' ), true );
		if ( is_wp_error( $result ) ) {
			wp_die( esc_html( $result->get_error_message() ), '', array( 'response' => 500 ) );
		}
		$updated = $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}slc_consents SET withdrawn_at=UTC_TIMESTAMP(),withdrawn_by=%d WHERE lesson_id=%d AND withdrawn_at IS NULL", get_current_user_id(), $id ) );
		if ( ! $updated ) {
			wp_update_post( array( 'ID' => $id, 'post_status' => $old_status ) );
			wp_die( esc_html__( 'Consent withdrawal did not complete; the lesson state was restored.', 'sabri-learning' ), '', array( 'response' => 500 ) );
		}
		update_post_meta( $id, '_slc_workflow_state', 'hidden' );
		update_post_meta( $id, '_slc_row_version', max( 1, absint( get_post_meta( $id, '_slc_row_version', true ) ) ) + 1 );
		self::audit( $id, 'consent_withdrawn', $note, $from, 'hidden' );
		SLC_Dependencies::audit( 'consent_withdrawn', array( 'lesson_id' => $id ) );
		wp_safe_redirect( admin_url( 'admin.php?page=sabri-learning&lesson_state=hidden' ) );
		exit;
	}

	public static function audit( $lesson, $action, $note, $from = '', $to = '' ) {
		global $wpdb;
		$request_id = function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'slc-', true );
		$wpdb->insert(
			$wpdb->prefix . 'slc_audit_log',
			array(
				'lesson_id' => absint( $lesson ),
				'actor_id'  => get_current_user_id(),
				'action'    => sanitize_key( $action ),
				'from_state'=> sanitize_key( $from ),
				'to_state'  => sanitize_key( $to ),
				'note'      => sanitize_textarea_field( $note ),
				'request_id'=> substr( sanitize_text_field( $request_id ), 0, 64 ),
				'created_at'=> current_time( 'mysql', true ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
	}

	private function guard() {
		if ( ! current_user_can( SLC_Permissions::CAP_REVIEW ) ) {
			wp_die( esc_html__( 'You cannot manage learning content.', 'sabri-learning' ), '', array( 'response' => 403 ) );
		}
	}

	public function notice() {
		if ( current_user_can( SLC_Permissions::CAP_MANAGE ) && get_transient( 'slc_activation_notice' ) ) {
			delete_transient( 'slc_activation_notice' );
			echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Learn Sabri Classical Homeopathy is active.', 'sabri-learning' ) . '</strong> ' . esc_html__( 'Review the public learning page and moderation dashboard.', 'sabri-learning' ) . '</p></div>';
		}
	}
}
