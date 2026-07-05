<?php

namespace Tests\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CreateWordPressDraftPostTest extends TestCase
{
    public function test_it_creates_wordpress_draft_successfully(): void
    {
        Http::fake([
            'example.com/xmlrpc.php' => Http::response(
                '<?xml version="1.0"?>
                <methodResponse>
                    <params>
                        <param>
                            <value>
                                <string>123</string>
                            </value>
                        </param>
                    </params>
                </methodResponse>',
                200,
                ['Content-Type' => 'text/xml']
            ),
        ]);

        $this->artisan('wp:create-draft', [
            'site' => 'https://example.com',
            'username' => 'admin',
            'password' => 'secret',
        ])
            ->expectsOutput('SUCCESS: Draft post created. ID: 123')
            ->assertSuccessful();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://example.com/xmlrpc.php'
                && $request->method() === 'POST'
                && str_contains($request->body(), '<methodName>wp.newPost</methodName>')
                && str_contains($request->body(), '<string>admin</string>')
                && str_contains($request->body(), '<string>secret</string>')
                && str_contains($request->body(), '<name>post_status</name>')
                && str_contains($request->body(), '<string>draft</string>');
        });
    }

    public function test_it_handles_invalid_username_or_password(): void
    {
        Http::fake([
            'example.com/xmlrpc.php' => Http::response(
                '<?xml version="1.0"?>
                <methodResponse>
                    <fault>
                        <value>
                            <struct>
                                <member>
                                    <name>faultCode</name>
                                    <value>
                                        <int>403</int>
                                    </value>
                                </member>
                                <member>
                                    <name>faultString</name>
                                    <value>
                                        <string>Incorrect username or password.</string>
                                    </value>
                                </member>
                            </struct>
                        </value>
                    </fault>
                </methodResponse>',
                200,
                ['Content-Type' => 'text/xml']
            ),
        ]);

        $this->artisan('wp:create-draft', [
            'site' => 'https://example.com',
            'username' => 'admin',
            'password' => 'wrong-password',
        ])
            ->expectsOutput('ERROR XML-RPC 403: Incorrect username or password.')
            ->expectsOutput('Hint: Check WordPress username/password and user permissions.')
            ->assertFailed();
    }

    public function test_it_handles_unavailable_site_or_timeout(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection timed out.');
        });

        $this->artisan('wp:create-draft', [
            'site' => 'https://example.com',
            'username' => 'admin',
            'password' => 'secret',
            '--timeout' => 3,
        ])
            ->expectsOutput('ERROR: Site unavailable or request timeout after 3 seconds.')
            ->expectsOutput('Details: Connection timed out.')
            ->assertFailed();
    }

    public function test_it_handles_http_error(): void
    {
        Http::fake([
            'example.com/xmlrpc.php' => Http::response('Server error', 500),
        ]);

        $this->artisan('wp:create-draft', [
            'site' => 'https://example.com',
            'username' => 'admin',
            'password' => 'secret',
        ])
            ->expectsOutput('ERROR HTTP 500: WordPress XML-RPC request failed.')
            ->expectsOutput('Response: Server error')
            ->assertFailed();
    }
}
