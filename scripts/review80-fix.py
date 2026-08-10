#!/usr/bin/env python3
from pathlib import Path

fpath = Path('05-learn-sabri-classical-homeopathy/includes/class-lsch-future18.php')
s = fpath.read_text(encoding='utf-8')
needle = """\tpublic static function mentorships( $user_id ) {"""
insert = r'''	public static function end_mentorship( $actor_id, $mentorship_id, $expected_version ) {
		$actor_id = absint( $actor_id );
		$mentorship_id = absint( $mentorship_id );
		if ( ! self::approved_user( $actor_id ) ) {
			return new WP_Error( 'lsch_future18_mentorship_end_forbidden', __( 'Mentorship closure is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
		}
		global $wpdb;
		$t = self::tables();
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['mentorship']} WHERE id=%d LIMIT 1", $mentorship_id ), ARRAY_A );
		if ( ! $row || 'active' !== $row['status'] || absint( $row['version'] ) !== absint( $expected_version ) ) {
			return new WP_Error( 'lsch_future18_mentorship_end_conflict', __( 'Mentorship record changed, ended, or was not found.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
		}
		$participant = $actor_id === absint( $row['mentor_id'] ) || $actor_id === absint( $row['learner_id'] );
		if ( ! $participant && ! user_can( $actor_id, LSCH_Capabilities::MANAGE_CURRICULUM ) ) {
			return new WP_Error( 'lsch_future18_mentorship_end_scope', __( 'Only a participant or curriculum manager may end this mentorship.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
		}
		$updated = $wpdb->update( $t['mentorship'], array( 'status' => 'ended', 'version' => absint( $row['version'] ) + 1, 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $mentorship_id, 'status' => 'active', 'version' => absint( $row['version'] ) ), array( '%s', '%d', '%s' ), array( '%d', '%s', '%d' ) );
		if ( 1 !== $updated ) {
			return new WP_Error( 'lsch_future18_mentorship_end_conflict', __( 'Mentorship changed while closing.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
		}
		LSCH_Events::publish( 'LearningMentorshipEnded.v1', 'mentorship', $mentorship_id, array( 'mentor_id' => absint( $row['mentor_id'] ), 'learner_id' => absint( $row['learner_id'] ), 'course_id' => absint( $row['course_id'] ), 'ended_by' => $actor_id ) );
		return array( 'id' => $mentorship_id, 'status' => 'ended', 'version' => absint( $row['version'] ) + 1 );
	}

'''
if needle not in s:
    raise SystemExit('Round 09 mentorship insertion target not found')
s = s.replace(needle, insert + needle, 1)
fpath.write_text(s, encoding='utf-8')

rpath = Path('05-learn-sabri-classical-homeopathy/includes/class-lsch-future18-rest.php')
r = rpath.read_text(encoding='utf-8')
route_needle = "\t\tregister_rest_route( $ns, '/future18/mentorship/(?P<id>\\d+)/feedback', array( 'methods' => WP_REST_Server::EDITABLE, 'callback' => array( $this, 'mentorship_feedback' ), 'permission_callback' => array( $this, 'teacher' ) ) );\n"
route = route_needle + "\t\tregister_rest_route( $ns, '/future18/mentorship/(?P<id>\\d+)/end', array( 'methods' => WP_REST_Server::EDITABLE, 'callback' => array( $this, 'mentorship_end' ), 'permission_callback' => array( $this, 'approved' ) ) );\n"
if route_needle not in r:
    raise SystemExit('Round 09 route target not found')
r = r.replace(route_needle, route, 1)
cb_needle = """\tpublic function mentorships() {"""
cb = r'''	public function mentorship_end( WP_REST_Request $request ) {
		return LSCH_Future18::end_mentorship( get_current_user_id(), absint( $request['id'] ), absint( $request->get_param( 'version' ) ) );
	}

'''
if cb_needle not in r:
    raise SystemExit('Round 09 callback target not found')
r = r.replace(cb_needle, cb + cb_needle, 1)
rpath.write_text(r, encoding='utf-8')

tpath = Path('tests/future18-invariants.py')
x = tpath.read_text(encoding='utf-8')
marker = "# Read paths must not mutate personalized pathway state.\n"
check = "# Mentorship lifecycle must support bounded, optimistic-lock termination.\nif 'end_mentorship' not in f or 'LearningMentorshipEnded.v1' not in f or \"'status' => 'ended'\" not in f:\n    errors.append('Mentorship end lifecycle is incomplete.')\nif '/future18/mentorship/(?P<id>\\\\d+)/end' not in r or 'mentorship_end' not in r:\n    errors.append('Mentorship end REST route is missing.')\n\n"
if marker not in x:
    raise SystemExit('Round 09 invariant marker missing')
if check.strip() not in x:
    x = x.replace(marker, check + marker, 1)
tpath.write_text(x, encoding='utf-8')
