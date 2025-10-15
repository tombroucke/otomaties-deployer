<?php

namespace Otomaties\Deployer;

use Illuminate\Support\Str;

use function Deployer\ask;
use function Deployer\get;
use function Deployer\run;
use function Deployer\test;
use function Deployer\within;
use function Deployer\writeln;

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

function replacePlaceholders(string|int|null $text): string
{
    if (is_null($text) || is_int($text)) {
        return (string) $text;
    }

    preg_match_all('/{{\s(.*?)(?::(.*?))?\s}}/', $text, $matches, PREG_SET_ORDER);

    $url = parse_url(get('url'), PHP_URL_HOST);

    $defaults = [
        'wordfence_domain_no_extension' => is_string($url) ? preg_replace('/\.[^.]*$/', '', $url) : null,
        'wordfence_domain_extension' => is_string($url) ? $url : null,
    ];

    collect($matches)
        ->each(function ($match) use (&$text, $defaults) {
            $replace = $match[0];
            $key = $match[1];
            $defaultValue = $match[2] ?? $defaults[$key] ?? '';
            $value = get($key);

            if (! filled($value)) {
                $defaultValue = filled($defaultValue) ? $defaultValue : null;
                $value = ask("Enter a value for {$key}", $defaultValue);
            }

            $text = str_replace($replace, $value, $text);
        });

    return trim(str_replace("'", '"', $text));
}

/**
 * Basic Auth headers for HTTP requests.
 *
 * @return array<string, string>
 */
function basicAuthRequestHeaders(): array
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
    return (string) Str::of($path)
        ->replace(['\\', '//'], '/')
        ->replace(['../', './'], '');
}

function url(?string $filePath): string
{
    return (string) Str::of(get('url'))
        ->rtrim('/')
        ->append('/')
        ->append($filePath ? ltrim($filePath, '/') : '');
}
