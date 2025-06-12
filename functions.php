<?php

namespace Deployer;

use Illuminate\Support\Str;

function createFileIfNotExists($path): bool
{
    if (! test("[ -f {$path} ]")) {
        run("mkdir -p $(dirname {$path})");
        run("touch {$path}");

        return true;
    }

    return false;
}

function runWpQuery($cmd, $path = '{{release_path}}')
{
    if (! str_starts_with($cmd, 'wp ')) {
        $cmd = "wp {$cmd}";
    }

    writeln("<info>Running WP CLI command:</info> <comment>{$cmd}</comment> in <comment>{$path}</comment>");
    within($path, function () use ($cmd) {
        return run(
            command: $cmd,
            real_time_output: true,
        );
    });
}

function runWpDbQuery($filename)
{
    $query = file_get_contents(__DIR__ . '/snippets/' . ltrim($filename, '/') . '.sql');

    // Extracting placeholders and default values
    preg_match_all('/{{\s(.*?)(?::(.*?))?\s}}/', $query, $matches, PREG_SET_ORDER);

    $url = parse_url(get('url'), PHP_URL_HOST);
    $defaults = [
        'domain_no_extension' => preg_replace('/\.[^.]*$/', '', $url),
        'domain_extension' => $url,
    ];

    foreach ($matches as $match) {
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

    $query = trim(str_replace("'", '"', $query));

    runWpQuery("wp db query '{$query}'");
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

function createSlug(string $string): string
{
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', '-', trim($string));

    return $string;
}

function generateSalt()
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#%^&*()-_[]{}<>~+=,.;:/?|';
    $charOptionLength = strlen($chars) - 1;

    $password = '';
    for ($i = 0; $i < 64; $i++) {
        $password .= substr($chars, random_int(0, $charOptionLength), 1);
    }

    return $password;
}

function cleanPath($path)
{
    return Str::of($path)
        ->replace(['\\', '//'], '/')
        ->replace(['../', './'], '')
        ->rtrim('/')
        ->append('/')
        ->toString();
}
