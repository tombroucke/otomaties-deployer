<?php

namespace Deployer;

use Illuminate\Support\Str;

function createFileIfNotExists(string $path): bool
{
    if (! test("[ -f {$path} ]")) {
        run("mkdir -p $(dirname {$path})");
        run("touch {$path}");

        return true;
    }

    return false;
}

function runWpQuery(string $cmd, string $path = '{{release_path}}'): mixed
{
    $cmd = str_starts_with($cmd, 'wp ') ? $cmd : "wp {$cmd}";

    writeln("<info>Running WP CLI command:</info> <comment>{$cmd}</comment> in <comment>{$path}</comment>");

    return within($path, function () use ($cmd) {
        return run(
            command: $cmd,
            real_time_output: true,
        );
    });
}

function replacePlaceholders(string $query): string
{
    preg_match_all('/{{\s(.*?)(?::(.*?))?\s}}/', $query, $matches, PREG_SET_ORDER);

    $url = parse_url(get('url'), PHP_URL_HOST);

    $defaults = [
        'domain_no_extension' => preg_replace('/\.[^.]*$/', '', $url),
        'domain_extension' => $url,
    ];

    collect($matches)
        ->each(function ($match) use (&$query, $defaults) {
            $replace = $match[0];
            $key = $match[1];
            $defaultValue = $match[2] ?? $defaults[$key] ?? '';
            $value = get($key);

            if (! filled($value)) {
                $defaultValue = filled($defaultValue) ? $defaultValue : null;
                $value = ask("Enter a value for {$key}", $defaultValue);
            }

            $query = str_replace($replace, $value, $query);
        });

    return trim(str_replace("'", '"', $query));
}

function requestHeaders(): array
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

function generateSalt(): string
{
    $salt = '';
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#%^&*()-_[]{}<>~+=,.;:/?|';

    for ($i = 0; $i < 64; $i++) {
        $salt .= substr($chars, random_int(0, strlen($chars) - 1), 1);
    }

    return $salt;
}

function cleanPath(string $path): string
{
    return Str::of($path)
        ->replace(['\\', '//'], '/')
        ->replace(['../', './'], '')
        ->toString();
}
