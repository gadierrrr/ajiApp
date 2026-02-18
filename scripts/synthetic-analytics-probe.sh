#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${1:-${APP_URL:-https://www.puertoricobeachfinder.com}}"
MAX_AGE_SECONDS="${MAX_AGE_SECONDS:-900}"
PROBE_PATH="${PROBE_PATH:-/}"

BASE_URL="${BASE_URL%/}"
PROBE_URL="${BASE_URL}${PROBE_PATH}?bf_analytics_probe=1&bf_probe_ts=$(date +%s)"
HEALTH_URL="${BASE_URL}/api/health/analytics.php?page_probe=1"

run_headless_probe() {
  if command -v google-chrome >/dev/null 2>&1; then
    google-chrome --headless=new --no-sandbox --disable-gpu --virtual-time-budget=15000 --dump-dom "$PROBE_URL" >/tmp/analytics-probe-dom.html 2>/tmp/analytics-probe-browser.log
    return 0
  fi

  if command -v chromium-browser >/dev/null 2>&1; then
    chromium-browser --headless --no-sandbox --disable-gpu --virtual-time-budget=15000 --dump-dom "$PROBE_URL" >/tmp/analytics-probe-dom.html 2>/tmp/analytics-probe-browser.log
    return 0
  fi

  if command -v chromium >/dev/null 2>&1; then
    chromium --headless --no-sandbox --disable-gpu --virtual-time-budget=15000 --dump-dom "$PROBE_URL" >/tmp/analytics-probe-dom.html 2>/tmp/analytics-probe-browser.log
    return 0
  fi

  echo "No supported headless browser found (google-chrome/chromium)." >&2
  return 1
}

echo "Running analytics synthetic probe against: ${PROBE_URL}"
run_headless_probe

health_json="$(curl -fsS "${HEALTH_URL}")"
echo "$health_json"

printf '%s' "$health_json" | php -r '
$maxAge = (int) ($argv[1] ?? 900);
$payload = stream_get_contents(STDIN);
$data = json_decode($payload, true);
if (!is_array($data)) {
    fwrite(STDERR, "Invalid JSON from analytics health endpoint\n");
    exit(1);
}

$errors = [];
if (($data["ok"] ?? false) !== true) {
    $errors[] = "Health endpoint reports ok=false";
}

$page = $data["checks"]["page_probe"] ?? [];
if (($page["umami_tag_present"] ?? false) !== true) {
    $errors[] = "Umami script tag missing in page probe";
}
if (($page["analytics_wrapper_present"] ?? false) !== true) {
    $errors[] = "analytics.js missing in page probe";
}

$probe = $data["checks"]["client_probe"] ?? [];
if (($probe["available"] ?? false) !== true) {
    $errors[] = "No client probe received";
} else {
    $lastSeenRaw = (string) ($probe["last_seen_at"] ?? "");
    $lastSeen = $lastSeenRaw !== "" ? strtotime($lastSeenRaw) : false;
    if ($lastSeen === false) {
        $errors[] = "Invalid client probe timestamp";
    } else {
        $age = time() - $lastSeen;
        if ($age > $maxAge) {
            $errors[] = "Client probe is stale (age={$age}s > {$maxAge}s)";
        }
    }

    if (($probe["umami_available"] ?? false) !== true) {
        $errors[] = "Client probe reported umami_available=false";
    }
}

if (!empty($errors)) {
    foreach ($errors as $error) {
        fwrite(STDERR, "- {$error}\n");
    }
    exit(1);
}

fwrite(STDOUT, "Analytics synthetic probe passed\n");
' "$MAX_AGE_SECONDS"
