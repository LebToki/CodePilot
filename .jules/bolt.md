## 2025-02-12 - [Optimize project file counting]
**Learning:** `RecursiveDirectoryIterator` can be extremely slow on large projects with `node_modules` or `vendor` directories.
**Action:** Use `RecursiveCallbackFilterIterator` to ignore these directories during traversal to drastically improve performance. Note that this changes the total file count reported by the API.

## 2025-03-01 - [Optimize array sorting in API endpoints]
**Learning:** `usort()` with closure callbacks (like `strcasecmp` or `strtotime`) is significantly slower in PHP when sorting large arrays of associative arrays due to overhead of executing user-defined functions repeatedly.
**Action:** Use `array_multisort()` combined with `array_column()` for a native C-level sorting performance, leading to substantial speedups when dealing with list APIs.

## 2026-03-20 - [Optimize directory listing with FilesystemIterator]
**Learning:** `scandir()` requires loading all file names into a large array at once, which consumes more memory. While PHP natively caches `stat()` calls, iterating with `scandir()` over large directories is still less efficient than lazy iteration.
**Action:** Use `FilesystemIterator` when iterating over directories. This reduces the memory footprint via lazy iteration. Note: Always wrap `SplFileInfo` methods like `getSize()` and `getMTime()` in a `try-catch` block, as they will throw a `RuntimeException` for unreadable files or broken symlinks.

## 2024-05-24 - [Optimize file extension checking in searchDirectory]
**Learning:** Inside a large loop (e.g., iterating through `RecursiveDirectoryIterator`), extracting file extensions with `pathinfo()` and checking them against a static array with `in_array()` adds significant overhead.
**Action:** Use `$file->getExtension()` directly from the `SplFileInfo` object, and use `isset()` with an `array_flip()` array for faster O(1) lookups instead of O(n) `in_array()` lookups.
