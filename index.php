<?php
declare(strict_types=1);

require __DIR__ . '/login/lib.php';

if (!function_exists('mb_ucfirst')) {
    function mb_ucfirst(string $s): string
    {
        $first = substr($s, 0, 1);
        $rest = substr($s, 1, null);
        return strtoupper($first) . $rest;
    }
}

function normalizeEmailForCompare(?string $email): ?string
{
    if (!is_string($email)) {
        return null;
    }

    $normalized = strtolower(trim($email));
    return $normalized === '' ? null : $normalized;
}

function ensureDirectory(string $path): void
{
    if (!is_dir($path)) {
        @mkdir($path, 0775, true);
    }
}

function loadDescriptionHtml(string $folderPath): string
{
    $descPath = $folderPath . DIRECTORY_SEPARATOR . 'description.html';
    if (!is_file($descPath)) {
        $fallbackDescPath = $folderPath . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . 'description.html';
        if (!is_file($fallbackDescPath)) {
            return '';
        }
        $descPath = $fallbackDescPath;
    }

    $html = file_get_contents($descPath);
    if ($html === false) {
        return '';
    }

    $html = preg_replace('~<\s*(script|style|iframe|object|embed)\b[^>]*>.*?<\s*/\s*\1\s*>~is', '', $html);
    return trim((string) $html);
}

function parseCategoryFromMetaRaw(string $raw): ?string
{
    $cleanRaw = ltrim($raw, "\xEF\xBB\xBF \t\r\n");

    $decoded = json_decode($cleanRaw, true);
    if (is_array($decoded)) {
        $category = trim((string) ($decoded['category'] ?? ''));
        if ($category !== '') {
            return $category;
        }
    }

    $ini = @parse_ini_string($cleanRaw, false, INI_SCANNER_TYPED);
    if (is_array($ini)) {
        $category = trim((string) ($ini['category'] ?? ''));
        if ($category !== '') {
            return $category;
        }
    }

    if (preg_match('/^\s*category\s*[:=]\s*(.+?)\s*$/im', $cleanRaw, $match) === 1) {
        $category = trim((string) ($match[1] ?? ''), " \t\r\n\"'");
        if ($category !== '') {
            return $category;
        }
    }

    return null;
}

function loadMetaCategory(string $folderPath): ?string
{
    $localCandidates = [
        $folderPath . DIRECTORY_SEPARATOR . 'meta.conf',
        $folderPath . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . 'meta.conf',
        $folderPath . DIRECTORY_SEPARATOR . 'meta.json',
        $folderPath . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . 'meta.json',
    ];

    foreach ($localCandidates as $metaPath) {
        if (!is_file($metaPath)) {
            continue;
        }

        $raw = file_get_contents($metaPath);
        if ($raw === false) {
            continue;
        }

        $category = parseCategoryFromMetaRaw($raw);
        if ($category !== null) {
            return $category;
        }
    }

    $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === '') {
        return null;
    }

    $isHttps = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';
    $scheme = $isHttps ? 'https' : 'http';
    $scriptDir = trim((string) dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    $basePath = $scriptDir === '' || $scriptDir === '.' ? '' : '/' . $scriptDir;
    $folderSegment = rawurlencode((string) basename($folderPath));

    $urlCandidates = [
        $scheme . '://' . $host . $basePath . '/' . $folderSegment . '/meta.conf',
        $scheme . '://' . $host . $basePath . '/' . $folderSegment . '/web/meta.conf',
        $scheme . '://' . $host . $basePath . '/' . $folderSegment . '/meta.json',
        $scheme . '://' . $host . $basePath . '/' . $folderSegment . '/web/meta.json',
    ];

    $httpContext = stream_context_create([
        'http' => [
            'timeout' => 2,
            'ignore_errors' => true,
        ],
    ]);

    foreach ($urlCandidates as $url) {
        $raw = @file_get_contents($url, false, $httpContext);
        if ($raw === false) {
            continue;
        }

        $category = parseCategoryFromMetaRaw($raw);
        if ($category !== null) {
            return $category;
        }
    }

    return null;
}

