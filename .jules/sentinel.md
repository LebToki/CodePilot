## 2024-05-20 - [Critical] Command Injection via Carriage Return

**Vulnerability:** The `isAllowedCommand` function in `public/api/terminal.php` used a regular expression `/[\;&|\`$<>\n]/` to block shell metacharacters. However, it failed to block the carriage return character (`\r`). On Windows systems, `cmd.exe` interprets `\r` as a command separator, allowing an attacker to bypass the filter and execute arbitrary commands by appending `\r` followed by the malicious payload.

**Learning:** Shell metacharacter blocklists must be exhaustive across all target operating systems. Windows command parsing (`cmd.exe`) has unique quirks, such as treating `\r` as a newline/separator, which are often overlooked when writing regex filters primarily designed for POSIX shells (`bash`, `sh`).

**Prevention:** Always include `\r` alongside `\n` in command injection blocklists: `/[;&|\`$<>\n\r]/`. Where possible, avoid `cmd /c` wrapping with unsanitized input entirely, and prefer passing arguments as an array to functions like `proc_open` to bypass the shell.
