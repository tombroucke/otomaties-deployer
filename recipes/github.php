<?php
/**
 * Deployer recipe: GitHub Actions deploy key
 *
 *   dep github:deploy-key production
 *
 * 1. Generates a fresh ed25519 key locally (temp dir, no passphrase)
 * 2. Adds the public key to ~/.ssh/authorized_keys on every selected host
 *    (manual hosts: prints the key and waits until you've added it in the control panel)
 * 3. Tests that the new key can log in
 * 4. Stores DEPLOY_SSH_KEY and SSH_KNOWN_HOSTS as GitHub Actions secrets (via gh CLI),
 *    and registers the key as a read-only Deploy key on the repo, so the server can
 *    `git fetch` through the forwarded agent during a CI deploy
 * 5. Removes older keys for this repo from authorized_keys (rotation)
 * 6. Deletes the local private key
 *
 * Safe to re-run: re-running = rotating the key.
 *
 * Requirements: gh CLI logged in (`gh auth login`) with admin access to the repo.
 *
 * Settings (in deploy.php, globally or per host):
 *   set('github_deploy_key_repo', 'owner/repo');            // default: derived from `repository`
 *   host('production')->set('github_deploy_key_manual', true); // keys managed in a hosting
 *                                                           // control panel (e.g. Combell)
 * Or for a single run: dep github:deploy-key production -o github_deploy_key_manual=true
 */
 
namespace Deployer;
 
use Deployer\Exception\RunException;
 
set('github_deploy_key_repo', function () {
    $repository = get('repository');
    if (preg_match('#github\.com[:/]([^/]+/[^/]+?)(?:\.git)?/?$#', $repository, $m)) {
        return $m[1];
    }
    throw new \RuntimeException(
        "Can't derive a GitHub repo from '{$repository}'. Set github_deploy_key_repo in deploy.php."
    );
});
 
// Keys are managed in a hosting control panel instead of ~/.ssh/authorized_keys.
set('github_deploy_key_manual', false);
 
// Comment on the key line; used to find (and rotate) this repo's keys.
set('github_deploy_key_marker', fn () => 'github-actions-deploy:' . get('github_deploy_key_repo'));
 
set('github_deploy_key_path', fn () => sys_get_temp_dir() . '/gha-deploy-' . md5(get('github_deploy_key_repo')));
 
function githubDeployKeyManual(): bool
{
    return filter_var(get('github_deploy_key_manual'), FILTER_VALIDATE_BOOLEAN);
}
 
function githubDeployKeyTarget(\Deployer\Host\Host $host): string
{
    return ($host->getRemoteUser() ? $host->getRemoteUser() . '@' : '') . $host->getHostname();
}
 
function githubDeployKeyHostLookup(\Deployer\Host\Host $host): string
{
    $port = $host->getPort() ?: 22;
    return $port == 22 ? $host->getHostname() : sprintf('[%s]:%d', $host->getHostname(), $port);
}
 
task('github:deploy-key:generate', function () {
    runLocally('gh auth status');
 
    $key = get('github_deploy_key_path');
    @unlink($key);
    @unlink("$key.pub");
 
    runLocally(sprintf(
        'ssh-keygen -q -t ed25519 -N "" -C %s -f %s',
        escapeshellarg(get('github_deploy_key_marker')),
        escapeshellarg($key)
    ));
    info('Generated new deploy key for <info>' . get('github_deploy_key_repo') . '</info>');
})->once()->hidden();
 
task('github:deploy-key:authorize', function () {
    $pubRaw = trim(file_get_contents(get('github_deploy_key_path') . '.pub'));
 
    if (githubDeployKeyManual()) {
        writeln('');
        writeln('<comment>Add this public key in the control panel for ' . githubDeployKeyTarget(currentHost()) . ':</comment>');
        writeln('');
        writeln($pubRaw);
        writeln('');
        if (!askConfirmation('Have you added the key?', false)) {
            throw new \RuntimeException('Aborted: key not added.');
        }
        return;
    }
 
    $pub = escapeshellarg($pubRaw);
    run('mkdir -p ~/.ssh && chmod 700 ~/.ssh && touch ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys');
 
    // If the file doesn't end with a newline, add one first. Otherwise the new key
    // would be glued onto the last existing key's line (and sshd would ignore it).
    run('if [ -s ~/.ssh/authorized_keys ] && [ -n "$(tail -c1 ~/.ssh/authorized_keys)" ]; then echo >> ~/.ssh/authorized_keys; fi');
 
    run("grep -qxF $pub ~/.ssh/authorized_keys || echo $pub >> ~/.ssh/authorized_keys");
})->hidden()->limit(1); // one host at a time, so prompts don't interleave
 
