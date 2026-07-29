<?php
/** Private learning progress, bookmarks, quizzes, and atomic metrics. */

defined( 'ABSPATH' ) || exit;

final class SLC_Learning {
	public function hooks() {
		add_action( 'wp_ajax_slc_learning_action', array( $this, 'ajax' ) );
		add_action( 'template_redirect', array( $this, 'track' ) );
		add_shortcode( 'slc_my_learning', array( $this, 'dashboard' ) );
		add_filter( 'the_content', array( $this, 'append_level_progress' ), 20 );
	}

	public function ajax() {
		check_ajax_referer( 'slc_learning', 'nonce' );
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Please log in to use learning tools.', 'sabri-learning' ) ), 401 );
		}
		$user = get_current_user_id();
		if ( ! SLC_Database::allow( 'learning:' . $user, 40, MINUTE_IN_SECONDS ) ) {
			wp_send_json_error( array( 'message' => __( 'Please wait before trying again.', 'sabri-learning' ) ), 429 );
		}
		$lesson = isset( $_POST['lessonId'] ) ? absint( $_POST['lessonId'] ) : 0;
		$kind   = isset( $_POST['kind'] ) ? sanitize_key( wp_unslash( $_POST['kind'] ) ) : '';
		if ( ! SLC_Database::lesson_publicly_available( $lesson ) ) {
			wp_send_json_error( array( 'message' => __( 'Lesson not found.', 'sabri-learning' ) ), 404 );
		}
		if ( 'bookmark' === $kind ) {
			$active = $this->toggle_bookmark( $user, $lesson );
			wp_send_json_success( array( 'active' => $active, 'label' => $active ? __( 'Bookmarked', 'sabri-learning' ) : __( 'Bookmark', 'sabri-learning' ) ) );
		}
		if ( 'complete' === $kind ) {
			$this->progress( $user, $lesson, 'completed' );
			wp_send_json_success( array( 'active' => true, 'label' => __( 'Completed', 'sabri-learning' ) ) );
		}
		if ( 'quiz' === $kind ) {
			$this->quiz( $user, $lesson );
		}
		wp_send_json_error( array( 'message' => __( 'Unknown learning action.', 'sabri-learning' ) ), 400 );
	}

	private function toggle_bookmark( $user, $lesson ) {
		global $wpdb;
		$table = $wpdb->prefix . 'slc_bookmarks';
		$id    = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE user_id=%d AND lesson_id=%d", $user, $lesson ) );
		if ( $id ) {
			$wpdb->delete( $table, array( 'id' => absint( $id ) ), array( '%d' ) );
			return false;
		}
		return false !== $wpdb->insert( $table, array( 'user_id' => $user, 'lesson_id' => $lesson, 'created_at' => current_time( 'mysql', true ) ), array( '%d', '%d', '%s' ) );
	}

	private function quiz( $user, $lesson ) {
		$quiz = get_post_meta( $lesson, '_slc_quiz', true );
		if ( ! is_array( $quiz ) || ! $quiz ) {
			wp_send_json_error( array( 'message' => __( 'No knowledge check is available.', 'sabri-learning' ) ), 400 );
		}
		$answers = isset( $_POST['answers'] ) ? json_decode( wp_unslash( $_POST['answers'] ), true ) : array();
		$answers = is_array( $answers ) ? $answers : array();
		$correct = 0;
		$review  = array();
		foreach ( $quiz as $index => $item ) {
			$given        = isset( $answers[ $index ] ) ? (int) $answers[ $index ] : -1;
			$answer_index = isset( $item['a'] ) ? (int) $item['a'] : 0;
			if ( $answer_index < 0 || $answer_index > 2 || empty( $item['o'][ $answer_index ] ) ) {
				continue;
			}
			$ok = $given === $answer_index;
			if ( $ok ) {
				++$correct;
			}
			$review[] = array( 'correct' => $ok, 'answer' => sanitize_text_field( $item['o'][ $answer_index ] ), 'explanation' => sanitize_text_field( isset( $item['e'] ) ? $item['e'] : '' ) );
		}
		$total = count( $review );
		if ( ! $total ) {
			wp_send_json_error( array( 'message' => __( 'The knowledge check is invalid.', 'sabri-learning' ) ), 500 );
		}
		$score = (int) round( 100 * $correct / $total );
		$this->progress( $user, $lesson, 'started', $score );
		wp_send_json_success( array( 'score' => $score, 'correct' => $correct, 'total' => $total, 'review' => $review ) );
	}

	private function progress( $user, $lesson, $status, $score = null ) {
		global $wpdb;
		if ( ! SLC_Database::lesson_publicly_available( $lesson ) ) {
			return false;
		}
		$table       = $wpdb->prefix . 'slc_progress';
		$score_value = null === $score ? 0 : min( 100, absint( $score ) );
		$sql         = $wpdb->prepare(
			"INSERT INTO {$table} (user_id,lesson_id,status,score,updated_at) VALUES (%d,%d,%s,%d,UTC_TIMESTAMP())
			 ON DUPLICATE KEY UPDATE status=IF(status='completed','completed',VALUES(status)), score=GREATEST(score,VALUES(score)), updated_at=UTC_TIMESTAMP()",
			$user,
			$lesson,
			in_array( $status, array( 'started', 'completed' ), true ) ? $status : 'started',
			$score_value
		);
		return false !== $wpdb->query( $sql );
	}

	public function track() {
		if ( ! is_singular( SLC_Content::LESSON ) ) {
			return;
		}
		$id = get_queried_object_id();
		if ( ! SLC_Database::lesson_publicly_available( $id ) ) {
			return;
		}
		if ( is_user_logged_in() ) {
			$this->progress( get_current_user_id(), $id, 'started' );
		}
		$cookie = 'slc_viewed_' . $id;
		if ( empty( $_COOKIE[ $cookie ] ) ) {
			SLC_Database::increment_view( $id );
			if ( ! headers_sent() ) {
				setcookie( $cookie, '1', array( 'expires' => time() + 12 * HOUR_IN_SECONDS, 'path' => COOKIEPATH ? COOKIEPATH : '/', 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax' ) );
			}
		}
	}

	public static function bookmarked( $lesson, $user = 0 ) {
		global $wpdb;
		$user = $user ? absint( $user ) : get_current_user_id();
		if ( ! $user || ! SLC_Database::lesson_publicly_available( $lesson ) ) { return false; }
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT b.id FROM {$wpdb->prefix}slc_bookmarks b INNER JOIN {$wpdb->posts} p ON p.ID=b.lesson_id WHERE b.user_id=%d AND b.lesson_id=%d AND p.post_type=%s AND p.post_status='publish'", $user, absint( $lesson ), SLC_Content::LESSON ) );
	}

	public static function status( $lesson, $user = 0 ) {
		global $wpdb;
		$user = $user ? absint( $user ) : get_current_user_id();
		if ( ! $user || ! SLC_Database::lesson_publicly_available( $lesson ) ) { return ''; }
		return (string) $wpdb->get_var( $wpdb->prepare( "SELECT r.status FROM {$wpdb->prefix}slc_progress r INNER JOIN {$wpdb->posts} p ON p.ID=r.lesson_id WHERE r.user_id=%d AND r.lesson_id=%d AND p.post_type=%s AND p.post_status='publish'", $user, absint( $lesson ), SLC_Content::LESSON ) );
	}

	public static function actions( $lesson ) {
		$logged   = is_user_logged_in();
		$saved    = $logged && self::bookmarked( $lesson );
		$complete = $logged && 'completed' === self::status( $lesson );
		ob_start();
		?><div class="slc-actions" data-slc-actions><?php if ( $logged ) : ?><button type="button" data-slc-action="bookmark" aria-pressed="<?php echo $saved ? 'true' : 'false'; ?>" class="<?php echo $saved ? 'is-active' : ''; ?>"><?php echo esc_html( $saved ? __( 'Bookmarked', 'sabri-learning' ) : __( 'Bookmark', 'sabri-learning' ) ); ?></button><button type="button" data-slc-action="complete" aria-pressed="<?php echo $complete ? 'true' : 'false'; ?>" class="<?php echo $complete ? 'is-active' : ''; ?>"><?php echo esc_html( $complete ? __( 'Completed', 'sabri-learning' ) : __( 'Mark as Complete', 'sabri-learning' ) ); ?></button><?php else : ?><a href="<?php echo esc_url( wp_login_url( get_permalink( $lesson ) ) ); ?>"><?php esc_html_e( 'Log in to track progress', 'sabri-learning' ); ?></a><?php endif; ?></div><?php
		return ob_get_clean();
	}

	public static function quiz_form( $lesson ) {
		$quiz = get_post_meta( $lesson, '_slc_quiz', true );
		if ( ! is_array( $quiz ) || ! $quiz ) {
			return '';
		}
		ob_start();
		?><section class="slc-quiz"><h2><?php esc_html_e( 'Knowledge Check', 'sabri-learning' ); ?></h2><form data-slc-quiz><?php foreach ( $quiz as $i => $item ) : ?><fieldset><legend><?php echo esc_html( ( $i + 1 ) . '. ' . $item['q'] ); ?></legend><?php foreach ( $item['o'] as $j => $option ) : ?><label><input type="radio" name="q<?php echo absint( $i ); ?>" value="<?php echo absint( $j ); ?>" required> <?php echo esc_html( $option ); ?></label><?php endforeach; ?></fieldset><?php endforeach; ?><?php if ( is_user_logged_in() ) : ?><button class="slc-button" type="submit"><?php esc_html_e( 'Check Answers', 'sabri-learning' ); ?></button><?php else : ?><a class="slc-button" href="<?php echo esc_url( wp_login_url( get_permalink( $lesson ) ) ); ?>"><?php esc_html_e( 'Log In to Take Check', 'sabri-learning' ); ?></a><?php endif; ?><div data-slc-quiz-result aria-live="polite"></div></form></section><?php
		return ob_get_clean();
	}

	public function dashboard() {
		if ( ! is_user_logged_in() ) {
			return '<div class="slc-notice"><p>' . esc_html__( 'Log in to view your learning progress.', 'sabri-learning' ) . '</p><a class="slc-button" href="' . esc_url( wp_login_url( get_permalink() ) ) . '">' . esc_html__( 'Log In', 'sabri-learning' ) . '</a></div>';
		}
		global $wpdb;
		$user = get_current_user_id();
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT r.* FROM {$wpdb->prefix}slc_progress r INNER JOIN {$wpdb->posts} p ON p.ID=r.lesson_id WHERE r.user_id=%d AND p.post_type=%s AND p.post_status='publish' ORDER BY r.updated_at DESC LIMIT 200", $user, SLC_Content::LESSON ) );
		$rows = array_values( array_filter( $rows, static function( $row ) { return SLC_Database::lesson_publicly_available( $row->lesson_id ); } ) );
		$bookmarks = $wpdb->get_col( $wpdb->prepare( "SELECT b.lesson_id FROM {$wpdb->prefix}slc_bookmarks b INNER JOIN {$wpdb->posts} p ON p.ID=b.lesson_id WHERE b.user_id=%d AND p.post_type=%s AND p.post_status='publish' ORDER BY b.created_at DESC", $user, SLC_Content::LESSON ) );
		$bookmarks = array_values( array_filter( array_map( 'absint', $bookmarks ), static function( $lesson_id ) { return SLC_Database::lesson_publicly_available( $lesson_id ); } ) );
		$public_ids = get_posts( array( 'post_type' => SLC_Content::LESSON, 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids', 'no_found_rows' => true ) );
		$public_ids = array_values( array_filter( array_map( 'absint', $public_ids ), static function( $lesson_id ) { return SLC_Database::lesson_publicly_available( $lesson_id ); } ) );
		$total     = count( $public_ids );
		$completed = count( array_filter( $rows, static function( $row ) { return 'completed' === $row->status; } ) );
		$percent   = $total ? min( 100, round( 100 * $completed / $total ) ) : 0;
		$continue  = array();
		foreach ( $rows as $row ) {
			if ( 'completed' !== $row->status ) {
				$continue[] = $row->lesson_id;
			}
		}
		ob_start();
		?><div class="slc-shell" data-slc-module="progress"><header class="slc-page-head"><span><?php esc_html_e( 'Private Learning Dashboard', 'sabri-learning' ); ?></span><h1><?php esc_html_e( 'My Learning', 'sabri-learning' ); ?></h1><p><?php echo esc_html( sprintf( __( '%1$d of %2$d lessons completed — %3$d%%', 'sabri-learning' ), $completed, $total, $percent ) ); ?></p><progress max="100" value="<?php echo absint( $percent ); ?>"><?php echo absint( $percent ); ?>%</progress></header><div class="slc-two"><section class="slc-panel"><h2><?php esc_html_e( 'Continue Learning', 'sabri-learning' ); ?></h2><?php echo $this->lesson_list( $continue ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></section><section class="slc-panel"><h2><?php esc_html_e( 'Bookmarked Lessons', 'sabri-learning' ); ?></h2><?php echo $this->lesson_list( $bookmarks ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></section></div></div><?php
		return ob_get_clean();
	}

	private function lesson_list( $ids ) {
		if ( ! $ids ) {
			return '<p>' . esc_html__( 'No lessons here yet.', 'sabri-learning' ) . '</p>';
		}
		$out = '<ul class="slc-lesson-list">';
		foreach ( array_slice( array_unique( array_map( 'absint', $ids ) ), 0, 20 ) as $id ) {
			if ( SLC_Database::lesson_publicly_available( $id ) ) {
				$out .= '<li><a href="' . esc_url( get_permalink( $id ) ) . '">' . esc_html( get_the_title( $id ) ) . '</a></li>';
			}
		}
		return $out . '</ul>';
	}

	public function append_level_progress( $content ) {
		$pages = (array) get_option( 'slc_page_map', array() );
		if ( ! is_user_logged_in() || empty( $pages['progress'] ) || ! is_page( absint( $pages['progress'] ) ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		global $wpdb;
		$completed = $wpdb->get_col( $wpdb->prepare( "SELECT r.lesson_id FROM {$wpdb->prefix}slc_progress r INNER JOIN {$wpdb->posts} p ON p.ID=r.lesson_id WHERE r.user_id=%d AND r.status='completed' AND p.post_type=%s AND p.post_status='publish'", get_current_user_id(), SLC_Content::LESSON ) );
		$completed = array_values( array_filter( array_map( 'absint', $completed ), static function( $lesson_id ) { return SLC_Database::lesson_publicly_available( $lesson_id ); } ) );
		$out = '<section class="slc-shell"><div class="slc-panel"><h2>' . esc_html__( 'Progress by Level', 'sabri-learning' ) . '</h2><div class="slc-level-progress">';
		foreach ( SLC_Content::levels() as $slug => $name ) {
			$ids = get_posts( array( 'post_type' => SLC_Content::LESSON, 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids', 'tax_query' => array( array( 'taxonomy' => SLC_Content::LEVEL, 'field' => 'slug', 'terms' => $slug ) ), 'no_found_rows' => true ) );
			$ids = array_values( array_filter( array_map( 'absint', $ids ), static function( $lesson_id ) { return SLC_Database::lesson_publicly_available( $lesson_id ); } ) );
			$done = count( array_intersect( $ids, $completed ) );
			$total = count( $ids );
			$percent = $total ? round( 100 * $done / $total ) : 0;
			$out .= '<div><strong>' . esc_html( $name ) . '</strong><span>' . absint( $done ) . '/' . absint( $total ) . '</span><progress max="100" value="' . absint( $percent ) . '">' . absint( $percent ) . '%</progress></div>';
		}
		return $content . $out . '</div></div></section>';
	}
}