function getThumbnailPath(string $folderName, string $folderPath): ?string
{
    $primary = $folderPath . DIRECTORY_SEPARATOR . 'thumbnail.png';
    if (is_file($primary)) {
        return rawurlencode($folderName) . '/thumbnail.png';
    }

    $fallback = $folderPath . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . 'thumbnail.png';
    if (is_file($fallback)) {
        return rawurlencode($folderName) . '/web/thumbnail.png';
    }

    return null;
}

function getCardHref(string $folderName, string $folderPath): string
{
    $primary = $folderPath . DIRECTORY_SEPARATOR . 'thumbnail.png';
    if (is_file($primary)) {
        return rawurlencode($folderName) . '/';
    }

    $fallback = $folderPath . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . 'thumbnail.png';
    if (is_file($fallback)) {
        return rawurlencode($folderName) . '/web/';
    }

    return rawurlencode($folderName) . '/';
}

function getAllowedUsersFromFolderAuth(string $folderPath): array
{
    $candidatePaths = [
        $folderPath . DIRECTORY_SEPARATOR . 'auth.php',
        $folderPath . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . 'auth.php',
    ];

    $authPath = null;
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            $authPath = $candidatePath;
            break;
        }
    }

    if ($authPath === null) {
        return ['hasAllowedUsers' => false, 'allowedUsers' => null];
    }

    $content = file_get_contents($authPath);
    if ($content === false) {
        return ['hasAllowedUsers' => false, 'allowedUsers' => null];
    }

    if (preg_match('/\$allowedUsers\s*=\s*null\s*;/i', $content) === 1) {
        return ['hasAllowedUsers' => true, 'allowedUsers' => null];
    }

    if (preg_match('/\$allowedUsers\s*=\s*\[(.*?)\]\s*;/is', $content, $match) === 1) {
        $users = [];
        preg_match_all('/[\'\"]([^\'\"]+)[\'\"]/', $match[1], $allMatches);
        foreach ($allMatches[1] ?? [] as $email) {
            $users[] = trim((string) $email);
        }
        return ['hasAllowedUsers' => true, 'allowedUsers' => array_values(array_unique($users))];
    }

    if (preg_match('/\$allowedUsers\s*=\s*array\s*\((.*?)\)\s*;/is', $content, $match) === 1) {
        $users = [];
        preg_match_all('/[\'\"]([^\'\"]+)[\'\"]/', $match[1], $allMatches);
        foreach ($allMatches[1] ?? [] as $email) {
            $users[] = trim((string) $email);
        }
        return ['hasAllowedUsers' => true, 'allowedUsers' => array_values(array_unique($users))];
    }

    if (preg_match('/\$allowedUsers\s*=\s*.+?;/is', $content) === 1) {
        return ['hasAllowedUsers' => true, 'allowedUsers' => []];
    }

    return ['hasAllowedUsers' => false, 'allowedUsers' => null];
}

function loadThemeConfigurations(string $themeDir): array
{
    $themes = [];
    if (!is_dir($themeDir)) {
        return $themes;
    }

    $themeFiles = glob($themeDir . DIRECTORY_SEPARATOR . '*.php') ?: [];
    foreach ($themeFiles as $themeFile) {
        $themeConfig = include $themeFile;
        if (!is_array($themeConfig)) {
            continue;
        }

        $themeId = strtolower(trim((string) ($themeConfig['id'] ?? '')));
        if ($themeId === '') {
            continue;
        }

        $themeConfig['id'] = $themeId;
        $themeConfig['label'] = (string) ($themeConfig['label'] ?? mb_ucfirst($themeId));
        $themeConfig['isDefault'] = !empty($themeConfig['isDefault']);
        $themeConfig['showThumbnail'] = !empty($themeConfig['showThumbnail']);
        $themeConfig['showTopBar'] = !empty($themeConfig['showTopBar']);
        $themeConfig['showTitlebar'] = !empty($themeConfig['showTitlebar']);
        $themeConfig['expandDescriptionByDefault'] = !empty($themeConfig['expandDescriptionByDefault']);
        $themeConfig['useLoadingAnimation'] = !empty($themeConfig['useLoadingAnimation']);
        $themeConfig['transformDescriptionHeading'] = !empty($themeConfig['transformDescriptionHeading']);
        $themeConfig['logo'] = trim((string) ($themeConfig['logo'] ?? 'kvt_logo.png'));
        if ($themeConfig['logo'] === '') {
            $themeConfig['logo'] = 'kvt_logo.png';
        }
        $themeConfig['css'] = (string) ($themeConfig['css'] ?? '');
        $themeConfig['js'] = (string) ($themeConfig['js'] ?? '');

        $themes[$themeId] = $themeConfig;
    }

    return $themes;
}

