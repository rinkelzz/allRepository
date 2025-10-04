<?php
$config = require __DIR__ . '/config.php';

if (!is_array($config)) {
    $config = [];
}

$defaultOwner = trim((string) ($config['owner'] ?? 'symfony'));
$defaultRepo = trim((string) ($config['default_repository'] ?? ''));
$pageTitle = trim((string) ($config['title'] ?? 'GitHub Repository Explorer'));
$welcomeMessage = trim((string) ($config['welcome_message'] ?? 'Erkunde GitHub Repositories inklusive Besitzerinformationen und README.'));

$owner = $defaultOwner;
$repo = trim(filter_input(INPUT_GET, 'repo', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');

if ($repo === '') {
    $repo = $defaultRepo !== '' ? $defaultRepo : $repo;
}

function fetchGithubData(string $url): ?array
{
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: PHP Github Viewer\r\nAccept: application/vnd.github+json\r\n",
            'ignore_errors' => true,
            'timeout' => 10,
        ],
    ]);

    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        return null;
    }

    $decoded = json_decode($response, true);

    if (!is_array($decoded)) {
        return null;
    }

    return $decoded;
}

$repositoriesData = fetchGithubData("https://api.github.com/users/{$owner}/repos?per_page=100&sort=updated");

if (!is_array($repositoriesData)) {
    $repositoriesData = [];
}

$availableRepositoryNames = array_map(static fn ($repository) => $repository['name'] ?? '', $repositoriesData);
$availableRepositoryNames = array_filter($availableRepositoryNames, static fn ($name) => is_string($name) && $name !== '');

if ($repo === '' && !empty($availableRepositoryNames)) {
    $repo = reset($availableRepositoryNames);
}

if (!in_array($repo, $availableRepositoryNames, true) && !empty($availableRepositoryNames)) {
    $repo = reset($availableRepositoryNames);
}

$repositoryData = $repo !== '' ? fetchGithubData("https://api.github.com/repos/{$owner}/{$repo}") : null;
$readmeData = $repo !== '' ? fetchGithubData("https://api.github.com/repos/{$owner}/{$repo}/readme") : null;
$userData = fetchGithubData("https://api.github.com/users/{$owner}");

$readmeContent = '';

if (is_array($readmeData) && isset($readmeData['content'])) {
    $readmeContent = base64_decode($readmeData['content']);
}

