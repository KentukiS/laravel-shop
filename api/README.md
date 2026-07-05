# WordPress Draft Post Creator

This is a small Laravel command that creates a draft post on a WordPress site using a WordPress username and password.

The task requires three input parameters:

* WordPress site URL
* WordPress username
* WordPress password

Because the task explicitly requires a username/password pair, the implementation uses the built-in WordPress XML-RPC API method `wp.newPost`.

## Main files

The main implementation is here:

```text
app/Console/Commands/CreateWordPressDraftPost.php
```

Tests are here:

```text
tests/Feature/CreateWordPressDraftPostTest.php
```

## How it works

The command sends a POST request to the WordPress XML-RPC endpoint:

```text
/xmlrpc.php
```

It calls the XML-RPC method:

```text
wp.newPost
```

The created post uses:

```text
post_type: post
post_status: draft
```

If the request is successful, the command prints the created post ID.

Example successful output:

```text
SUCCESS: Draft post created. ID: 123
```

## Usage

Run the command with Artisan:

```bash
php artisan wp:create-draft "https://example.com" "wp_username" "wp_password"
```

With Laravel Sail:

```bash
./vendor/bin/sail artisan wp:create-draft "https://example.com" "wp_username" "wp_password"
```

Optional title and content:

```bash
php artisan wp:create-draft "https://example.com" "wp_username" "wp_password" \
  --title="Test Draft Post" \
  --content="This post was created from Laravel."
```

## Error handling

The command handles:

* unavailable site
* request timeout
* invalid username/password
* HTTP errors
* invalid XML response
* missing post ID in the response

Example errors:

```text
ERROR: Site unavailable or request timeout after 10 seconds.
```

```text
ERROR XML-RPC 403: Incorrect username or password.
```

```text
ERROR HTTP 500: WordPress XML-RPC request failed.
```

## Testing

A real WordPress site is not required for testing.

The command is covered by Laravel feature tests using `Http::fake()`.
The tests mock WordPress XML-RPC responses and check successful and failed scenarios.

Run tests:

```bash
php artisan test --filter=CreateWordPressDraftPostTest
```

With Laravel Sail:

```bash
./vendor/bin/sail artisan test --filter=CreateWordPressDraftPostTest
```

Tested scenarios:

* successful draft creation
* invalid username/password
* unavailable site or timeout
* HTTP error response

Example test result:

```text
PASS  Tests\Feature\CreateWordPressDraftPostTest
✓ it creates wordpress draft successfully
✓ it handles invalid username or password
✓ it handles unavailable site or timeout
✓ it handles http error
```

## Notes

A local or real WordPress site is not required for this task.
The tests mock WordPress XML-RPC responses, so the request structure, response parsing, and error handling can be reviewed without deploying WordPress.

On a real WordPress website, `/xmlrpc.php` must be enabled and the provided user must have permission to create posts.
