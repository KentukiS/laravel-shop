<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;
use Throwable;

class CreateWordPressDraftPost extends Command
{
    protected $signature = 'wp:create-draft
        {site : WordPress site URL}
        {username : WordPress username}
        {password : WordPress password}
        {--title=Test Draft Post : Post title}
        {--content=This is a test draft post created from Laravel. : Post content}
        {--timeout=10 : Request timeout in seconds}';

    protected $description = 'Create a draft post on a WordPress site using username and password.';

    public function handle(): int
    {
        $site = $this->normalizeUrl((string) $this->argument('site'));
        $username = (string) $this->argument('username');
        $password = (string) $this->argument('password');
        $timeout = (int) $this->option('timeout');

        if ($timeout < 1) {
            $this->error('ERROR: Timeout must be greater than 0.');

            return self::FAILURE;
        }

        try {
            $response = Http::timeout($timeout)
                ->connectTimeout(5)
                ->withBody($this->buildXmlRequest($username, $password), 'text/xml')
                ->post($site . '/xmlrpc.php');
        } catch (ConnectionException $e) {
            $this->error("ERROR: Site unavailable or request timeout after {$timeout} seconds.");
            $this->line('Details: ' . $this->shorten($e->getMessage()));

            return self::FAILURE;
        }

        if ($response->failed()) {
            $this->error("ERROR HTTP {$response->status()}: WordPress XML-RPC request failed.");
            $this->line('Response: ' . $this->shorten($response->body()));

            return self::FAILURE;
        }

        return $this->handleWordPressResponse($response->body());
    }

    private function buildXmlRequest(string $username, string $password): string
    {
        $title = $this->xml((string) $this->option('title'));
        $content = $this->xml((string) $this->option('content'));
        $username = $this->xml($username);
        $password = $this->xml($password);

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<methodCall>
    <methodName>wp.newPost</methodName>
    <params>
        <param><value><int>0</int></value></param>
        <param><value><string>{$username}</string></value></param>
        <param><value><string>{$password}</string></value></param>
        <param>
            <value>
                <struct>
                    <member>
                        <name>post_type</name>
                        <value><string>post</string></value>
                    </member>
                    <member>
                        <name>post_status</name>
                        <value><string>draft</string></value>
                    </member>
                    <member>
                        <name>post_title</name>
                        <value><string>{$title}</string></value>
                    </member>
                    <member>
                        <name>post_content</name>
                        <value><string>{$content}</string></value>
                    </member>
                </struct>
            </value>
        </param>
    </params>
</methodCall>
XML;
    }

    private function handleWordPressResponse(string $body): int
    {
        try {
            $xml = new SimpleXMLElement($body);
        } catch (Throwable) {
            $this->error('ERROR: Invalid XML response from WordPress.');
            $this->line('Response: ' . $this->shorten($body));

            return self::FAILURE;
        }

        if (isset($xml->fault)) {
            [$code, $message] = $this->readXmlRpcFault($xml);

            $this->error("ERROR XML-RPC {$code}: {$message}");
            $this->line('Hint: Check WordPress username/password and user permissions.');

            return self::FAILURE;
        }

        $postId = $this->readCreatedPostId($xml);

        if (!$postId) {
            $this->error('ERROR: Post may not have been created. WordPress response does not contain post ID.');

            return self::FAILURE;
        }

        $this->info("SUCCESS: Draft post created. ID: {$postId}");

        return self::SUCCESS;
    }

    private function readCreatedPostId(SimpleXMLElement $xml): ?string
    {
        $value = $xml->xpath('/methodResponse/params/param/value')[0] ?? null;

        if (!$value) {
            return null;
        }

        $postId = trim((string) $value);

        return $postId !== '' ? $postId : null;
    }

    private function readXmlRpcFault(SimpleXMLElement $xml): array
    {
        $code = 'unknown';
        $message = 'Unknown WordPress XML-RPC error.';

        foreach ($xml->fault->value->struct->member as $member) {
            $name = (string) $member->name;
            $value = trim((string) $member->value->children()[0]);

            if ($name === 'faultCode') {
                $code = $value;
            }

            if ($name === 'faultString') {
                $message = $value;
            }
        }

        return [$code, $message];
    }

    private function normalizeUrl(string $url): string
    {
        $url = trim($url);

        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            $url = 'https://' . $url;
        }

        return rtrim($url, '/');
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private function shorten(string $value): string
    {
        $value = trim(preg_replace('/\s+/', ' ', $value));

        return mb_strlen($value) > 300
            ? mb_substr($value, 0, 300) . '...'
            : $value;
    }
}