task('github:deploy-key:test', function () {
    $host   = currentHost();
    $target = githubDeployKeyTarget($host);
    $cmd    = sprintf(
        'ssh -i %s -o IdentitiesOnly=yes -o BatchMode=yes -o StrictHostKeyChecking=accept-new -p %d %s true',
        escapeshellarg(get('github_deploy_key_path')),
        $host->getPort() ?: 22,
        escapeshellarg($target)
    );
 
    // Control panels can take a moment to push the key to the server.
    $attempts = githubDeployKeyManual() ? 12 : 1;
    for ($i = 1; ; $i++) {
        try {
            runLocally($cmd);
            break;
        } catch (RunException $e) {
            if ($i >= $attempts) {
                throw $e;
            }
            writeln("Key not active yet on {$target}, retrying in 10s ({$i}/{$attempts})…");
            sleep(10);
        }
    }
    info("New key works on <info>{$target}</info>");
})->hidden();
 
task('github:deploy-key:secrets', function () {
    $repo = escapeshellarg(get('github_deploy_key_repo'));
 
    runLocally(sprintf(
        'gh secret set DEPLOY_SSH_KEY --repo %s < %s',
        $repo,
        escapeshellarg(get('github_deploy_key_path'))
    ));
 
    // Use host keys from your own known_hosts (already trusted by you),
    // rather than a fresh ssh-keyscan.
    $known = [];
    foreach (selectedHosts() as $host) {
        $lookup = githubDeployKeyHostLookup($host);
        $lines  = runLocally(sprintf('ssh-keygen -F %s | grep -v "^#" || true', escapeshellarg($lookup)));
        if (trim($lines) === '') {
            throw new \RuntimeException("No entry for {$lookup} in your known_hosts. SSH into it once first.");
        }
        $known[] = trim($lines);
    }
 
    $tmp = tempnam(sys_get_temp_dir(), 'gha-known-hosts');
    file_put_contents($tmp, implode("\n", array_unique($known)) . "\n");
    try {
        runLocally(sprintf('gh secret set SSH_KNOWN_HOSTS --repo %s < %s', $repo, escapeshellarg($tmp)));
    } finally {
        unlink($tmp);
    }
 
    info('Secrets DEPLOY_SSH_KEY and SSH_KNOWN_HOSTS set on <info>' . get('github_deploy_key_repo') . '</info>');
 
    // Also register the key as a read-only Deploy key on the repo. During a CI deploy, Deployer
    // forwards the runner's SSH agent, so the server's `git fetch` uses this same key.
    // Replace any previous key added by this recipe (matched by title).
    $title = get('github_deploy_key_marker');
    $ids   = runLocally(sprintf(
        'gh repo deploy-key list --repo %s --json id,title --jq %s',
        $repo,
        escapeshellarg('.[] | select(.title == "' . $title . '") | .id')
    ));
    foreach (array_filter(array_map('trim', explode("\n", $ids))) as $id) {
        runLocally(sprintf('gh repo deploy-key delete %d --repo %s', (int) $id, $repo));
    }
    runLocally(sprintf(
        'gh repo deploy-key add %s --repo %s --title %s',
        escapeshellarg(get('github_deploy_key_path') . '.pub'),
        $repo,
        escapeshellarg($title)
    ));
    info('Read-only deploy key added to <info>' . get('github_deploy_key_repo') . '</info>');
})->once()->hidden();
 
task('github:deploy-key:prune', function () {
    if (githubDeployKeyManual()) {
        warning('If this was a rotation, remove the OLD key labelled "' . get('github_deploy_key_marker')
            . '" from the control panel for ' . githubDeployKeyTarget(currentHost()) . '.');
        return;
    }
 
    $pub    = escapeshellarg(trim(file_get_contents(get('github_deploy_key_path') . '.pub')));
    $marker = escapeshellarg(get('github_deploy_key_marker'));
 
    // Drop only lines that are exactly "<type> <key> <marker>" (3 fields), then re-add the new key.
    // A line where a key got glued onto another key has more fields and is left alone.
    run(sprintf(
        'awk -v m=%s \'!(NF == 3 && $3 == m)\' ~/.ssh/authorized_keys > ~/.ssh/authorized_keys.tmp'
        . ' && echo %s >> ~/.ssh/authorized_keys.tmp'
        . ' && cat ~/.ssh/authorized_keys.tmp > ~/.ssh/authorized_keys'
        . ' && rm ~/.ssh/authorized_keys.tmp',
        $marker,
        $pub
    ));
})->hidden();
 
// On failure: remove exactly the line we added (whole-line match), nothing else.
task('github:deploy-key:revoke', function () {
    $file = get('github_deploy_key_path') . '.pub';
    if (!file_exists($file)) {
        return;
    }
 
    if (githubDeployKeyManual()) {
        warning('If you added the new key in the control panel for ' . githubDeployKeyTarget(currentHost())
            . ', remove it again. It ends with: ' . substr(trim(file_get_contents($file)), -60));
        return;
    }
 
    $pub = escapeshellarg(trim(file_get_contents($file)));
    run("if grep -qxF $pub ~/.ssh/authorized_keys; then"
        . " grep -vxF $pub ~/.ssh/authorized_keys > ~/.ssh/authorized_keys.tmp;"
        . " cat ~/.ssh/authorized_keys.tmp > ~/.ssh/authorized_keys && rm ~/.ssh/authorized_keys.tmp; fi");
})->hidden();
 
