## 2025-02-12 - [Optimize project file counting]
**Learning:** `RecursiveDirectoryIterator` can be extremely slow on large projects with `node_modules` or `vendor` directories.
**Action:** Use `RecursiveCallbackFilterIterator` to ignore these directories during traversal to drastically improve performance. Note that this changes the total file count reported by the API.
