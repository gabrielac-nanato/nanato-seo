# Code Standards — nanato-seo

This document defines coding and documentation standards for this plugin.
WordPress Coding Standards (WPCS) is the primary base for PHP.

## 1. General Principles
- Follow WordPress coding standards and APIs.
- Minimum PHP version: 7.4.
- Keep files focused on a single responsibility.
- Keep schema output deterministic and filterable.
- Treat this repository as a schema architecture/code project, not a content-writing project.

## 2. PHP Standards

### 2.1 Tooling
| Tool | Config | Command |
|------|--------|---------|
| PHP_CodeSniffer (PHPCS) | `phpcs.xml.dist` | `composer run lint` |
| PHP Code Beautifier (PHPCBF) | `phpcs.xml.dist` | `composer run format` |

### 2.2 Rules
- WPCS v3.1+ is the authoritative baseline.
- Use tabs for indentation.
- Use one space inside control-structure parentheses: `if ( $condition ) {`.
- Keep opening braces on the same line for classes and functions.
- Do not leave trailing whitespace.
- Follow WPCS array formatting rules.
- Build JSON-LD as PHP arrays and output through `wp_json_encode()`.

### 2.3 Namespace and Class Layout
- Namespace: `Nanato_SEO`.
- Autoload mapping: `Nanato_SEO\\` to `classes/`.
- One class per file.
- File name matches class name (for example, `Schema_Builder` in `classes/Schema_Builder.php`).
- Register hooks in `__construct()` or in a private `register_hooks()` called from `__construct()`.
- Use `array( $this, 'method_name' )` for hook callbacks.

### 2.4 File Header
Every PHP file must start with:
- File-level DocBlock with `@package Nanato_SEO`
- `ABSPATH` guard

### 2.5 DocBlocks
- Public methods include DocBlocks with description, `@param`, and `@return`.
- Private helpers can omit DocBlocks when the method is self-explanatory.

### 2.6 Hooks and Prefixes
- Register hooks with `add_action()` and `add_filter()`.
- Prefix plugin hooks, options, transients, and meta keys with `nanato_seo_`.
- Pass JSON-LD fragments and graph nodes through `apply_filters( 'nanato_seo_...', ... )` before output.

## 3. JavaScript Standards

### 3.1 Tooling
| Tool | Command |
|------|---------|
| ESLint (`@wordpress/scripts`) | `npm run wp:lint:js` |
| Formatter (`@wordpress/scripts`) | `npm run wp:format` |

### 3.2 Rules
- Use ES modules (`import` / `export`).
- Prefer `const`; use `let` only when reassignment is needed.
- Do not use `var`.
- Keep logic modular and readable.

## 4. SCSS and CSS Standards

### 4.1 Tooling
| Tool | Config | Command |
|------|--------|---------|
| Stylelint | `stylelint.config.js` | `npm run wp:lint:css` |

### 4.2 Rules
- Follow `@wordpress/stylelint-config/scss`.
- Use tabs for indentation.
- Keep selectors and declarations clear and maintainable.

## 5. Security
- Always escape output.
- Always sanitize input.
- Protect admin-only operations with capability checks.
- Use nonces for form submissions and AJAX requests.
- Use prepared queries for custom SQL.

### 5.1 Escaping Output
| Context | Function |
|---------|---------|
| HTML content | `wp_kses_post()` |
| HTML attribute | `esc_attr()` |
| URL | `esc_url()` |
| Plain text | `esc_html()` |
| JSON or inline data | `wp_json_encode()` |

### 5.2 Sanitizing Input
| Input type | Function |
|------------|---------|
| Text field | `sanitize_text_field()` |
| Textarea | `sanitize_textarea_field()` |
| Email | `sanitize_email()` |
| URL | `esc_url_raw()` |
| Integer | `absint()` or `intval()` |
| HTML content | `wp_kses_post()` |

## 6. Internationalization
- Text domain: `nanato-seo`.
- Wrap user-facing strings in translation functions such as `__()` and `esc_html__()`.
- Use `sprintf()` placeholders for translatable strings instead of string concatenation.

## 7. ACF Standards
- Keep all ACF references uppercase as `ACF` in documentation.
- Store ACF field groups in `acf-json/`.
- Manage ACF field groups through the ACF UI.
- Do not manually edit files in `acf-json/`.
- Use `get_field()` and `the_field()` with correct field names/keys.

## 8. File and Directory Structure
```
nanato-seo/
├── classes/     # PHP classes (PSR-4 namespace Nanato_SEO)
├── inc/         # Procedural includes and helpers
├── src/         # JS and SCSS source
├── acf-json/    # ACF local JSON (generated)
├── build/       # Build output
└── .docs/       # Documentation
```

- Keep business logic out of templates.
- Put plain helper functions in `inc/helpers.php` and prefix with `nanato_seo_`.

## 9. Build and Lint Commands
- `npm run wp:start`
- `npm run wp:build`
- `npm run wp:lint:js`
- `npm run wp:lint:css`
- `npm run wp:format`
- `composer run lint`
- `composer run format`
