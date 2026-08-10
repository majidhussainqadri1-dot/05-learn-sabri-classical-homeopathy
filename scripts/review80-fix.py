#!/usr/bin/env python3
from pathlib import Path

p = Path('05-learn-sabri-classical-homeopathy/includes/class-lsch-future18.php')
s = p.read_text(encoding='utf-8')
old = """\tprivate static function reject_sensitive_practice_payload( array $payload ) {
\t\t$forbidden = array( 'patient_name', 'full_name', 'email', 'phone', 'mobile', 'address', 'national_id', 'passport', 'cnic', 'identity_document', 'date_of_birth' );
\t\t$stack = array( $payload );
\t\twhile ( $stack ) {
\t\t\t$current = array_pop( $stack );
\t\t\tforeach ( $current as $key => $value ) {
\t\t\t\t$key = strtolower( sanitize_key( (string) $key ) );
\t\t\t\tif ( in_array( $key, $forbidden, true ) ) {
\t\t\t\t\treturn new WP_Error( 'lsch_future18_sensitive_case_data', __( 'Practice laboratories accept simulated/de-identified educational data only.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) );
\t\t\t\t}
\t\t\t\tif ( is_array( $value ) ) {
\t\t\t\t\t$stack[] = $value;
\t\t\t\t}
\t\t\t}
\t\t}
\t\treturn true;
\t}
"""
new = """\tprivate static function reject_sensitive_practice_payload( array $payload ) {
\t\t$forbidden = array( 'patientname', 'fullname', 'email', 'emailaddress', 'phone', 'phonenumber', 'mobile', 'mobilenumber', 'address', 'postaladdress', 'nationalid', 'passport', 'passportnumber', 'cnic', 'cnicnumber', 'identitydocument', 'dateofbirth', 'dob' );
\t\t$stack = array( $payload );
\t\twhile ( $stack ) {
\t\t\t$current = array_pop( $stack );
\t\t\tforeach ( $current as $key => $value ) {
\t\t\t\t$normalized_key = strtolower( (string) $key );
\t\t\t\t$normalized_key = preg_replace( '/[^a-z0-9]+/', '', $normalized_key );
\t\t\t\tforeach ( $forbidden as $needle ) {
\t\t\t\t\tif ( $normalized_key === $needle || ( 5 <= strlen( $needle ) && false !== strpos( $normalized_key, $needle ) ) ) {
\t\t\t\t\t\treturn new WP_Error( 'lsch_future18_sensitive_case_data', __( 'Practice laboratories accept simulated/de-identified educational data only.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) );
\t\t\t\t\t}
\t\t\t\t}
\t\t\t\tif ( is_string( $value ) ) {
\t\t\t\t\tif ( preg_match( '/\\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\\.[A-Z]{2,}\\b/i', $value ) || preg_match( '/\\b[0-9]{5}-?[0-9]{7}-?[0-9]\\b/', $value ) ) {
\t\t\t\t\t\treturn new WP_Error( 'lsch_future18_sensitive_case_data', __( 'Practice laboratories accept simulated/de-identified educational data only.', 'learn-sabri-classical-homeopathy' ), array( 'status' => 400 ) );
\t\t\t\t\t}
\t\t\t\t}
\t\t\t\tif ( is_array( $value ) ) {
\t\t\t\t\t$stack[] = $value;
\t\t\t\t}
\t\t\t}
\t\t}
\t\treturn true;
\t}
"""
if old not in s:
    raise SystemExit('Round 05 sensitive payload target not found')
s = s.replace(old, new, 1)
p.write_text(s, encoding='utf-8')

t = Path('tests/future18-invariants.py')
x = t.read_text(encoding='utf-8')
marker = "# Read paths must not mutate personalized pathway state.\n"
check = "# De-identified clinical practice must reject identifier aliases and obvious embedded identifiers.\nsensitive = f.split('private static function reject_sensitive_practice_payload',1)[-1].split('private static function review_schedule',1)[0]\nfor token in ['patientname','emailaddress','phonenumber','cnicnumber','dateofbirth','preg_match']:\n    if token not in sensitive:\n        errors.append(f'Missing de-identification guard token: {token}')\n\n"
if marker not in x:
    raise SystemExit('Round 05 invariant marker missing')
if check.strip() not in x:
    x = x.replace(marker, check + marker, 1)
t.write_text(x, encoding='utf-8')
