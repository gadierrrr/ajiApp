<?php
/**
 * Convert inline event handlers to data-action attributes
 * and add nonces to inline <script> blocks.
 *
 * Usage: php scripts/convert-inline-handlers.php [--dry-run]
 */

$dryRun = in_array('--dry-run', $argv);

$files = [
    'components/nav.php',
    'components/filters.php',
    'components/footer.php',
    'components/beach-card.php',
    'components/beach-drawer.php',
    'components/beach-grid.php',
    'components/review-card.php',
    'components/footer-minimal.php',
    'components/guide-map-panel.php',
    'components/collection/explorer.php',
    'components/send-list-capture.php',
    'public/index.php',
    'public/beach.php',
    'public/compare.php',
    'public/quiz.php',
    'public/quiz-results.php',
    'public/profile.php',
    'public/favorites.php',
    'public/offline.php',
    'public/guides/spring-break-beaches-puerto-rico.php',
    'public/api/beaches.php',
    'public/api/reviews.php',
    'public/api/photos.php',
    'public/api/toggle-favorite.php',
    'public/admin/index.php',
    'public/admin/beaches.php',
    'public/admin/place-id-audit.php',
    'public/admin/emails.php',
    'public/admin/referrals.php',
    'public/admin/users.php',
    'public/admin/components/footer.php',
];

$root = dirname(__DIR__);
$stats = ['files' => 0, 'scripts_nonced' => 0, 'handlers_converted' => 0];

foreach ($files as $relPath) {
    $path = $root . '/' . $relPath;
    if (!file_exists($path)) {
        echo "SKIP (not found): {$relPath}\n";
        continue;
    }

    $original = file_get_contents($path);
    $content = $original;

    // 1) Add nonce to <script> blocks (inline and external in components)
    $content = preg_replace_callback(
        '/<script\b([^>]*)>/i',
        function ($m) use (&$stats) {
            $attrs = $m[1];
            if (stripos($attrs, 'nonce') !== false) return $m[0];
            if (stripos($attrs, 'cspNonceAttr') !== false) return $m[0];
            if (stripos($attrs, 'application/ld+json') !== false) return $m[0];
            $stats['scripts_nonced']++;
            return '<script' . $attrs . ' <?= cspNonceAttr() ?>>';
        },
        $content
    );

    // 2) Handle onerror on img tags
    $content = preg_replace(
        '/\bonerror\s*=\s*"this\.src\s*=\s*\'([^\']+)\';\s*this\.onerror\s*=\s*null;\s*"/i',
        'data-fallback-src="$1"',
        $content
    );

    // 3) Convert inline event handlers
    $eventTypes = ['onclick', 'onchange', 'onsubmit', 'oninput', 'onkeydown'];

    foreach ($eventTypes as $eventType) {
        $event = substr($eventType, 2);

        $content = preg_replace_callback(
            '/\b' . preg_quote($eventType, '/') . '\s*=\s*"([^"]*)"/i',
            function ($m) use ($event, &$stats, $relPath) {
                $handler = $m[1];
                $stats['handlers_converted']++;
                return convertHandler($event, $handler, $relPath);
            },
            $content
        );
    }

    if ($content !== $original) {
        $stats['files']++;
        if ($dryRun) {
            echo "WOULD MODIFY: {$relPath}\n";
        } else {
            file_put_contents($path, $content);
            echo "MODIFIED: {$relPath}\n";
        }
    }
}

echo "\n--- Summary ---\n";
echo "Files modified: {$stats['files']}\n";
echo "Script blocks nonced: {$stats['scripts_nonced']}\n";
echo "Handlers converted: {$stats['handlers_converted']}\n";

// --- Conversion functions ---

