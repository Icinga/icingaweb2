# Release Workflow <a id="release-workflow"></a>

#### Table of Content

- [1. Preparations](#preparations)
  - [1.1. Issues](#issues)
  - [1.2. Backport Commits](#backport-commits)
  - [1.3. Authors](#authors)
- [2. Version](#version)
- [3. Changelog](#changelog)
- [4. Git Tag](#git-tag)
- [5. Package Builds](#package-builds)
  - [5.1. RPM Packages](#rpm-packages)
  - [5.2. DEB Packages](#deb-packages)
- [6. Build Server](#build-server)
- [7. Release Tests](#release-tests)
- [8. GitHub Release](#github-release)
- [9. Post Release](#post-release)
  - [9.1. Online Documentation](#online-documentation)
  - [9.2. Announcement](#announcement)
  - [9.3. Project Management](#project-management)

## Preparations <a id="preparations"></a>

Specify the release version.

```
VERSION=2.6.0
```

Add your signing key to your Git configuration file, if not already there.

```
vim $HOME/.gitconfig

[user]
        email = michael.friedrich@icinga.com
        name = Michael Friedrich
        signingkey = D14A1F16
```

### Issues <a id="issues"></a>

Check issues at https://github.com/Icinga/icingaweb2

### Backport Commits <a id="backport-commits"></a>

For minor versions not branched off git master you need
to manually backports any and all commits from the
master branch which should be part of this release.

### Authors <a id="authors"></a>

Update the [.mailmap](.mailmap) and [AUTHORS](AUTHORS) files:

```
git log --use-mailmap | grep '^Author:' | cut -f2- -d' ' | sort | uniq > AUTHORS
```

## Version <a id="version"></a>

Update the version in the following files:

* [VERSION](VERSION)
* Application Version: [library/Icinga/Application/Version.php](library/Icinga/Application/Version.php)
* Module Versions in `modules/*/module.info`

Commands:

```
echo "v$VERSION" > VERSION
sed -i '' "s/const VERSION = '.*'/const VERSION = '$VERSION'/g" library/Icinga/Application/Version.php
find . -type f -name '*.info' -exec sed -i '' "s/Version: .*/Version: $VERSION/g" {} \;
```

## Changelog <a id="changelog"></a>

Link to the milestone and closed=1 as filter.

## Git Tag  <a id="git-tag"></a>

```
git commit -v -a -m "Release version $VERSION"
```

Create a signed tag (tags/v<VERSION>) on the `master` branch (for major
releases) or the `support` branch (for minor releases).

```
git tag -s -m "Version $VERSION" v$VERSION
```

Push the tag:

```
git push --tags
```

**For major releases:** Create a new `support` branch:

```
git checkout master
git checkout -b support/2.6
git push -u origin support/2.6
```

## Package Builds  <a id="package-builds"></a>

### RPM Packages  <a id="rpm-packages"></a>

```
git clone git@github.com:icinga/rpm-icingaweb2.git && cd rpm-icingaweb2
```

#### Branch Workflow

**Major releases** are branched off `master`.

```
git checkout master && git pull
```

**Bugfix releases** are created in the `release` branch and later merged to master.

```
git checkout release && git pull
```

#### Release Commit

Set the `Version`, `Revision` and `changelog` inside the spec file.

```
sed -i "s/Version: .*/Version: $VERSION/g" icingaweb2.spec

vim icingaweb2.spec

%changelog
* Fri Apr 27 2018 Eric.Lippmann <eric.lippmann@icinga.com> 2.5.3-1
- Update to 2.5.3
```

```
git commit -av -m "Release 2.5.3-1"
git push
```

**Note for major releases**: Update release branch to latest.
`git checkout release && git pull && git merge master && git push`

**Note for minor releases**: Cherry-pick the release commit into master.
`git checkout master && git pull && git cherry-pick release && git push`


### DEB Packages  <a id="deb-packages"></a>

```
git clone git@github.com:icinga/deb-icingaweb2.git && cd deb-icingaweb2
```

#### Branch Workflow

**Major releases** are branched off `master`.

```
git checkout master && git pull
```

**Bugfix releases** are created in the `release` branch and later merged to master.

```
git checkout release && git pull
```

#### Release Commit

Set the `Version`, `Revision` and `changelog` inside the spec file.

```
./dch 2.5.3-1 "Update to 2.5.3"
```

```
git commit -av -m "Release 2.5.3-1"
git push
```

```
git commit -av -m "Release 2.5.3-1"
```

**Note for major releases**: Update release branch to latest.
`git checkout release && git pull && git merge master && git push`

**Note for minor releases**: Cherry-pick the release commit into master.
`git checkout master && git pull && git cherry-pick release && git push`


#### DEB with dch on macOS

```
docker run -v `pwd`:/mnt/packaging -ti ubuntu:xenial bash

apt-get update
apt-get install git dev-tools vim
cd /mnt/packaging

git config --global user.name "Eric Lippmann"
git config --global user.email "eric.lippmann@icinga.com"

./dch 2.5.3-1 "Update to 2.5.3"
```

## Build Server <a id="build-server"></a>

* Verify package build changes for this version.
* Test the snapshot packages for all distributions beforehand.
* Build the newly created Git tag for Debian/RHEL/SuSE.

## Release Tests  <a id="release-tests"></a>

* Provision the vagrant boxes and test the release packages.

## GitHub Release  <a id="github-release"></a>

Create a new release for the newly created Git tag: https://github.com/Icinga/icingaweb2/releases

### Online Documentation  <a id="online-documentation"></a>

Ask @bobapple to update the documentation at docs.icinga.com.

### Announcement  <a id="announcement"></a>

* Create a new blog post on www.icinga.com/blog
* Social media: [Twitter](https://twitter.com/icinga), [Facebook](https://www.facebook.com/icinga), [G+](https://plus.google.com/+icinga), [Xing](https://www.xing.com/communities/groups/icinga-da4b-1060043), [LinkedIn](https://www.linkedin.com/groups/Icinga-1921830/about)
* Update IRC channel topic

### Project Management  <a id="project-management"></a>

* Add new minor version on [GitHub](https://github.com/Icinga/icingaweb2/milestones).
* Close the released version on [GitHub](https://github.com/Icinga/icingaweb2/milestones).
