## 2025-02-12 - [Optimize project file counting]
**Learning:** `RecursiveDirectoryIterator` can be extremely slow on large projects with `node_modules` or `vendor` directories.
**Action:** Use `RecursiveCallbackFilterIterator` to ignore these directories during traversal to drastically improve performance. Note that this changes the total file count reported by the API.

## 2025-03-01 - [Optimize array sorting in API endpoints]
**Learning:** `usort()` with closure callbacks (like `strcasecmp` or `strtotime`) is significantly slower in PHP when sorting large arrays of associative arrays due to overhead of executing user-defined functions repeatedly.
**Action:** Use `array_multisort()` combined with `array_column()` for a native C-level sorting performance, leading to substantial speedups when dealing with list APIs.
