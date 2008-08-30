This is a little AB testing setup for Windows. Before committing any non-trivial
change, these tests should be run so "results_summary.txt" will be an up-to-date
document of each revision's performance.

Before testing make sure all cache locations are correct.

Double-click "test_all.bat" to run the tests. At the end, "results_summary.txt"
will be opened in notepad. For more details see "results.txt".

Delete "results.txt" before committing. We don't need this much info in svn.