# Quality Assurance

Review and test the changes and issues for this version.
https://github.com/icinga/icingaweb2

# Release Workflow

## Authors

Update the [.mailmap](.mailmap) and [AUTHORS](AUTHORS) files:

    $ git log --use-mailmap | grep ^Author: | cut -f2- -d' ' | sort | uniq > AUTHORS

## Version

Update the version number in the following files:

* [icingaweb2.spec] (ensure that the revision is properly set)
* [VERSION]
* Application Version: [library/Icinga/Application/Version.php]
* Module Versions in modules/*/module.info

Commands:

    VERSION=2.0.0

    vim icingaweb2.spec

    echo "v$VERSION" > VERSION

    sed -i '' "s/const VERSION = '.*'/const VERSION = '$VERSION'/g" library/Icinga/Application/Version.php

    find . -type f -name '*.info' -exec sed -i '' "s/Version: .*/Version: $VERSION/g" {} \;

## Changelog

Update the [ChangeLog](ChangeLog) file using
the changelog.py script.

Changelog:

    $ ./changelog.py --version 2.0.0

Wordpress:

    $ ./changelog.py --version 2.0.0 --html --links

## Git Tag

Commit these changes to the "master" branch:

    $ git commit -v -a -m "Release version <VERSION>"

For minor releases: Cherry-pick this commit into the "support" branch.

Create a signed tag (tags/v<VERSION>) on the "master" branch (for major
releases) or the "support" branch (for minor releases).

    $ git tag -m "Version <VERSION>" v<VERSION>

Push the tag.

    $ git push --tags

For major releases: Create a new "support" branch:

    $ git checkout master
    $ git checkout -b support/2.x
    $ git push -u origin support/2.x

# External Dependencies

## Build Server

### Linux

* Build the newly created git tag for Debian/RHEL/SuSE.
* Provision the vagrant boxes and test the release packages.

## Github Release

Create a new release from the newly created git tag.
https://github.com/Icinga/icingaweb2/releases

## Announcement

* Create a new blog post on www.icinga.com/blog
* Send announcement mail to icinga-announce@lists.icinga.org
* Social media: [Twitter](https://twitter.com/icinga), [Facebook](https://www.facebook.com/icinga), [G+](http://plus.google.com/+icinga), [Xing](https://www.xing.com/communities/groups/icinga-da4b-1060043), [LinkedIn](https://www.linkedin.com/groups/Icinga-1921830/about)