function formatCount(?int $count): string
{
    if ($count === null) {
        return 'N/A';
    }

    if ($count >= 1000) {
        return number_format($count / 1000, 1) . 'k';
    }

    return (string) $count;
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f8;
            margin: 0;
            padding: 0;
            color: #1f2328;
        }
        header {
            background: #24292f;
            color: #ffffff;
            padding: 20px;
            text-align: center;
        }
        main {
            max-width: 960px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.15);
            overflow: hidden;
        }
        .form-container {
            padding: 20px;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
        }
        .form-container form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .form-container label {
            font-weight: 600;
        }
        .form-container input[type="text"],
        .form-container select {
            flex: 1;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            font-size: 1rem;
            background-color: #ffffff;
        }
        .form-container button {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            background-color: #2da44e;
            color: white;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.2s ease-in-out;
        }
        .form-container button:hover {
            background-color: #238636;
        }
        .content {
            padding: 20px;
        }
        .repository-header {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        @media (min-width: 768px) {
            .repository-header {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }
        }
        .download-action {
            display: flex;
            flex-direction: column;
            gap: 6px;
            align-items: flex-start;
        }
        .download-button {
            display: inline-block;
            padding: 10px 16px;
            border-radius: 8px;
            background: linear-gradient(135deg, #2da44e, #238636);
            color: #ffffff;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(45, 164, 78, 0.3);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .download-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(45, 164, 78, 0.35);
        }
        .download-action small {
            color: #57606a;
        }
        .intro {
            background: #eef6ff;
            border-left: 4px solid #0969da;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .error {
            color: #dc2626;
            font-weight: 600;
        }
        .repository-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .meta-card {
            background: #f9fafb;
            border-radius: 10px;
            padding: 15px;
            border: 1px solid #e5e7eb;
        }
        h2 {
            margin-top: 0;
        }
        pre {
            background: #0d1117;
            color: #e6edf3;
            padding: 20px;
            border-radius: 10px;
            overflow-x: auto;
            white-space: pre-wrap;
            word-break: break-word;
            font-family: "Fira Code", "Source Code Pro", monospace;
        }
        .owner {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 10px;
        }
        .owner img {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            border: 2px solid #d0d7de;
        }
        a {
            color: #0969da;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<header>
    <h1><?= htmlspecialchars($pageTitle, ENT_QUOTES) ?></h1>
    <p><?= htmlspecialchars($welcomeMessage, ENT_QUOTES) ?></p>
</header>
<main>
    <div class="form-container">
        <form method="get">
            <label for="repo">Repository von <strong><?= htmlspecialchars($owner, ENT_QUOTES) ?></strong>:</label>
            <?php if (!empty($availableRepositoryNames)): ?>
                <select id="repo" name="repo">
                    <?php foreach ($availableRepositoryNames as $repositoryName): ?>
                        <option value="<?= htmlspecialchars($repositoryName, ENT_QUOTES) ?>" <?= $repositoryName === $repo ? 'selected' : '' ?>>
                            <?= htmlspecialchars($repositoryName, ENT_QUOTES) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <input type="text" id="repo" name="repo" value="<?= htmlspecialchars($repo, ENT_QUOTES) ?>" required>
            <?php endif; ?>
            <button type="submit">Laden</button>
        </form>
        <p>Aktueller Besitzer: <strong><a href="https://github.com/<?= htmlspecialchars($owner, ENT_QUOTES) ?>" target="_blank" rel="noopener">github.com/<?= htmlspecialchars($owner, ENT_QUOTES) ?></a></strong></p>
    </div>
    <div class="content">
        <div class="intro">
            <p>Schön, dass du hier bist! Wähle ein Repository aus, um freundliche Einblicke in die Projekte von <?= htmlspecialchars($owner, ENT_QUOTES) ?> zu erhalten.</p>
        </div>
        <?php if ($repo === ''): ?>
            <p class="error">Bitte wähle ein Repository aus der Liste aus.</p>
        <?php elseif (!$repositoryData): ?>
            <p class="error">Das Repository konnte nicht geladen werden. Bitte versuche es später erneut.</p>
        <?php else: ?>
            <?php
                $defaultBranch = trim((string) ($repositoryData['default_branch'] ?? 'main'));
                $downloadBranch = $defaultBranch !== '' ? $defaultBranch : 'main';
                $zipDownloadUrl = "https://github.com/{$owner}/{$repo}/archive/refs/heads/" . rawurlencode($downloadBranch) . '.zip';
            ?>
            <section>
                <div class="repository-header">
                    <div>
                        <h2>Repository: <a href="<?= htmlspecialchars($repositoryData['html_url'] ?? '#', ENT_QUOTES) ?>" target="_blank" rel="noopener">
                            <?= htmlspecialchars($repositoryData['full_name'] ?? ($owner . '/' . $repo), ENT_QUOTES) ?>
                        </a></h2>
                        <p><?= htmlspecialchars($repositoryData['description'] ?? 'Keine Beschreibung verfügbar.', ENT_QUOTES) ?></p>
                    </div>
                    <div class="download-action">
                        <a class="download-button" href="<?= htmlspecialchars($zipDownloadUrl, ENT_QUOTES) ?>" target="_blank" rel="noopener">
                            Main-Zip herunterladen
                        </a>
                        <small>Branch: <?= htmlspecialchars($downloadBranch, ENT_QUOTES) ?></small>
                    </div>
                </div>
                <div class="repository-meta">
                    <div class="meta-card">
                        <strong>Sterne:</strong>
                        <div><?= htmlspecialchars(formatCount($repositoryData['stargazers_count'] ?? null)) ?></div>
                    </div>
                    <div class="meta-card">
                        <strong>Forks:</strong>
                        <div><?= htmlspecialchars(formatCount($repositoryData['forks_count'] ?? null)) ?></div>
                    </div>
                    <div class="meta-card">
                        <strong>Watchers:</strong>
                        <div><?= htmlspecialchars(formatCount($repositoryData['subscribers_count'] ?? null)) ?></div>
                    </div>
                    <div class="meta-card">
                        <strong>Open Issues:</strong>
                        <div><?= htmlspecialchars(formatCount($repositoryData['open_issues_count'] ?? null)) ?></div>
                    </div>
                </div>
            </section>

            <?php if ($userData): ?>
                <section>
                    <h2>Owner</h2>
                    <div class="owner">
                        <?php if (!empty($userData['avatar_url'])): ?>
                            <img src="<?= htmlspecialchars($userData['avatar_url'], ENT_QUOTES) ?>" alt="Avatar von <?= htmlspecialchars($userData['login'] ?? $owner, ENT_QUOTES) ?>">
                        <?php endif; ?>
                        <div>
                            <div><strong><?= htmlspecialchars($userData['name'] ?? $userData['login'] ?? $owner, ENT_QUOTES) ?></strong></div>
                            <?php if (!empty($userData['bio'])): ?>
                                <div><?= htmlspecialchars($userData['bio'], ENT_QUOTES) ?></div>
                            <?php endif; ?>
                            <div><a href="<?= htmlspecialchars($userData['html_url'] ?? ('https://github.com/' . $owner), ENT_QUOTES) ?>" target="_blank" rel="noopener">GitHub Profil</a></div>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <section>
                <h2>README</h2>
                <?php if ($readmeContent): ?>
                    <pre><?= htmlspecialchars($readmeContent) ?></pre>
                <?php else: ?>
                    <p>Für dieses Repository wurde kein README gefunden.</p>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
