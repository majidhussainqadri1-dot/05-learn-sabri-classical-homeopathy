#!/usr/bin/env bash
set -euo pipefail

test -f sabri-learning/sabri-learning.php
test "$(find sabri-learning -type f | wc -l | tr -d ' ')" -eq 21
test "$(find sabri-learning -type f -name '*.php' | wc -l | tr -d ' ')" -eq 15
! grep -R "SPD_Helpers" sabri-learning
! grep -R "option_comment_registration" sabri-learning
! grep -R "\.innerHTML" sabri-learning/assets/js
! grep -R "capability_type.*post" sabri-learning/includes/class-slc-content.php
grep -R "SABRI_SHELL_VERSION" sabri-learning/includes/class-slc-dependencies.php
grep -R "smc_user_status" sabri-learning/includes/class-slc-permissions.php
grep -R "slc_review_lessons" sabri-learning/includes/class-slc-permissions.php
echo "Source invariants passed."