function convertHandler($event, $handler, $file)
{
    $handler = trim($handler);

    // Pure stopPropagation
    if ($handler === 'event.stopPropagation()') {
        return 'data-action-stop data-action="noop" data-on="' . $event . '"';
    }

    // event.stopPropagation() + function call
    if (preg_match('/^event\.stopPropagation\(\);\s*(.+)$/', $handler, $m)) {
        $rest = trim($m[1]);
        $converted = parseCall($rest, $event);
        if ($converted) {
            return 'data-action-stop ' . $converted;
        }
    }

    // Pass-event patterns: closeFilterDrawer(event), closeBeachDrawer(event), closeLightbox(event)
    if (preg_match('/^(\w+)\(event\)$/', $handler, $m)) {
        return da($m[1], '["__event__"]', $event);
    }

    // submitX(event) — form submits
    if (preg_match('/^(submit\w+)\(event\)$/', $handler, $m)) {
        return 'data-action="' . $m[1] . '" data-action-args=' . "'" . '["__event__"]' . "'" . ' data-action-prevent data-on="' . $event . '"';
    }

    // confirm + getElementById submit: if(confirm('msg')) { document.getElementById('id').submit(); }
    if (preg_match('#^if\s*\(confirm\(\'([^\']*)\'\)\)\s*\{\s*document\.getElementById\(\'([^\']+)\'\)\.submit\(\);\s*\}$#', $handler, $m)) {
        return 'data-action="submitFormById" data-action-args=' . "'" . '["' . $m[2] . '"]' . "'" . ' data-action-confirm="' . htmlspecialchars($m[1], ENT_QUOTES) . '" data-on="' . $event . '"';
    }

    // return confirm('...')
    if (preg_match('#^return confirm\(\'([^\']*)\'\);?$#', $handler, $m)) {
        return 'data-action-confirm="' . htmlspecialchars($m[1], ENT_QUOTES) . '" data-action="submitParentForm" data-action-args=' . "'" . '["__this__"]' . "'" . ' data-on="' . $event . '"';
    }

    // window.location.reload()
    if ($handler === 'window.location.reload()') {
        return da('reloadPage', null, $event);
    }

    // functionName(this) — previewPhotos(this), previewUploadPhoto(this)
    if (preg_match('/^(\w+)\(this\)$/', $handler, $m)) {
        return da($m[1], '["__this__"]', $event);
    }

    // functionName(this.value) — for oninput
    if (preg_match('/^(\w+)\(this\.value\)$/', $handler, $m)) {
        return da($m[1], '["__this__"]', $event);
    }

    // keydown: if(event.key==='Enter'||event.key===' '){event.preventDefault();fn('arg');}
    if (preg_match('#^if\(event\.key===\'([^\']+)\'\|\|event\.key===\'([^\']*)\'\)\{event\.preventDefault\(\);(\w+)\(\'([^\']*)\'\);\}$#', $handler, $m)) {
        $keys = $m[1] . ',' . ($m[2] === ' ' ? ' ' : $m[2]);
        return 'data-action="' . $m[3] . '" data-action-args=' . "'" . '["' . addcslashes($m[4], '"') . '"]' . "'" . ' data-action-keys="' . htmlspecialchars($keys, ENT_QUOTES) . '" data-action-prevent data-on="keydown"';
    }

    // setLanguage('en', this.dataset.targetUrl || '')
    if (preg_match('#^setLanguage\(\'([^\']*)\',\s*this\.dataset\.targetUrl\s*\|\|\s*\'\'\)$#', $handler, $m)) {
        return da('setLanguage', '["' . $m[1] . '","__this__"]', $event);
    }

    // Escaped-quote pattern from API context: showSignupPrompt(\'favorites\')
    if (preg_match('#^event\.stopPropagation\(\);\s*(\w+)\(\\\\\'([^\\\\]*)\\\\\'(\)?)$#', $handler, $m)) {
        return 'data-action-stop ' . da($m[1], '["' . $m[2] . '"]', $event);
    }

    // Generic: try parseCall for simple static args
    $converted = parseCall($handler, $event);
    if ($converted) {
        return $converted;
    }

    // Fallback: try to handle any PHP-expression arguments
    // Match: functionName(anyArgs) where anyArgs may contain PHP echo tags
    if (preg_match('/^(\w+)\((.+)\)$/', $handler, $m)) {
        $fn = $m[1];
        $rawArgs = $m[2];
        $converted = convertPhpArgs($fn, $rawArgs, $event);
        if ($converted) {
            return $converted;
        }
    }

    echo "  WARNING: Could not convert in {$file}: {$handler}\n";
    return 'on' . $event . '="' . $handler . '"';
}

