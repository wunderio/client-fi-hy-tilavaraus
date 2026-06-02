#!/usr/bin/env bash
set -euo pipefail

REPO_DIR="/Users/gervinkalvet/Work/client-fi-hy-tilavaraus"

if [[ "${1:-}" == "--help" ]]; then
  cat <<'EOF'
Run a local Optime migration test using bundled JSON sample data.

Usage:
  scripts/optime-local-test.sh [minimal|full]

Defaults:
  sample=minimal

Behavior:
  - switches source to local offline mode
  - shows source status
  - runs migrate import
  - always restores live source mode before exit
EOF
  exit 0
fi

sample="${1:-minimal}"
if [[ "$sample" != "minimal" && "$sample" != "full" ]]; then
  echo "Invalid sample '$sample'. Use 'minimal' or 'full'." >&2
  exit 2
fi

cd "$REPO_DIR"

switched=0
restore_live() {
  if [[ "$switched" -eq 1 ]]; then
    ddev drush optime:source-live >/dev/null || true
    ddev drush cr >/dev/null || true
  fi
}
trap restore_live EXIT

ddev drush optime:source-offline --sample="$sample"
switched=1
ddev drush cr
ddev drush optime:source-status
ddev drush migrate:import optime_integration --update -vvv

echo "Local test run completed. Source mode restored to live."


