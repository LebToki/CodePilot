## 2025-02-12 - [Optimize project file counting]
**Learning:** `RecursiveDirectoryIterator` can be extremely slow on large projects with `node_modules` or `vendor` directories.
**Action:** Use `RecursiveCallbackFilterIterator` to ignore these directories during traversal to drastically improve performance. Note that this changes the total file count reported by the API.

## 2025-03-18 - [Optimize array sorting in PHP APIs]
**Learning:** In PHP, using `usort()` with user-defined closures (like `strtotime` or `strcasecmp`) to sort large associative arrays is very slow due to the overhead of calling the closure for every comparison. Native sorting functions are significantly faster.
**Action:** Replace `usort()` closures with a combination of `array_column()` and `array_multisort()`. This approach extracts the column to sort by and uses native C-level sorting logic, providing a dramatic performance boost (e.g., ~20x faster) for large lists.
