#!/bin/bash

#
# Get Changed PHP Files Script
# ----------------------------
#
# This script detects PHP files that have changed between the current HEAD
# and the base branch, excluding CICanary.php.
#
# Usage:
#   ./scripts/get-changed-php-files.sh
#
# Environment Variables:
#   GITHUB_BASE_REF - The base branch for GitHub Actions pull requests
#
# Returns:
#   A list of changed PHP files, one per line
#

# Enable strict error handling and command tracing.
set -e              # Exit immediately if a command exits with a non-zero status.
set -u              # Treat unset variables as errors and exit immediately.
set -o pipefail     # The return value of a pipeline is the status of the last command to exit with a non-zero status, or zero if all succeed.
set -x              # Print each command and its arguments as they are executed.

# Determine the base branch
if [ -n "${GITHUB_BASE_REF:-}" ]; then
    # In GitHub Actions, use the provided base ref
    BASE="origin/$GITHUB_BASE_REF"
else
    # Local development: try to detect the default branch
    BASE_BRANCH=$(git symbolic-ref refs/remotes/origin/HEAD 2>/dev/null | sed 's@^refs/remotes/origin/@@' || echo 'main')
    BASE="origin/$BASE_BRANCH"
fi

echo "Base branch: $BASE"

# Get changed PHP files, excluding deleted files and CICanary.php
git diff --diff-filter=d --name-only "$BASE...HEAD" -- '*.php' | grep -v CICanary.php