task('github:deploy-key:cleanup', function () {
    $key = get('github_deploy_key_path');
    @unlink($key);
    @unlink("$key.pub");
})->once()->hidden();
 
desc('Create/rotate the GitHub Actions deploy key and store it as repo secrets');
task('github:deploy-key', [
    'github:deploy-key:generate',
    'github:deploy-key:authorize',
    'github:deploy-key:test',
    'github:deploy-key:secrets',
    'github:deploy-key:prune',
    'github:deploy-key:cleanup',
]);
 
task('github:deploy-key:rollback', [
    'github:deploy-key:revoke',
    'github:deploy-key:cleanup',
])->hidden();
 
fail('github:deploy-key', 'github:deploy-key:rollback');
 
/*
 * dep github:composer-auth
 *
 * Stores the project's auth.json as the COMPOSER_AUTH Actions secret.
 * Only the project-level auth.json is used, never your global ~/.composer/auth.json
 * (that one usually holds tokens for every project, e.g. your GitHub OAuth token).
 */
set('github_composer_auth_file', fn () => getcwd() . '/auth.json');
 
desc('Store the project auth.json as the COMPOSER_AUTH GitHub Actions secret');
task('github:composer-auth', function () {
    $file = get('github_composer_auth_file');
    if (!is_file($file)) {
        throw new \RuntimeException("No auth.json found at {$file}.");
    }
 
    $auth = json_decode(file_get_contents($file), true);
    if (!is_array($auth) || $auth === []) {
        throw new \RuntimeException("{$file} is not valid JSON or is empty.");
    }
 
    // Safety net: it must not be committed.
    $tracked = runLocally(sprintf('git ls-files -- %s', escapeshellarg($file)));
    if (trim($tracked) !== '') {
        throw new \RuntimeException('auth.json is tracked by git. Remove it from the repo (and rotate the credentials) first.');
    }
 
    runLocally('gh auth status');
    runLocally(sprintf(
        'gh secret set COMPOSER_AUTH --repo %s < %s',
        escapeshellarg(get('github_deploy_key_repo')),
        escapeshellarg($file)
    ));
 
    // e.g. {"http-basic": {"connect.advancedcustomfields.com": {...}}} → list the hosts, never the values
    $hosts = [];
    foreach ($auth as $section) {
        if (is_array($section)) {
            $hosts = array_merge($hosts, array_keys($section));
        }
    }
    info('COMPOSER_AUTH set on <info>' . get('github_deploy_key_repo') . '</info> (for: '
        . implode(', ', array_unique($hosts)) . ')');
})->once();

/*
 * dep github:basic-auth
 *
 * Reads BASIC_AUTH_USER and BASIC_AUTH_PASS from the local .env and stores
 * them as the DEPLOY_BASIC_AUTH Actions secret (format: "user:pass").
 *
 * The deploy workflow writes a minimal .env from this secret before running
 * Deployer, so combell:reloadPHP and combell:reset_opcode_cache can make
 * authenticated HTTP requests to the staging site.
 *
 * Run once per project: dep github:basic-auth production
 */
desc('Store basic auth credentials as the DEPLOY_BASIC_AUTH GitHub Actions secret');
task('github:basic-auth', function () {
    $envFile = getcwd() . '/.env';
    if (!is_file($envFile)) {
        throw new \RuntimeException("No .env found at {$envFile}.");
    }

    $vars = [];
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
        $vars[trim($key)] = trim($value, " \t\"'");
    }

    $user = $vars['BASIC_AUTH_USER'] ?? '';
    $pass = $vars['BASIC_AUTH_PASS'] ?? '';

    if ($user === '' || $pass === '') {
        throw new \RuntimeException('BASIC_AUTH_USER and BASIC_AUTH_PASS must both be set in .env.');
    }

    if (str_contains($user, ':') || str_contains($pass, ':')) {
        throw new \RuntimeException('Basic auth credentials must not contain a colon.');
    }

    $repo = escapeshellarg(get('github_deploy_key_repo'));
    $tmp  = tempnam(sys_get_temp_dir(), 'gha-basic-auth');
    file_put_contents($tmp, "{$user}:{$pass}");

    try {
        runLocally('gh auth status');
        runLocally(sprintf('gh secret set DEPLOY_BASIC_AUTH --repo %s < %s', $repo, escapeshellarg($tmp)));
    } finally {
        unlink($tmp);
    }

    info('DEPLOY_BASIC_AUTH set on <info>' . get('github_deploy_key_repo') . '</info>');
})->once();
