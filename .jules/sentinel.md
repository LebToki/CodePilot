## 2024-05-20 - Fix Command Injection in Terminal API
**Vulnerability:** Command injection vulnerability in `killProcess` function (`public/api/terminal.php`).
**Learning:** The `$pid` was received from user input and directly interpolated into system commands (`shell_exec("taskkill /PID $pid /F")` and `shell_exec("kill -9 $pid 2>&1")`). Even though the command was hardcoded, concatenating unvalidated user input directly into `shell_exec` is a severe security risk.
**Prevention:** Always validate and sanitize user input before passing it to system commands. For numeric values like PIDs, strictly casting to integer (`(int)$pid`) and checking bounds (`> 0`) is essential. Using `escapeshellarg()` adds a defense-in-depth layer.
