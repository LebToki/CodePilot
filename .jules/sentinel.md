## 2026-03-17 - Fix Command Injection in Terminal Execution API
**Vulnerability:** Critical command injection in `isAllowedCommand` function (`public/api/terminal.php`).
**Learning:** Checking only the prefix of a command string (using `stripos`) is insufficient security if the entire unescaped string is later passed to a shell execution function (e.g., `proc_open`). An attacker could supply an allowed prefix followed by shell chaining metacharacters (e.g., `npm install; rm -rf /`) to execute arbitrary commands.
**Prevention:** In addition to validating the allowed command prefix, strictly reject any user-provided shell command string that contains dangerous metacharacters (e.g., `;`, `&`, `|`, `` ` ``, `$`, `<`, `>`). Use `preg_match` to enforce this rule before execution.

## 2024-05-20 - Fix Command Injection in Terminal API
**Vulnerability:** Command injection vulnerability in `killProcess` function (`public/api/terminal.php`).
**Learning:** The `$pid` was received from user input and directly interpolated into system commands (`shell_exec("taskkill /PID $pid /F")` and `shell_exec("kill -9 $pid 2>&1")`). Even though the command was hardcoded, concatenating unvalidated user input directly into `shell_exec` is a severe security risk.
**Prevention:** Always validate and sanitize user input before passing it to system commands. For numeric values like PIDs, strictly casting to integer (`(int)$pid`) and checking bounds (`> 0`) is essential. Using `escapeshellarg()` adds a defense-in-depth layer.
