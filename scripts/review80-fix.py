#!/usr/bin/env python3
from pathlib import Path

fpath = Path('05-learn-sabri-classical-homeopathy/includes/class-lsch-future18.php')
s = fpath.read_text(encoding='utf-8')
needle = """\tpublic static function assign_mentor( $mentor_id, $learner_id, $course_id, array $goals ) {"""
insert = r'''	public static function set_portfolio_visibility( $user_id, $id, $visibility, $expected_version ) {
		$user_id = absint( $user_id );
		$id = absint( $id );
		$visibility = sanitize_key( $visibility );
		if ( ! self::approved_user( $user_id ) || ! in_array( $visibility, array( 'private', 'shareable_by_consent' ), true ) ) {
			return new WP_Error( 'lsch_future18_portfolio_visibility_forbidden', __( 'Portfolio sharing preference is unavailable.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 403 ) );
		}
		global $wpdb;
		$t = self::tables();
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT id,public_id,user_id,visibility,version FROM {$t['portfolio']} WHERE id=%d AND user_id=%d LIMIT 1", $id, $user_id ), ARRAY_A );
		if ( ! $row || absint( $row['version'] ) !== absint( $expected_version ) ) {
			return new WP_Error( 'lsch_future18_portfolio_visibility_conflict', __( 'Portfolio item changed or was not found.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
		}
		if ( $row['visibility'] === $visibility ) {
			return array( 'id' => $id, 'public_id' => $row['public_id'], 'visibility' => $visibility, 'version' => absint( $row['version'] ) );
		}
		$updated = $wpdb->update( $t['portfolio'], array( 'visibility' => $visibility, 'version' => absint( $row['version'] ) + 1, 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $id, 'user_id' => $user_id, 'version' => absint( $row['version'] ) ), array( '%s', '%d', '%s' ), array( '%d', '%d', '%d' ) );
		if ( 1 !== $updated ) {
			return new WP_Error( 'lsch_future18_portfolio_visibility_conflict', __( 'Portfolio sharing preference changed while saving.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 409 ) );
		}
		LSCH_Events::publish( 'LearningPortfolioConsentChanged.v1', 'portfolio', $row['public_id'], array( 'user_id' => $user_id, 'visibility' => $visibility, 'revoked' => 'private' === $visibility ) );
		return array( 'id' => $id, 'public_id' => $row['public_id'], 'visibility' => $visibility, 'version' => absint( $row['version'] ) + 1 );
	}

'''
if needle not in s:
    raise SystemExit('Round 08 portfolio insertion target not found')
s = s.replace(needle, insert + needle, 1)
fpath.write_text(s, encoding='utf-8')

rpath = Path('05-learn-sabri-classical-homeopathy/includes/class-lsch-future18-rest.php')
r = rpath.read_text(encoding='utf-8')
route_needle = "\t\tregister_rest_route( $ns, '/future18/portfolio', array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( $this, 'portfolio' ), 'permission_callback' => array( $this, 'approved' ) ) );\n"
route = route_needle + "\t\tregister_rest_route( $ns, '/future18/portfolio/(?P<id>\\d+)/visibility', array( 'methods' => WP_REST_Server::EDITABLE, 'callback' => array( $this, 'portfolio_visibility' ), 'permission_callback' => array( $this, 'approved' ) ) );\n"
if route_needle not in r:
    raise SystemExit('Round 08 portfolio route target not found')
r = r.replace(route_needle, route, 1)
cb_needle = """\tpublic function mentorship_assign( WP_REST_Request $request ) {"""
cb = r'''	public function portfolio_visibility( WP_REST_Request $request ) {
		return LSCH_Future18::set_portfolio_visibility( get_current_user_id(), absint( $request['id'] ), $request->get_param( 'visibility' ), absint( $request->get_param( 'version' ) ) );
	}

'''
if cb_needle not in r:
    raise SystemExit('Round 08 portfolio callback target not found')
r = r.replace(cb_needle, cb + cb_needle, 1)
rpath.write_text(r, encoding='utf-8')

tpath = Path('tests/future18-invariants.py')
x = tpath.read_text(encoding='utf-8')
marker = "# Read paths must not mutate personalized pathway state.\n"
check = "# Portfolio sharing must be explicit, owner-scoped, versioned and revocable.\nif 'set_portfolio_visibility' not in f or 'LearningPortfolioConsentChanged.v1' not in f or \"'revoked' => 'private' === $visibility\" not in f:\n    errors.append('Portfolio consent/revocation lifecycle is incomplete.')\nif '/future18/portfolio/(?P<id>\\\\d+)/visibility' not in r or 'portfolio_visibility' not in r:\n    errors.append('Portfolio consent/revocation REST surface is missing.')\n\n"
if marker not in x:
    raise SystemExit('Round 08 invariant marker missing')
if check.strip() not in x:
    x = x.replace(marker, check + marker, 1)
tpath.write_text(x, encoding='utf-8')
