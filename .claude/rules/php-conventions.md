# PHP Conventions — nanato-seo

PHP-specific detail derived from `.docs/CODE_STANDARDS.md` §2 — that file is the authoritative team standard (also covers JS, SCSS, ACF, and build tooling); this file exists to give Claude the fuller PHP-specific rationale and examples.

## 1. General
- WordPress Coding Standards (WPCS) is the primary base for all PHP code.
- Minimum WordPress version: match the parent theme's supported version (confirm before first release).
- Minimum PHP version: 7.4.
- All user-facing strings must be translatable with text domain `nanato-seo`.
- Always escape output and sanitize input.
- Keep files focused on a single responsibility.
- Keep schema output deterministic and filterable.
- Treat this repository as a schema architecture/code project, not a content-writing project.

## 2. Tooling
| Tool | Config | Command |
|------|--------|---------|
| PHP_CodeSniffer (PHPCS) | `phpcs.xml.dist` | `composer run lint` |
| PHP Code Beautifier (PHPCBF) | `phpcs.xml.dist` | `composer run format` |

Ruleset and formatting:
- Use WPCS v3.1+.
- Use text domain `nanato-seo`.

## 3. Namespace
All classes use the `Nanato_SEO` namespace (PSR-4 mapped to `classes/` in `composer.json`).

```php
namespace Nanato_SEO;
```

Add sub-namespaces by schema layer once needed:

```php
namespace Nanato_SEO\Page_Layer;
```

Place `use` statements after the namespace declaration.

## 4. File Header
Every PHP file starts with a file-level DocBlock and an `ABSPATH` guard:

```php
<?php
/**
 * Short description of the file.
 *
 * @package Nanato_SEO
 */

namespace Nanato_SEO;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
```

## 5. Class Structure
- One class per file.
- File name matches class name exactly (PSR-4), for example `Schema_Builder` in `classes/Schema_Builder.php`.
- Class names use `Studly_Case_With_Underscores`, for example `Schema_Builder`, `Organization_Schema`, `ACF_Field_Map`.
- Register hooks in `__construct()` or in private `register_hooks()` called from `__construct()`.
- Use `array( $this, 'method_name' )` for hook callbacks.
- Set explicit priority and accepted argument count when they differ from defaults.

```php
class Organization_Schema {
    public function __construct() {
        $this->register_hooks();
    }

    private function register_hooks() {
        add_filter( 'nanato_seo_graph', array( $this, 'add_organization_node' ) );
    }
}
```

## 6. Method DocBlocks
Public methods include DocBlocks with description, `@param`, and `@return` tags.

```php
/**
 * Add the Organization node to the JSON-LD graph.
 *
 * @param array $graph Existing @graph nodes.
 * @return array Modified @graph nodes.
 */
public function add_organization_node( $graph ) { ... }
```

Skip DocBlocks on obvious private helpers when the signature is already clear.

## 7. Hooks And Filters
- Use `add_action()` and `add_filter()` for registration.
- Group related hook registrations with a short comment.
- Pass every JSON-LD fragment or graph node through a matching `apply_filters( 'nanato_seo_...', ... )` before output.
- Prefix hooks, options, transients, and meta keys with `nanato_seo_`.

## 8. Indentation And Spacing
- Use tabs for indentation.
- Use one space inside control-structure parentheses: `if ( $condition ) {`.
- Do not leave trailing whitespace.
- Keep opening braces on the same line for classes and functions.

## 9. Arrays
Follow WPCS array formatting rules.

```php
$assoc = array(
    'key'   => 'value',
    'other' => 'value',
);
```

Build JSON-LD graphs as PHP arrays and encode with `wp_json_encode()` at output time.

## 10. Static Utility Classes
Use static methods only for stateless helpers, such as path, URL, and prefix utilities.

```php
Plugin_Paths::plugin_path();
Plugin_Paths::plugin_url();
Plugin_Paths::plugin_prefix();
```

## 11. Instantiating Classes
Instantiate classes in the main plugin file from a flat class list:

```php
$classes = [
    \Nanato_SEO\Organization_Schema::class,
    \Nanato_SEO\Breadcrumb_Schema::class,
];

foreach ( $classes as $class ) {
    new $class();
}
```

## 12. Security
- Always escape output.
- Always sanitize input.
- Protect admin-only operations with capability checks.
- Use nonces for form submissions and AJAX requests.
- Use prepared queries (`$wpdb->prepare()`) for custom SQL; never build SQL strings from raw input.

### Escaping Output
| Context | Function |
|---------|---------|
| HTML content | `wp_kses_post()` |
| HTML attribute | `esc_attr()` |
| URL | `esc_url()` |
| Plain text | `esc_html()` |
| JSON or inline data | `wp_json_encode()` |

Never echo raw variables into HTML.

### Sanitizing Input (ACF field values and options)
| Input type | Function |
|------------|---------|
| Text field | `sanitize_text_field()` |
| Textarea | `sanitize_textarea_field()` |
| Email | `sanitize_email()` |
| URL | `esc_url_raw()` |
| Integer | `absint()` or `intval()` |
| HTML content | `wp_kses_post()` |

### Capability Checks
Protect admin-only functionality such as options-page updates:

```php
if ( ! current_user_can( 'manage_options' ) ) {
    return;
}
```

### Nonces
Use nonces for any form submission or AJAX request this plugin adds (e.g. an options-page save handler):

```php
// Create
wp_create_nonce( 'nanato_seo_action' );

// Verify
if ( ! wp_verify_nonce( $_POST['nonce'], 'nanato_seo_action' ) ) {
    wp_die( 'Security check failed.' );
}
```

### Defensive Validation
Validate required ACF field data before rendering schema output. If required data is missing, return early instead of outputting incomplete JSON-LD.

## 13. Internationalization
- Use text domain `nanato-seo`.
- Wrap user-facing strings with translation functions such as `__()` and `esc_html__()`.
- Use `sprintf()` with placeholders for translatable strings instead of string concatenation.

## 14. File And Directory Structure
```
nanato-seo/
├── classes/         # PHP classes (PSR-4 namespace Nanato_SEO)
├── inc/             # Procedural includes/helpers
├── src/             # JS/SCSS source (admin, editor, frontend)
├── acf-json/        # ACF Local JSON (auto-generated, do not hand-edit)
├── build/           # Webpack output (gitignored)
└── .docs/           # Developer and architecture documentation
```

- Keep business logic out of templates.
- Put plain helper functions in `inc/helpers.php` and prefix with `nanato_seo_`.
