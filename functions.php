<?php 
namespace Deployer;

function createFileIfNotExists($path) : bool
{
    if (!test("[ -f {$path} ]")) {
        run("mkdir -p $(dirname {$path})");
        run("touch {$path}");
        return true;
    }
    
    return false;
}

function runWpQuery($filename) {
    $deployPath = get('deploy_path');
    $webRoot = get('web_root');
    
    $query = file_get_contents(__DIR__ . '/snippets/' . ltrim($filename, '/') . '.sql');

    // Extracting placeholders and default values
    preg_match_all('/{{\s(.*?)(?::(.*?))?\s}}/', $query, $matches, PREG_SET_ORDER);

    $url = parse_url(get('url'), PHP_URL_HOST);
    $defaults = [
        'domain_no_extension' => preg_replace('/\.[^.]*$/', '', $url),
        'domain_extension' => $url,
    ];
    
    foreach($matches as $match) {
        $replace = $match[0];
        $key = $match[1];
        $defaultValue = $match[2] ?? $defaults[$key] ?? '';
        if (get($key) && get($key) !== '') {
            $value = get($key);
        } else {
            $value = ask("Enter a value for {$key}", $defaultValue);
        }
        $query = str_replace($replace, $value, $query);        
    }

    $query = trim(str_replace("'", "\"", $query));
    return run("wp db query '{$query}' --path={$deployPath}/current/{$webRoot}/wp");
}

function requestHeaders()
{
    $headers = [];
    $basicAuthUser = get('basic_auth_user');
    $basicAuthPass = get('basic_auth_pass');

    if ($basicAuthUser && $basicAuthPass) {
        $base64EncodedString = base64_encode("{$basicAuthUser}:{$basicAuthPass}");
        $headers['Authorization'] = "Basic {$base64EncodedString}";
    }
    return $headers;
}
