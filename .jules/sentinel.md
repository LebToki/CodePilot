## 2024-05-20 - [Critical] Command Injection via Carriage Return

**Vulnerability:** The `isAllowedCommand` function in `public/api/terminal.php` used a regular expression `/[\;&|\`$<>\n]/` to block shell metacharacters. However, it failed to block the carriage return character (`\r`). On Windows systems, `cmd.exe` interprets `\r` as a command separator, allowing an attacker to bypass the filter and execute arbitrary commands by appending `\r` followed by the malicious payload.

**Learning:** Shell metacharacter blocklists must be exhaustive across all target operating systems. Windows command parsing (`cmd.exe`) has unique quirks, such as treating `\r` as a newline/separator, which are often overlooked when writing regex filters primarily designed for POSIX shells (`bash`, `sh`).

**Prevention:** Always include `\r` alongside `\n` in command injection blocklists: `/[;&|\`$<>\n\r]/`. Where possible, avoid `cmd /c` wrapping with unsanitized input entirely, and prefer passing arguments as an array to functions like `proc_open` to bypass the shell.

## 2024-05-20 - [CRITICAL] CRLF Injection in .env Modification

**Vulnerability:** The application exposed an API endpoint (`public/api/settings.php`) to update `.env` variables from user input. It only `trim()`'ed the input but failed to strip internal carriage return (`\r`) or line feed (`\n`) characters. This allowed attackers to inject new environment variables into the configuration file (CRLF injection), such as inserting `Value\nINJECTED_KEY=malicious_value` to overwrite or add new configurations.

**Learning:** When dealing with structured text files where line breaks determine the structure (like `.env`, `ini`, or CSV files), user input MUST be explicitly stripped of all newline characters (`\r` and `\n`) before interpolation. Relying only on `trim()` is insufficient as it only removes whitespace at the boundaries, not within the payload.

**Prevention:** Sanitize all user input destined for line-based configuration files by explicitly stripping `\r` and `\n` characters (e.g., using `str_replace(["\r", "\n"], '', $value)` in PHP) to ensure the input remains strictly on a single line.

## 2024-05-25 - Path Validation Bypass (Directory Traversal)
**Vulnerability:** In `validatePath` and `isValidProjectPath` across multiple API files (`files.php`, `terminal.php`, `projects.php`, `src/Utils/Security.php`), the code used `strpos($realPath, $allowedReal) === 0` to check if a user-supplied path was within an allowed directory.
**Learning:** This is a classic path traversal bypass in PHP. Because it only checks for a string prefix, an allowed path like `/var/www` will successfully match a malicious sibling directory like `/var/www_backup` or `/var/www-secret`.
**Prevention:** Always append a trailing directory separator (`/`) to the allowed path prefix before checking with `strpos`, or perform an exact match if the paths are identical. For example: `strpos($realPath, rtrim($allowedReal, '/') . '/') === 0`.

## 2024-05-26 - [CRITICAL] Reflected XSS in index.php via `addslashes`

**Vulnerability:** In `public/index.php`, the `$projectPath` variable (sourced from user input) was passed to the frontend JavaScript using `addslashes()`. This function only escapes single/double quotes, backslashes, and NUL characters. It failed to escape or encode HTML tags like `<` and `>`, allowing an attacker to break out of the script tag context using `</script>` and execute arbitrary JavaScript.

**Learning:** `addslashes()` is insufficient for securely embedding PHP variables inside JavaScript strings within an HTML document. Even if quotes are escaped, the browser's HTML parser runs before the JavaScript engine, so a literal `</script>` string inside a JS string literal will terminate the script block prematurely.

**Prevention:** Always use `json_encode($variable)` when embedding PHP data into JavaScript. This safely handles quotes, special characters, and HTML tags (with proper flags or encoding), ensuring the data remains safely within the JS context.
