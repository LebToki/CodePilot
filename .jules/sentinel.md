## 2024-05-20 - [Critical] Command Injection via Carriage Return

**Vulnerability:** The `isAllowedCommand` function in `public/api/terminal.php` used a regular expression `/[\;&|\`$<>\n]/` to block shell metacharacters. However, it failed to block the carriage return character (`\r`). On Windows systems, `cmd.exe` interprets `\r` as a command separator, allowing an attacker to bypass the filter and execute arbitrary commands by appending `\r` followed by the malicious payload.

**Learning:** Shell metacharacter blocklists must be exhaustive across all target operating systems. Windows command parsing (`cmd.exe`) has unique quirks, such as treating `\r` as a newline/separator, which are often overlooked when writing regex filters primarily designed for POSIX shells (`bash`, `sh`).

**Prevention:** Always include `\r` alongside `\n` in command injection blocklists: `/[;&|\`$<>\n\r]/`. Where possible, avoid `cmd /c` wrapping with unsanitized input entirely, and prefer passing arguments as an array to functions like `proc_open` to bypass the shell.

## 2024-05-20 - [CRITICAL] CRLF Injection in .env Modification

**Vulnerability:** The application exposed an API endpoint (`public/api/settings.php`) to update `.env` variables from user input. It only `trim()`'ed the input but failed to strip internal carriage return (`\r`) or line feed (`\n`) characters. This allowed attackers to inject new environment variables into the configuration file (CRLF injection), such as inserting `Value\nINJECTED_KEY=malicious_value` to overwrite or add new configurations.

**Learning:** When dealing with structured text files where line breaks determine the structure (like `.env`, `ini`, or CSV files), user input MUST be explicitly stripped of all newline characters (`\r` and `\n`) before interpolation. Relying only on `trim()` is insufficient as it only removes whitespace at the boundaries, not within the payload.

**Prevention:** Sanitize all user input destined for line-based configuration files by explicitly stripping `\r` and `\n` characters (e.g., using `str_replace(["\r", "\n"], '', $value)` in PHP) to ensure the input remains strictly on a single line.