function parseCall($handler, $event)
{
    $handler = rtrim(trim($handler), ';');

    // fn()
    if (preg_match('/^(\w+)\(\)$/', $handler, $m)) {
        return da($m[1], null, $event);
    }

    // fn(123)
    if (preg_match('/^(\w+)\((-?\d+(?:\.\d+)?)\)$/', $handler, $m)) {
        return da($m[1], '[' . $m[2] . ']', $event);
    }

    // fn('str')
    if (preg_match('#^(\w+)\(\'([^\']*)\'\)$#', $handler, $m)) {
        return da($m[1], '["' . addcslashes($m[2], '"') . '"]', $event);
    }

    // fn('a', 'b')
    if (preg_match('#^(\w+)\(\'([^\']*)\',\s*\'([^\']*)\'\)$#', $handler, $m)) {
        return da($m[1], '["' . addcslashes($m[2], '"') . '","' . addcslashes($m[3], '"') . '"]', $event);
    }

    // fn('a', 'b', this)
    if (preg_match('#^(\w+)\(\'([^\']*)\',\s*\'([^\']*)\',\s*this\)$#', $handler, $m)) {
        return da($m[1], '["' . addcslashes($m[2], '"') . '","' . addcslashes($m[3], '"') . '","__this__"]', $event);
    }

    // fn('a', 'b', 'c', this)
    if (preg_match('#^(\w+)\(\'([^\']*)\',\s*\'([^\']*)\',\s*\'([^\']*)\',\s*this\)$#', $handler, $m)) {
        return da($m[1], '["' . addcslashes($m[2], '"') . '","' . addcslashes($m[3], '"') . '","' . addcslashes($m[4], '"') . '","__this__"]', $event);
    }

    // fn(123, this)
    if (preg_match('/^(\w+)\((-?\d+),\s*this\)$/', $handler, $m)) {
        return da($m[1], '[' . $m[2] . ',"__this__"]', $event);
    }

    // fn(123, true/false)
    if (preg_match('/^(\w+)\((-?\d+),\s*(true|false)\)$/', $handler, $m)) {
        return da($m[1], '[' . $m[2] . ',' . $m[3] . ']', $event);
    }

    // fn('str', -1)
    if (preg_match('#^(\w+)\(\'([^\']*)\',\s*(-?\d+)\)$#', $handler, $m)) {
        return da($m[1], '["' . addcslashes($m[2], '"') . '",' . $m[3] . ']', $event);
    }

    return null;
}

/**
 * Handle arguments containing PHP short-echo expressions.
 * We split on commas that are outside of PHP tags or nested parens,
 * then wrap each arg appropriately.
 */
function convertPhpArgs($fn, $rawArgs, $event)
{
    // Split args: simple comma split won't work with nested calls.
    // Use a state-machine approach.
    $args = splitArgs($rawArgs);
    if ($args === null) {
        return null;
    }

    $jsonParts = [];
    foreach ($args as $arg) {
        $arg = trim($arg);

        // 'this' keyword
        if ($arg === 'this') {
            $jsonParts[] = '"__this__"';
            continue;
        }

        // Numeric
        if (preg_match('/^-?\d+(?:\.\d+)?$/', $arg)) {
            $jsonParts[] = $arg;
            continue;
        }

        // true/false
        if ($arg === 'true' || $arg === 'false') {
            $jsonParts[] = $arg;
            continue;
        }

        // 'literal string' (may contain PHP tags)
        if (preg_match('/^\'(.*)\'$/', $arg, $m)) {
            $inner = $m[1];
            // If it contains PHP tags, output them raw inside the JSON
            $jsonParts[] = '"' . addcslashes($inner, '"') . '"';
            continue;
        }

        // Bare PHP expression (numeric context)
        if (preg_match('/^<\?=/', $arg)) {
            $jsonParts[] = $arg;
            continue;
        }

        // JS template literal: ${varName}
        if (preg_match('/^\$\{(\w+)\}$/', $arg)) {
            $jsonParts[] = $arg;
            continue;
        }

        // Could not determine arg type
        return null;
    }

    $argsJson = '[' . implode(',', $jsonParts) . ']';
    return da($fn, $argsJson, $event);
}

function splitArgs($s)
{
    $args = [];
    $current = '';
    $depth = 0;      // parens
    $inSingleQ = false;
    $inPhp = false;
    $len = strlen($s);

    for ($i = 0; $i < $len; $i++) {
        $ch = $s[$i];

        // PHP tag start
        if (!$inSingleQ && substr($s, $i, 3) === '<?=') {
            $inPhp = true;
            $current .= $ch;
            continue;
        }
        if ($inPhp && substr($s, $i, 2) === '?>') {
            $inPhp = false;
            $current .= $ch;
            continue;
        }
        if ($inPhp) {
            $current .= $ch;
            continue;
        }

        // Single quotes
        if ($ch === "'" && !$inPhp) {
            $inSingleQ = !$inSingleQ;
            $current .= $ch;
            continue;
        }
        if ($inSingleQ) {
            $current .= $ch;
            continue;
        }

        // Parens
        if ($ch === '(') { $depth++; $current .= $ch; continue; }
        if ($ch === ')') { $depth--; $current .= $ch; continue; }

        // Comma at top level
        if ($ch === ',' && $depth === 0) {
            $args[] = $current;
            $current = '';
            continue;
        }

        $current .= $ch;
    }

    if ($current !== '' || count($args) > 0) {
        $args[] = $current;
    }

    return $args;
}

/**
 * Build data-action="fn" [data-action-args='[...]'] [data-on="event"]
 */
function da($fn, $argsJson, $event)
{
    $out = 'data-action="' . $fn . '"';
    if ($argsJson !== null) {
        $out .= " data-action-args='" . $argsJson . "'";
    }
    if ($event !== 'click') {
        $out .= ' data-on="' . $event . '"';
    }
    return $out;
}