function getDefaultThemeId(array $themes): string
{
    foreach ($themes as $themeId => $themeConfig) {
        if (!empty($themeConfig['isDefault'])) {
            return (string) $themeId;
        }
    }

    return (string) (array_key_first($themes) ?? 'corporate');
}

function makeUserPrefsFilePath(string $prefsDir, ?string $email): ?string
{
    if (!is_string($email)) {
        return null;
    }

    $trimmed = trim($email);
    if ($trimmed === '') {
        return null;
    }

    $safeName = preg_replace('/[^a-z0-9._@-]+/i', '_', strtolower($trimmed));
    if (!is_string($safeName) || $safeName === '') {
        return null;
    }

    return $prefsDir . DIRECTORY_SEPARATOR . $safeName . '.json';
}

function normalizeShowCategoriesPreference(mixed $value): bool
{
    if (is_bool($value)) {
        return $value;
    }

    if (is_int($value)) {
        return $value !== 0;
    }

    if (is_string($value)) {
        $normalized = strtolower(trim($value));
        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }

    return false;
}

function writeUserPreferences(string $prefsFilePath, string $email, string $themeId, bool $showCategories): void
{
    ensureDirectory(dirname($prefsFilePath));
    $payload = [
        'email' => strtolower(trim($email)),
        'theme' => $themeId,
        'showCategories' => $showCategories,
        'updatedAt' => gmdate('c'),
    ];

    @file_put_contents($prefsFilePath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function loadUserPreferences(string $prefsDir, ?string $email, string $defaultThemeId, array $availableThemeIds): array
{
    $defaultPrefs = [
        'theme' => $defaultThemeId,
        'showCategories' => true,
    ];

    if (!is_string($email) || trim($email) === '') {
        return $defaultPrefs;
    }

    ensureDirectory($prefsDir);
    $prefsFilePath = makeUserPrefsFilePath($prefsDir, $email);
    if ($prefsFilePath === null) {
        return $defaultPrefs;
    }

    if (!is_file($prefsFilePath)) {
        writeUserPreferences($prefsFilePath, $email, $defaultThemeId, true);
        return $defaultPrefs;
    }

    $raw = file_get_contents($prefsFilePath);
    if ($raw === false) {
        writeUserPreferences($prefsFilePath, $email, $defaultThemeId, true);
        return $defaultPrefs;
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        writeUserPreferences($prefsFilePath, $email, $defaultThemeId, true);
        return $defaultPrefs;
    }

    $themeId = strtolower(trim((string) ($decoded['theme'] ?? '')));
    if ($themeId === '' || !in_array($themeId, $availableThemeIds, true)) {
        $themeId = $defaultThemeId;
    }

    $showCategories = array_key_exists('showCategories', $decoded)
        ? normalizeShowCategoriesPreference($decoded['showCategories'])
        : true;

    writeUserPreferences($prefsFilePath, $email, $themeId, $showCategories);

    return [
        'theme' => $themeId,
        'showCategories' => $showCategories,
    ];
}

function getPageNameFromHref(string $href, string $fallbackName): string
{
    $path = parse_url($href, PHP_URL_PATH);
    if (!is_string($path) || trim($path) === '') {
        return $fallbackName;
    }

    $segments = array_values(array_filter(explode('/', trim($path, '/')), static fn($segment) => $segment !== ''));
    if (count($segments) === 0) {
        return $fallbackName;
    }

    $last = $segments[count($segments) - 1];
    if (strcasecmp($last, 'web') === 0 && count($segments) > 1) {
        $last = $segments[count($segments) - 2];
    }

    $decoded = rawurldecode($last);
    if ($decoded === '') {
        return $fallbackName;
    }

    return mb_ucfirst($decoded);
}

function withCorporateHeadingPrefix(string $html, string $pageName): string
{
    $prefix = htmlspecialchars($pageName, ENT_QUOTES, 'UTF-8') . ' - ';
    $updated = preg_replace_callback(
        '~<h3\b([^>]*)>(.*?)</h3>~is',
        static fn($matches) => '<h3' . $matches[1] . '>' . $prefix . ltrim((string) $matches[2]) . '</h3>',
        $html,
        1
    );

    if (is_string($updated) && $updated !== $html) {
        return $updated;
    }

    return '<h3>' . $prefix . '</h3>' . $html;
}

$baseDir = __DIR__;
$themeDir = $baseDir . DIRECTORY_SEPARATOR . '.index-themes';
$prefsDir = $baseDir . DIRECTORY_SEPARATOR . '.index-userprefs';

$themes = loadThemeConfigurations($themeDir);
if (count($themes) === 0) {
    http_response_code(500);
    echo 'Geen thema-configuraties gevonden in .index-themes.';
    exit;
}

$defaultThemeId = getDefaultThemeId($themes);
$themeIds = array_keys($themes);

$currentUserEmail = $_SESSION['user']['email'] ?? null;
$normalizedCurrentUserEmail = normalizeEmailForCompare(is_string($currentUserEmail) ? $currentUserEmail : null);
$userPrefs = loadUserPreferences($prefsDir, $currentUserEmail, $defaultThemeId, $themeIds);
$selectedThemeId = (string) ($userPrefs['theme'] ?? $defaultThemeId);
$showCategoriesEnabled = !empty($userPrefs['showCategories']);

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && (string) ($_POST['action'] ?? '') === 'theme-select'
) {
    $requestedThemeId = strtolower(trim((string) ($_POST['theme'] ?? '')));
    $requestedShowCategories = array_key_exists('showCategories', $_POST);

    if ($requestedThemeId === '' || !in_array($requestedThemeId, $themeIds, true)) {
        $requestedThemeId = $selectedThemeId;
    }

    if (
        is_string($currentUserEmail)
        && trim($currentUserEmail) !== ''
    ) {
        $prefsFilePath = makeUserPrefsFilePath($prefsDir, $currentUserEmail);
        if ($prefsFilePath !== null) {
            writeUserPreferences($prefsFilePath, $currentUserEmail, $requestedThemeId, $requestedShowCategories);
        }
    }

    $redirectTo = strtok((string) ($_SERVER['REQUEST_URI'] ?? '/'), '?');
    header('Location: ' . ($redirectTo !== false ? $redirectTo : '/'));
    exit;
}

$currentTheme = $themes[$selectedThemeId] ?? $themes[$defaultThemeId];
$currentThemeId = (string) $currentTheme['id'];
$currentThemeLogo = (string) ($currentTheme['logo'] ?? 'kvt_logo.png');
$currentThemeLabel = (string) ($currentTheme['label'] ?? 'KVT');

$orderedThemes = $themes;
uksort(
    $orderedThemes,
    static function (string $a, string $b) use ($currentThemeId): int {
        if ($a === $currentThemeId && $b !== $currentThemeId) {
            return -1;
        }

        if ($b === $currentThemeId && $a !== $currentThemeId) {
            return 1;
        }

        return strcasecmp($a, $b);
    }
);

$items = @scandir($baseDir) ?: [];

$folders = [];
foreach ($items as $name) {
    if ($name === '.' || $name === '..' || $name === 'login' || $name === 'mobilreports' || $name === 'veritasreports') {
        continue;
    }

    if (substr($name, 0, 1) === '.') {
        continue;
    }

    $path = $baseDir . DIRECTORY_SEPARATOR . $name;
    if (!is_dir($path)) {
        continue;
    }

    $display = mb_ucfirst($name);
    $href = getCardHref($name, $path);

    $authConfig = getAllowedUsersFromFolderAuth($path);
    $isRestricted = false;
    if ($authConfig['hasAllowedUsers'] === true && $authConfig['allowedUsers'] !== null) {
        $normalizedAllowedUsers = array_values(array_filter(array_map(
            static fn($email) => normalizeEmailForCompare(is_string($email) ? $email : null),
            $authConfig['allowedUsers']
        ), static fn($email) => $email !== null));

        $isRestricted = $normalizedCurrentUserEmail === null || !in_array($normalizedCurrentUserEmail, $normalizedAllowedUsers, true);
    }

    $description = loadDescriptionHtml($path);
    if ($description === '') {
        $description = '<p><em>Geen beschrijving.</em></p>';
    }

    if (!empty($currentTheme['transformDescriptionHeading'])) {
        $pageName = getPageNameFromHref($href, $display);
        $description = withCorporateHeadingPrefix($description, $pageName);
    }

    $folders[] = [
        'name' => $name,
        'display' => $display,
        'category' => loadMetaCategory($path),
        'href' => $href,
        'description' => $description,
        'thumbnail' => getThumbnailPath($name, $path),
        'isRestricted' => $isRestricted,
    ];
}

usort($folders, static fn($a, $b) => strcasecmp((string) $a['display'], (string) $b['display']));

$allowedFolders = [];
$restrictedFolders = [];
foreach ($folders as $folder) {
    if (!empty($folder['isRestricted'])) {
        $restrictedFolders[] = $folder;
        continue;
    }
    $allowedFolders[] = $folder;
}

$allowedFoldersByCategory = [];
$allowedUncategorizedFolders = [];
foreach ($allowedFolders as $folder) {
    $category = trim((string) ($folder['category'] ?? ''));
    if ($category === '') {
        $allowedUncategorizedFolders[] = $folder;
        continue;
    }

    if (!array_key_exists($category, $allowedFoldersByCategory)) {
        $allowedFoldersByCategory[$category] = [];
    }
    $allowedFoldersByCategory[$category][] = $folder;
}

if (count($allowedFoldersByCategory) > 1) {
    uksort($allowedFoldersByCategory, static fn(string $a, string $b): int => strcasecmp($a, $b));
}
?>
<!doctype html>
<html lang="nl">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <title>Index</title>
    <style>
        :root {
            --bg: #f6f7fb;
            --panel: rgba(255, 255, 255, 0.85);
            --panel2: rgba(255, 255, 255, 0.92);
            --text: #0f172a;
            --muted: rgba(15, 23, 42, 0.68);
            --border: rgba(15, 23, 42, 0.1);
            --radius: 18px;
            --section-label-color: rgba(15, 23, 42, 0.72);
            --section-separator-color: rgba(15, 23, 42, 0.18);
            --switcher-bg: rgba(255, 255, 255, 0.84);
            --switcher-border: rgba(15, 23, 42, 0.16);
            --switcher-shadow: 0 14px 26px rgba(15, 23, 42, 0.12);
            --brush-color: rgba(15, 23, 42, 0.42);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial, "Noto Sans", "Liberation Sans", sans-serif;
            color: var(--text);
            background: var(--bg);
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            background:
                radial-gradient(900px 420px at 20% 0%, rgba(99, 102, 241, 0.1), transparent 60%),
                radial-gradient(800px 400px at 85% 10%, rgba(236, 72, 153, 0.08), transparent 60%),
                radial-gradient(900px 520px at 50% 100%, rgba(34, 197, 94, 0.06), transparent 65%);
        }

        .wrap {
            max-width: 1100px;
            margin: 0 auto;
            padding: 28px 18px 56px;
            position: relative;
        }

        header {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 8px 2px 22px;
            margin-bottom: 4px;
        }

        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            user-select: none;
            overflow: hidden;
            font-weight: 800;
            color: rgba(15, 23, 42, 0.55);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 16px;
            margin-top: 10px;
        }

        .section-label {
            margin: 18px 2px 2px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-weight: 700;
            color: var(--section-label-color);
        }

        .restricted-separator {
            margin: 20px 0 14px;
            border-top: 1px solid var(--section-separator-color);
        }

        a.card {
            grid-column: span 4;
            text-decoration: none;
            color: inherit;
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            position: relative;
        }

        .card-topbar {
            display: none;
        }

        .thumbwrap {
            position: relative;
            width: 100%;
            min-height: 190px;
            background: rgba(15, 23, 42, 0.04);
            overflow: hidden;
        }

        img.thumb {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .titlebar {
            display: none;
        }

        .content {
            padding: 14px 16px 16px;
        }

        .desc {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.45;
            margin: 0;
        }

        .desc.is-status-message {
            white-space: pre-line;
        }

        .empty {
            margin-top: 18px;
            padding: 18px;
            border: 1px dashed rgba(15, 23, 42, 0.18);
            border-radius: var(--radius);
            color: var(--muted);
            background: rgba(255, 255, 255, 0.7);
        }

        .theme-switcher {
            position: fixed;
            top: 14px;
            right: 6px;
            z-index: 1500;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .theme-switcher-button {
            width: auto;
            height: auto;
            padding: 4px;
            border-radius: 0;
            border: 0;
            background: transparent;
            color: var(--brush-color);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            line-height: 0;
            transition: transform 0.15s ease, color 0.15s ease;
        }

        .theme-switcher-button:hover,
        .theme-switcher-button:focus-visible {
            transform: translateY(-1px);
            color: rgba(15, 23, 42, 0.55);
            outline: none;
        }

        .theme-switcher-panel {
            width: 188px;
            padding: 10px;
            border-radius: 12px;
            border: 1px solid var(--switcher-border);
            background: rgba(255, 255, 255, 0.96);
            box-shadow: var(--switcher-shadow);
            transform: translateX(calc(100% + 20px));
            opacity: 0;
            pointer-events: none;
            transition: transform 0.22s ease, opacity 0.22s ease;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        .theme-switcher.is-open .theme-switcher-panel {
            transform: translateX(0);
            opacity: 1;
            pointer-events: auto;
        }

        .theme-switcher-title {
            font-size: 12px;
            font-weight: 700;
            color: rgba(15, 23, 42, 0.74);
            margin: 2px 2px 8px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .theme-option {
            width: 100%;
            border: 1px solid rgba(15, 23, 42, 0.12);
            background: #ffffff;
            color: rgba(15, 23, 42, 0.9);
            border-radius: 9px;
            padding: 8px 10px;
            margin: 0 0 6px;
            text-align: left;
            font-size: 13px;
            cursor: pointer;
            transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
        }

        .theme-option:last-child {
            margin-bottom: 0;
        }

        .theme-option:hover,
        .theme-option:focus-visible {
            background: rgba(15, 23, 42, 0.05);
            border-color: rgba(15, 23, 42, 0.28);
            outline: none;
        }

        .theme-option.is-active {
            background: #e2eeff;
            border-color: #93b9f6;
            color: #1a4f9e;
            font-weight: 700;
            cursor: default;
        }

        .theme-switcher-bottom {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid rgba(15, 23, 42, 0.12);
        }

        .theme-checkbox-row {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: rgba(15, 23, 42, 0.86);
            cursor: pointer;
            user-select: none;
        }

        .theme-checkbox-row input {
            margin: 0;
        }

        @media (max-width: 950px) {
            a.card {
                grid-column: span 6;
            }
        }

        @media (max-width: 620px) {
            a.card {
                grid-column: span 12;
            }

            .theme-switcher {
                top: 10px;
                right: 4px;
            }

            .theme-switcher-panel {
                width: 166px;
            }
        }
    </style>
    <?php if ($currentTheme['css'] !== ''): ?>
        <style>
            <?= $currentTheme['css'] ?>
        </style>
    <?php endif; ?>
</head>

<body data-theme="<?= htmlspecialchars($currentThemeId, ENT_QUOTES, 'UTF-8') ?>">
    <div class="theme-switcher" id="themeSwitcher">
        <form id="themeSwitcherPanel" class="theme-switcher-panel" method="post">
            <input type="hidden" name="action" value="theme-select">
            <input type="hidden" name="theme" value="<?= htmlspecialchars($currentThemeId, ENT_QUOTES, 'UTF-8') ?>">
            <div class="theme-switcher-title">Thema</div>
            <?php foreach ($orderedThemes as $themeId => $themeConfig): ?>
                <button type="submit" class="theme-option <?= $themeId === $currentThemeId ? 'is-active' : '' ?>"
                    name="theme" value="<?= htmlspecialchars((string) $themeId, ENT_QUOTES, 'UTF-8') ?>"
                    <?= $themeId === $currentThemeId ? 'disabled' : '' ?>>
                    <?= htmlspecialchars((string) $themeConfig['label'], ENT_QUOTES, 'UTF-8') ?>
                </button>
            <?php endforeach; ?>
            <div class="theme-switcher-bottom">
                <label class="theme-checkbox-row">
                    <input type="checkbox" name="showCategories" value="1" <?= $showCategoriesEnabled ? 'checked' : '' ?>>
                    <span>Categorieen</span>
                </label>
            </div>
        </form>
        <button type="button" class="theme-switcher-button" id="themeSwitcherToggle" aria-expanded="false"
            aria-controls="themeSwitcherPanel" aria-label="Selecteer thema">
            <svg viewBox="0 0 122.88 103.78" width="18" height="18" aria-hidden="true" fill="currentColor">
                <path fill-rule="evenodd"
                    d="M0,103.78c11.7-8.38,30.46.62,37.83-14a16.66,16.66,0,0,0,.62-13.37,10.9,10.9,0,0,0-3.17-4.35,11.88,11.88,0,0,0-2.11-1.35c-9.63-4.78-19.67,1.91-25,10-4.9,7.43-7,16.71-8.18,23.07ZM54.09,43.42a54.31,54.31,0,0,1,15,18.06l50.19-49.16c3.17-3,5-5.53,2.3-10.13A6.5,6.5,0,0,0,117.41,0,7.09,7.09,0,0,0,112.8,1.6L54.09,43.42Zm-16.85,22c2.82,1.52,6.69,5.25,7.61,9.32L65.83,64c-3.78-7.54-8.61-14-15.23-18.58-6.9,9.27-5.5,11.17-13.36,20Z" />
            </svg>
        </button>
    </div>

    <div class="wrap">
        <header>
            <img class="logo" src="<?= htmlspecialchars($currentThemeLogo, ENT_QUOTES, 'UTF-8') ?>"
                alt="<?= htmlspecialchars($currentThemeLabel, ENT_QUOTES, 'UTF-8') ?> logo"
                onerror="this.onerror=null;this.src='kvt_logo.png';" />
        </header>

        <?php if (count($folders) === 0): ?>
            <div class="empty">Geen folders gevonden in deze map.</div>
        <?php else: ?>
            <?php if ($showCategoriesEnabled): ?>
                <?php foreach ($allowedFoldersByCategory as $categoryName => $categoryFolders): ?>
                    <h2 class="section-label"><?= htmlspecialchars((string) $categoryName, ENT_QUOTES, 'UTF-8') ?></h2>
                    <div class="grid">
                        <?php foreach ($categoryFolders as $f): ?>
                            <a class="card" href="<?= htmlspecialchars((string) $f['href'], ENT_QUOTES, 'UTF-8') ?>">
                                <?php if (!empty($currentTheme['showTopBar'])): ?>
                                    <div class="card-topbar" aria-hidden="true"></div>
                                <?php endif; ?>

                                <?php if (!empty($currentTheme['showThumbnail'])): ?>
                                    <div class="thumbwrap">
                                        <?php if ($f['thumbnail']): ?>
                                            <img class="thumb" src="<?= htmlspecialchars((string) $f['thumbnail'], ENT_QUOTES, 'UTF-8') ?>"
                                                alt="">
                                        <?php endif; ?>

                                        <?php if (!empty($currentTheme['showTitlebar'])): ?>
                                            <div class="titlebar">
                                                <h2 class="title"><?= htmlspecialchars((string) $f['display'], ENT_QUOTES, 'UTF-8') ?></h2>
                                                <div class="pill">Open</div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="content <?= !empty($currentTheme['expandDescriptionByDefault']) ? 'is-expanded' : '' ?>">
                                    <div class="desc"><?= $f['description'] ?></div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>

                <?php if (count($allowedUncategorizedFolders) > 0): ?>
                    <h2 class="section-label">Ongecategoriseerd</h2>
                    <div class="grid">
                        <?php foreach ($allowedUncategorizedFolders as $f): ?>
                            <a class="card" href="<?= htmlspecialchars((string) $f['href'], ENT_QUOTES, 'UTF-8') ?>">
                                <?php if (!empty($currentTheme['showTopBar'])): ?>
                                    <div class="card-topbar" aria-hidden="true"></div>
                                <?php endif; ?>

                                <?php if (!empty($currentTheme['showThumbnail'])): ?>
                                    <div class="thumbwrap">
                                        <?php if ($f['thumbnail']): ?>
                                            <img class="thumb" src="<?= htmlspecialchars((string) $f['thumbnail'], ENT_QUOTES, 'UTF-8') ?>"
                                                alt="">
                                        <?php endif; ?>

                                        <?php if (!empty($currentTheme['showTitlebar'])): ?>
                                            <div class="titlebar">
                                                <h2 class="title"><?= htmlspecialchars((string) $f['display'], ENT_QUOTES, 'UTF-8') ?></h2>
                                                <div class="pill">Open</div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="content <?= !empty($currentTheme['expandDescriptionByDefault']) ? 'is-expanded' : '' ?>">
                                    <div class="desc"><?= $f['description'] ?></div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="grid">
                    <?php foreach ($allowedFolders as $f): ?>
                        <a class="card" href="<?= htmlspecialchars((string) $f['href'], ENT_QUOTES, 'UTF-8') ?>">
                            <?php if (!empty($currentTheme['showTopBar'])): ?>
                                <div class="card-topbar" aria-hidden="true"></div>
                            <?php endif; ?>

                            <?php if (!empty($currentTheme['showThumbnail'])): ?>
                                <div class="thumbwrap">
                                    <?php if ($f['thumbnail']): ?>
                                        <img class="thumb" src="<?= htmlspecialchars((string) $f['thumbnail'], ENT_QUOTES, 'UTF-8') ?>"
                                            alt="">
                                    <?php endif; ?>

                                    <?php if (!empty($currentTheme['showTitlebar'])): ?>
                                        <div class="titlebar">
                                            <h2 class="title"><?= htmlspecialchars((string) $f['display'], ENT_QUOTES, 'UTF-8') ?></h2>
                                            <div class="pill">Open</div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="content <?= !empty($currentTheme['expandDescriptionByDefault']) ? 'is-expanded' : '' ?>">
                                <div class="desc"><?= $f['description'] ?></div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (count($allowedFolders) > 0 && count($restrictedFolders) > 0): ?>
                <div class="restricted-separator" aria-hidden="true"></div>
            <?php endif; ?>

            <?php if (count($restrictedFolders) > 0): ?>
                <?php if ($showCategoriesEnabled): ?>
                    <h2 class="section-label">Geen Toegang</h2>
                <?php endif; ?>
                <div class="grid">
                    <?php foreach ($restrictedFolders as $f): ?>
                        <a class="card is-restricted" href="<?= htmlspecialchars((string) $f['href'], ENT_QUOTES, 'UTF-8') ?>">
                            <?php if (!empty($currentTheme['showTopBar'])): ?>
                                <div class="card-topbar" aria-hidden="true"></div>
                            <?php endif; ?>

                            <?php if (!empty($currentTheme['showThumbnail'])): ?>
                                <div class="thumbwrap">
                                    <?php if ($f['thumbnail']): ?>
                                        <img class="thumb" src="<?= htmlspecialchars((string) $f['thumbnail'], ENT_QUOTES, 'UTF-8') ?>"
                                            alt="">
                                    <?php endif; ?>

                                    <?php if (!empty($currentTheme['showTitlebar'])): ?>
                                        <div class="titlebar">
                                            <h2 class="title"><?= htmlspecialchars((string) $f['display'], ENT_QUOTES, 'UTF-8') ?></h2>
                                            <div class="pill">Open</div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="content <?= !empty($currentTheme['expandDescriptionByDefault']) ? 'is-expanded' : '' ?>">
                                <div class="desc"><?= $f['description'] ?></div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
        (function ()
        {
            const switcher = document.getElementById('themeSwitcher');
            const toggleButton = document.getElementById('themeSwitcherToggle');
            const panel = document.getElementById('themeSwitcherPanel');
            const categoriesCheckbox = panel ? panel.querySelector('input[name="showCategories"]') : null;

            if (!switcher || !toggleButton || !panel) return;

            const setOpen = (open) =>
            {
                switcher.classList.toggle('is-open', open);
                toggleButton.setAttribute('aria-expanded', open ? 'true' : 'false');
            };

            toggleButton.addEventListener('click', (event) =>
            {
                event.preventDefault();
                setOpen(!switcher.classList.contains('is-open'));
            });

            document.addEventListener('click', (event) =>
            {
                if (!switcher.contains(event.target))
                {
                    setOpen(false);
                }
            });

            document.addEventListener('keydown', (event) =>
            {
                if (event.key === 'Escape')
                {
                    setOpen(false);
                }
            });

            if (categoriesCheckbox)
            {
                categoriesCheckbox.addEventListener('change', () =>
                {
                    panel.requestSubmit();
                });
            }
        })();
    </script>
    <?php if ($currentTheme['js'] !== ''): ?>
        <script>
            <?= $currentTheme['js'] ?>
        </script>
    <?php endif; ?>
</body>

</html>