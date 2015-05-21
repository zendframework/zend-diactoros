Contributing
============

Issues may be reported to https://github.com/phly/http/issues.
Patches may be submitted via pull request to https://github.com/phly/http/pulls.


If you are submitting a pull request, please follow these guidelines:

- Please write unit tests for any features or bugfixes you have. I will not and
  can not read your mind; demonstrate with code what you are attempting.
- Please run unit tests before opening a pull request. You can do so by
  executing `./vendor/bin/phpunit` from the project root. This will help you
  identify whether or not your change affects other areas of the code.
- Please run CodeSniffer before opening a pull request, and correct any issues:
  `./vendor/bin/phpcs --standard=PSR2 --ignore=test/Bootstrap.php src test`.
  `phpcs` provides a tool for fixing most errors as well:
  `./vendor/bin/phpcbf --standard=PSR2 --ignore=test/Bootstrap.php src test`.
  If you run the tool and it fixes issues, make sure you commit them!
- Use a branch from your fork, not your master branch. This will help ensure you
  submit only commits specific to the bugfix or feature you are submitting.
  Feel free to continue pushing changes to that branch as you improve your
  patch.
- Keep your branch up-to-date with master (where "upstream" is a remote
  representing this repository):
  ```console
  $ git fetch upstream
  $ git rebase upstream/master
  ```
  If you rebase, make sure you force push your changes to your own branch (where
  "origin" is your own remote):
  ```console
  $ git push -f origin <your branch>:<your branch>
  ```
