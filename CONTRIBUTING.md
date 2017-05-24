# <a id="contributing"></a> Contributing

Icinga is an open source project and lives from your ideas and contributions.

There are many ways to contribute, from improving the documentation, submitting
bug reports and features requests or writing code to add enhancements or fix bugs.

#### Table of Contents

1. [Introduction](#contributing-intro)
2. [Fork the Project](#contributing-fork)
3. [Branches](#contributing-branches)
4. [Commits](#contributing-commits)
5. [Pull Requests](#contributing-pull-requests)
6. [Testing](#contributing-testing)
7. [Source Code Patches](#contributing-patches-source-code)
8. [Documentation Patches](#contributing-patches-documentation)
9. [Review](#contributing-review)

## <a id="contributing-intro"></a> Introduction

Please consider our [roadmap](https://github.com/Icinga/icingaweb2/milestones) and
[open issues](https://github.com/icinga/icingaweb2/issues) when you start contributing
to the project.

Before starting your work on Icinga Web 2, you should [fork the project](https://help.github.com/articles/fork-a-repo/)
to your GitHub account. This allows you to freely experiment with your changes.
When your changes are complete, submit a [pull request](https://help.github.com/articles/using-pull-requests/).
All pull requests will be reviewed and merged if they suit some general guidelines:

* Changes are located in a topic branch
* For new functionality, proper tests are written
* Changes should follow the existing coding style and standards

Please continue reading in the following sections for a step by step guide.

## <a id="contributing-fork"></a> Fork the Project

[Fork the project](https://help.github.com/articles/fork-a-repo/) to your GitHub account
and clone the repository:

```
git clone git@github.com:dnsmichi/icingaweb2.git
cd icingaweb2
```

Add a new remote `upstream` with this repository as value.

```
git remote add upstream https://github.com/icinga/icingaweb2.git
```

You can pull updates to your fork's master branch:

```
git fetch --all
git pull upstream HEAD
```

Please continue to learn about [branches](CONTRIBUTING.md#contributing-branches).

## <a id="contributing-branches"></a> Branches

Choosing a proper name for a branch helps us identify its purpose and possibly
find an associated bug or feature.
Generally a branch name should include a topic such as `fix` or `feature` followed
by a description and an issue number if applicable. Branches should have only changes
relevant to a specific issue.

```
git checkout -b fix/service-template-typo-1234
git checkout -b feature/config-handling-1235
```

Continue to apply your changes and test them. More details on specific changes:

* [Source Code Patches](#contributing-patches-source-code)
* [Documentation Patches](#contributing-patches-documentation)

## <a id="contributing-commits"></a> Commits

Once you've finished your work in a branch, please ensure to commit
your changes. A good commit message includes a short topic, additional body
and a reference to the issue you wish to solve (if existing).

Fixes:

```
Fix missing style in detail view

refs #4567
```

Features:

```
Add DateTime picker

refs #1234
```

You can add multiple commits during your journey to finish your patch.
Don't worry, you can squash those changes into a single commit later on.

## <a id="contributing-pull-requests"></a> Pull Requests

Once you've committed your changes, please update your local master
branch and rebase your fix/feature branch against it before submitting a PR.

```
git checkout master
git pull upstream HEAD

git checkout fix/style-detail-view
git rebase master
```

Once you've resolved any conflicts, push the branch to your remote repository.
It might be necessary to force push after rebasing - use with care!

New branch:
```
git push --set-upstream origin fix/style-detail-view
```

Existing branch:
```
git push -f origin fix/style-detail-view
```

You can now either use the [hub](https://hub.github.com) CLI tool to create a PR, or nagivate
to your GitHub repository and create a PR there.

The pull request should again contain a telling subject and a reference
with `fixes` to an existing issue id if any. That allows developers
to automatically resolve the issues once your PR gets merged.

```
hub pull-request

<a telling subject>

fixes #1234
```

Thanks a lot for your contribution!


### <a id="contributing-rebase"></a> Rebase a Branch

If you accidentally sent in a PR which was not rebased against the upstream master,
developers might ask you to rebase your PR.

First off, fetch and pull `upstream` master.

```
git checkout master
git fetch --all
git pull upstream HEAD
```

Then change to your working branch and start rebasing it against master:

```
git checkout fix/style-detail-view
git rebase master
```

If you are running into a conflict, rebase will stop and ask you to fix the problems.

```
git status

  both modified: path/to/conflict.php
```

Edit the file and search for `>>>`. Fix, build, test and save as needed.

Add the modified file(s) and continue rebasing.

```
git add path/to/conflict.php
git rebase --continue
```

Once succeeded ensure to push your changed history remotely.

```
git push -f origin fix/style-detail-view
```


If you fear to break things, do the rebase in a backup branch first and later replace your current branch.

```
git checkout fix/style-detail-view
git checkout -b fix/style-detail-view-rebase

git rebase master

git branch -D fix/style-detail-view
git checkout -b fix/style-detail-view

git push -f origin fix/style-detail-view
```

### <a id="contributing-squash"></a> Squash Commits

> **Note:**
>
> Be careful with squashing. This might lead to non-recoverable mistakes.
>
> This is for advanced Git users.

Say you want to squash the last 3 commits in your branch into a single one.

Start an interactive (`-i`)  rebase from current HEAD minus three commits (`HEAD~3`).

```
git rebase -i HEAD~3
```

Git opens your preferred editor. `pick` the commit in the first line, change `pick` to `squash` on the other lines.

```
pick e4bf04e47 Fix style detail view
squash d7b939d99 Tests
squash b37fd5377 Doc updates
```

Save and let rebase to its job. Then force push the changes to the remote origin.

```
git push -f origin fix/style-detail-view
```


## <a id="contributing-testing"></a> Testing

Basic unit test coverage is provided by running `icingacli test php unit`.
The [development Vagrant box](https://github.com/Icinga/icingaweb2/blob/master/doc/99-Vagrant.md)
provides a pre-built environment for development and tests.

Snapshot packages from the laster development branch are available inside the
[package repository](https://packages.icinga.com).

You can help test-drive the latest Icinga 2 snapshot packages inside the
[Icinga 2 Vagrant boxes](https://github.com/icinga/icinga-vagrant).


## <a id="contributing-patches-source-code"></a> Source Code Patches

Icinga Web 2 is written in PHP and JavaScript.

In order to develop Icinga Web 2 please use the [development Vagrant box](https://github.com/Icinga/icingaweb2/blob/master/doc/99-Vagrant.md).
You can edit the source code in your local git repository and review changes
live from the Vagrant environment.

## <a id="contributing-patches-documentation"></a> Documentation Patches

The documentation is written in GitHub flavored [Markdown](https://guides.github.com/features/mastering-markdown/).
It is located in the `doc/` directory and can be edited with your preferred editor. You can also
edit it online on GitHub.

```
vim doc/02-Installation.md
```

In order to review and test changes, you can use the `doc` module in Icinga Web 2.
Navigate to `Configuration - Modules` and enable the `doc` module. Open
`Documentation - Icinga Web 2` from the menu.


## <a id="contributing-review"></a> Review

### <a id="contributing-pr-review"></a> Pull Request Review

This is only important for developers who will review pull requests. If you want to join
the development team, kindly contact us.

- Ensure that the style guide applies.
- Verify that the patch fixes a problem or linked issue, if any.
- Discuss new features with team members.
- Test the patch in your local dev environment.

If there are changes required, kindly ask for an updated patch.

Once the review is completed, merge the PR via GitHub.

#### <a id="contributing-pr-review-fixes"></a> Pull Request Review Fixes

In order to amend the commit message, fix conflicts or add missing changes, you can
add your changes to the PR.

A PR is just a pointer to a different Git repository and branch.
By default, pull requests allow to push into the repository of the PR creator.

Example for [#4956](https://github.com/Icinga/icinga2/pull/4956):

At the bottom it says "Add more commits by pushing to the fix/persistent-comments-are-not-persistent branch on TheFlyingCorpse/icinga2."

First off, add the remote repository as additional origin and fetch its content:

```
git remote add theflyingcorpse https://github.com/TheFlyingCorpse/icinga2
git fetch --all
```

Checkout the mentioned remote branch into a local branch (Note: `theflyingcorpse` is the name of the remote):

```
git checkout theflyingcorpse/fix/persistent-comments-are-not-persistent -b fix/persistent-comments-are-not-persistent
```

Rebase, amend, squash or add your own commits on top.

Once you are satisfied, push the changes to the remote `theflyingcorpse` and its branch `fix/persistent-comments-are-not-persistent`.
The syntax here is `git push <remote> <localbranch>:<remotebranch>`.

```
git push theflyingcorpse fix/persistent-comments-are-not-persistent:fix/persistent-comments-are-not-persistent
```

In case you've changed the commit history (rebase, amend, squash), you'll need to force push. Be careful, this can't be reverted!

```
git push -f theflyingcorpse fix/persistent-comments-are-not-persistent:fix/persistent-comments-are-not-persistent
```
