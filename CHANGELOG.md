# Icinga Web 2 Changelog

Please make sure to always read our [Upgrading](doc/80-Upgrading.md) documentation before switching to a new version.

## What's New

### What's New in Version 2.12.5

You can find all issues related to this release on our [Roadmap](https://github.com/Icinga/icingaweb2/milestone/85?closed=1).

#### PHP 8.4 Support

We're again a little behind schedule, but now we support PHP 8.4! This means that installations on Ubuntu 25.04 and
Fedora 42+ can now install Icinga Web without worrying about PHP related incompatibilities. Icinga packages will be
available in the next few days.

#### Good Things Take Time

There's only a single (notable) recent issue that is fixed with this release. All the others are a bit older.

* External URLs set up as dashlets are not *embedded* the same as navigation items [#5346](https://github.com/Icinga/icingaweb2/issues/5346)

But the team sat together a few weeks ago and fixed a bug here and there. And of course, also in Icinga Web!

* Users who are not allowed to change the theme, cannot change the theme mode either [#5385](https://github.com/Icinga/icingaweb2/issues/5385)
* Filtering for older-than events with relative time does not work [#5263](https://github.com/Icinga/icingaweb2/issues/5263)
* External logout not working from the navigation dashboard [#5000](https://github.com/Icinga/icingaweb2/issues/5000)
* Empty values are NULL in CSV exports [#5350](https://github.com/Icinga/icingaweb2/issues/5350)

#### Breaking, Somewhat

This is mainly for developers.

With the support of PHP 8.4, we introduced a new environment variable, `ICINGAWEB_ENVIRONMENT`. Unless set to `dev`,
Icinga Web will not show nor log deprecation notices anymore.

### What's New in Version 2.12.4

This is a hotfix release which fixes the following issue:

Database login broken after upgrade [#5343](https://github.com/Icinga/icingaweb2/issues/5343)

### What's New in Version 2.12.3

**Notice:** This is a security release. It is recommended to upgrade _immediately_.

You can find all issues related to this release on our [Roadmap](https://github.com/Icinga/icingaweb2/milestone/83?closed=1).

#### Vulnerabilities, Closed

Cross site scripting is one of the worst attacks on web based platforms. Especially, if carrying it out is as easy as
the first two mentioned here. You might recognize the open redirect on the login. You are correct, we attempted to fix
it already with v2.11.3 but underestimated PHP's quirks. The last is difficult to exploit, hence the lowest severity
of all, but don't be fooled by that!

All four of them are backported to v2.11.5.

* XSS in embedded content [CVE-2025-27405](https://github.com/Icinga/icingaweb2/security/advisories/GHSA-3x37-fjc3-ch8w)
* DOM-based XSS [CVE-2025-27404](https://github.com/Icinga/icingaweb2/security/advisories/GHSA-c6pg-h955-wf66)
* Open redirect on login page [CVE-2025-30164](https://github.com/Icinga/icingaweb2/security/advisories/GHSA-8r73-6686-wv8q)
* Reflected XSS [CVE-2025-27609](https://github.com/Icinga/icingaweb2/security/advisories/GHSA-5cjw-fwjc-8j38)

Big thanks to all finders / reporters! :+1:

#### Bugs, Exterminated

Did you know, that we started [Icinga Notifications](https://icinga.com/docs/icinga-notifications/latest/) with support
for PostgreSQL first? Reason for that is, we wanted to make sure we are fully compatible with it right away. To ensure
things like logging in with a PostgreSQL authentication/group backend is case-insensitive, like it was always the case
for MySQL. Now it **really** is case-insensitive! There are also two issues fixed, which many of you will probably have
noticed since v2.12.2, sorry that it took so long :)

* Login against Postgres DB is case-sensitive [#5223](https://github.com/Icinga/icingaweb2/issues/5223)
* Role list has no functioning quick search [#5300](https://github.com/Icinga/icingaweb2/issues/5300)
* After clicking on Check now, the page does not refresh itself [#5293](https://github.com/Icinga/icingaweb2/issues/5293)
* Service States display wrong since update to 2.12.2 [#5290](https://github.com/Icinga/icingaweb2/issues/5290)

### What's New in Version 2.12.2

You can find all issues related to this release on our [Roadmap](https://github.com/Icinga/icingaweb2/milestone/81?closed=1).

#### General Fixes

Icinga Web has become quite mature over the years. Typically, only new features cause issues and require fixing.
However, there is always an exception to every rule, as shown by the issue where roles were not sorted by name.
We also improved the settings menu — the one that opens when hovering over the cog icon next to your name. We heard
your feedback about it closing too easily and made it more user-friendly. With v2.12.0, we introduced a new security
feature, the Content-Security-Policy header, which is designed to prevent cross-site scripting attacks. Ironically,
we initially forgot to include the `script-src` policy in it.

* Sort by name of roles does not work properly [#4789](https://github.com/Icinga/icingaweb2/issues/4789)
* Settings menu flyout closes too fast / easy [#5196](https://github.com/Icinga/icingaweb2/issues/5196)
* CSP header is missing the script-src policy [#5180](https://github.com/Icinga/icingaweb2/issues/5180)

#### Love For an Old Fellow

The *monitoring* module has been part of Icinga Web from the very beginning. Although it’s being replaced by Icinga DB
Web, some of you still rely on it, which is why we continue to fix issues — even if they’re not entirely our
responsibility, as the first example demonstrates. This particular issue only affects users on PHP 8.1 (> .24). The
second issue, introduced by a contribution in v2.12.0, caused some history entries to disappear but was resolved with
another contribution — a great example of teamwork. The third issue is also a testament to the module's age: Icinga 2
has automatically removed child downtimes since v2.13.0, and this is now accounted for in the module as well.

* Broken event overview due to IntlDateFormatter [#5172](https://github.com/Icinga/icingaweb2/issues/5172)
* Downtimes, which were started and canceled, are missing in the history [#5176](https://github.com/Icinga/icingaweb2/issues/5176)
* Usage of IcingaWeb2 api command returns 404, but is successful [#5183](https://github.com/Icinga/icingaweb2/issues/5183)

#### Awesome Customizations

Many of you have already tried Icinga DB Web and might have noticed it uses slightly different icons for its
sidebar entries. These icons are provided by Font Awesome, and now you can use them as well. Just find a suitable
icon on their [website](https://fontawesome.com/search?o=r&m=free&s=solid) and prefix its name with `fa-`. If you
hadn’t used an icon at all for a menu item and upgraded to Icinga DB Web, opening it will no longer result in an
error. Lastly, a particularly tricky issue caused the dashboard to display dashlets twice and prevented their
deletion. This should be fixed now — fingers crossed!

* Allow fontawesome icons as menu items [#5205](https://github.com/Icinga/icingaweb2/issues/5205)
* Error while opening a navigation root item [#5177](https://github.com/Icinga/icingaweb2/issues/5177)
* Dashlets twice in dashboard & not deletable [#5203](https://github.com/Icinga/icingaweb2/issues/5203)

#### Framework Enhancements

Those of you who take customization to the next level will be glad to hear that hooking into the rendering of plugin
output is now easier, as the first line and long output are now combined when passed to the renderer. Anyone using
the Icinga Web Graphite Integration may be familiar with this issue and will be relieved to know that graphs no
longer disappear when using graph controls. And finally, a new release for Icinga Director is coming next week,
which will hook into the rendering of custom variables. This feature has been available since Icinga Web v2.10.0,
but it’s now slightly improved.

* PluginOutputRenderer gets called twice [#5271](https://github.com/Icinga/icingaweb2/issues/5271)
* Graphs disappear after form controls are used [#4996](https://github.com/Icinga/icingaweb2/issues/4996)
* Make subgroups of custom variables fully collapsible [#5256](https://github.com/Icinga/icingaweb2/issues/5256)

### What's New in Version 2.12.1

You can find all issues related to this release on our [Roadmap](https://github.com/Icinga/icingaweb2/milestone/80?closed=1).

#### PHP 8.3 Support

This time we're a little ahead for once. PHP 8.3 is due in a week, and we are compatible with it now!
There's not much else to say about it, so let's continue with the fixes.

* Support for PHP 8.3 [#5136](https://github.com/Icinga/icingaweb2/issues/5136)

#### Fixes

You may have noticed a dashboard endlessly loading in the morning after you got to work again.
The web server may also have stopped that with a complaint about a too long URL. This is now
fixed and the dashboard should appear as usual. Then there was an issue with our support for
PostgreSQL. We learned it the hard way to avoid such already in the past again and again.
Though, this one slipped through our thorough testing and prevented some from successfully
migrating the database schema. It's fixed now. Another fixed issue, is that the UI looks
somewhat skewed if you have CSP enabled and logged out and in again.

* Login Redirect Loop [#5133](https://github.com/Icinga/icingaweb2/issues/5133)
* UI database migration not fully compatible with PostgreSQL [#5129](https://github.com/Icinga/icingaweb2/issues/5129)
* Missing styles when logging out and in while CSP is enabled [#5126](https://github.com/Icinga/icingaweb2/issues/5126)

### What's New in Version 2.12.0

You can find all issues related to this release on our [Roadmap](https://github.com/Icinga/icingaweb2/milestone/79?closed=1).

#### PHP 8.2 Support

This release finally adds support for the latest version of PHP, 8.2. This means that installations on Debian Bookworm,
Ubuntu 23.10 and Fedora 38+ can now install Icinga Web without worrying about PHP related incompatibilities. Some of our
other modules still require an update, which they will receive in the coming weeks. Next week Icinga DB Web will follow.
Icinga Certificate Monitoring, Icinga Business Process Modeling and Icinga Reporting the weeks after.

* Support for PHP 8.2 [#4918](https://github.com/Icinga/icingaweb2/issues/4918)

#### Simplified Database Migrations

Anyone who already performed an upgrade of Icinga Web or some Icinga Web module in the past has done it: A database
schema upgrade. This usually involved the following steps:

* Knowing that a database might need an upgrade
* Figuring out if that's true, by checking the upgrade documentation
* Alternatively relying on the users to find out about it as they're running into database errors
* Locating the upgrade file
* Connecting to the machine the database is running on
* Transferring the upgrade file over
* Importing the upgrade file into the correct database

With Icinga Web v2.12 and later, upgrade the application and, yes, still check the upgrade documentation. That's still
mandatory! But if you notice there, that just a database upgrade is necessary you can simply log in and check the
*Migrations* section in the *System* menu. With a single additional click you can perform the database upgrade directly
in the UI then. This view also offers to migrate module databases. The earlier mentioned updates of Icinga Certificate
Monitoring and Icinga Reporting will pop up there once they arrive.

* Provide a way to easily perform database migrations [#5043](https://github.com/Icinga/icingaweb2/issues/5043)

#### Content-Security-Policy Conformance

Err, what? That's an HTTP header to prevent cross site scripting attacks. (XSS) Still confused? It's a technique
to stop bad individuals. A very effective technique even. You don't need to do anything, other than visiting the
general configuration of Icinga Web and enabling the respective setting. The only downer here, is that support
for it isn't as widespread yet as you might hope. Icinga Web itself of course has it, but not all modules. But don't
worry, you might have guessed it already, those are the same modules which will receive updates in the coming weeks.

* Support for Content-Security-Policy [#4528](https://github.com/Icinga/icingaweb2/issues/4528)

#### Other Notable Changes

There are not only such big changes as previously mentioned part of this release.

Some module developers may be happy to hear that there is now more control for the server over the UI possible.
And with a new Javascript event it is now possible to react upon a column's content being moved to another column.
Now built-in into the framework is also an easy way to mark content in the UI as being copiable with a single click
by the user.

* Allow to initiate a refresh with `__REFRESH__` [#5108](https://github.com/Icinga/icingaweb2/pull/5108)
* Don't refresh twice upon `__CLOSE__` [#5106](https://github.com/Icinga/icingaweb2/pull/5106)
* Add event `column-moved` [#5049](https://github.com/Icinga/icingaweb2/pull/5049)
* Add copy-to-clipboard behavior [#5041](https://github.com/Icinga/icingaweb2/pull/5041)

Then there are some fixes related to other integrations. It is now possible to set up resources for Oracle databases,
without a `host` setting, which facilitate dynamic host name resolution. A part of the `monitoring` module's integration
into the Icinga Certificate Monitoring prevents a crash of its collector daemon in case the connection to the IDO was
interrupted. And exported content, with data that has double quotes, to CSV is now correctly escaped.

* Access Oracle Database via tnsnames.ora / LDAP Naming Services [#5062](https://github.com/Icinga/icingaweb2/issues/5062)
* Reduce risk of crashing the x509 collector daemon [#5115](https://github.com/Icinga/icingaweb2/pull/5115)
* CSV export does not escape double quotes [#4910](https://github.com/Icinga/icingaweb2/issues/4910)

### What's New in Version 2.11.4

You can find all issues related to this release on our [Roadmap](https://github.com/Icinga/icingaweb2/milestone/78?closed=1).

#### Notable Fixes

* Add/Edit dashlet not possible [#4970](https://github.com/Icinga/icingaweb2/issues/4970)
* Custom library path + custom library, without slash in its name, results in exception [#4971](https://github.com/Icinga/icingaweb2/issues/4971)
* Reflected XSS vulnerability in User Backends config page [#4979](https://github.com/Icinga/icingaweb2/issues/4979)

### What's New in Version 2.11.3

**Notice**: This is a security release. It is recommended to upgrade immediately.

You can find all issues related to this release on our [Roadmap](https://github.com/Icinga/icingaweb2/milestone/77?closed=1).

#### Minor to Medium Vulnerabilities

In late November we received multiple security vulnerability reports. They are listed below in order of severity
where you can also find further notes:

* Open Redirects for logged in users [#4945](https://github.com/Icinga/icingaweb2/issues/4945)
  This one is quite old, though got worse and easier to exploit since v2.9. It is for this reason that
  this fix has been backported all the way down to v2.9.8. It can be used to exploit incautious users,
  no matter their browser and its security settings. They need to click a specifically crafted link
  (in the easiest form) and log in to Icinga Web by filling in their access credentials. If they're
  already logged in, (due to an existing session or SSO) the browser prevents the exploit from happening.
  We encourage you to update to the latest release as soon as possible to mitigate any potential harm.

* SSH Resource Configuration form XSS Bug [#4947](https://github.com/Icinga/icingaweb2/issues/4947)
  Dashlets allow the user to run Javascript code [#4959](https://github.com/Icinga/icingaweb2/issues/4959)
  These two are very similar. Both revolve around Javascript getting injected by logged in users
  interacting with forms. The SSH resource configuration requires configuration access though and, since
  custom dashlets are only shown to the user who created them, the dashlet configuration cannot affect
  other users. Note that both interactions cannot be initiated externally by CSRF, the forms are protected
  against this. Because of this we assess the severity of these two very low.

* Role member suggestion endpoint is reachable for unauthorized users [#4961](https://github.com/Icinga/icingaweb2/issues/4961)
  This is more a case of missing authorization checks than a full fledged security flaw. But nevertheless,
  it allows any logged-in user, by use of a manually crafted request, to retrieve the names of all available
  users and usergroups.

#### The More Usual Dose of Fixes

* Browser print dialog result broken [#4957](https://github.com/Icinga/icingaweb2/issues/4957)
  If you tried to export a view using the browser's builtin print dialog, (e.g. Ctrl+P) you may have
  noticed a degradation of fanciness since the update to v2.10. This looks nicer than ever now.

* Shared navigation items are not accessible [#4953](https://github.com/Icinga/icingaweb2/issues/4953)
  Since v2.11.0 the shared navigation overview hasn't been accessible using the configuration menu.
  It is now accessible again.

* While using dropdown filter menu it gets closed automatically due to autorefresh [#4942](https://github.com/Icinga/icingaweb2/issues/4942)
  Are you annoyed by the filter editor repeatedly closing the column selection while you're looking for
  something? We have you covered with a fix for this and the column selection should stay open as long
  as you don't click anywhere else.

### What's New in Version 2.11.2

You can find all issues related to this release on our [Roadmap](https://github.com/Icinga/icingaweb2/milestone/76?closed=1).

It brings performance improvements and general fixes. Most notable of which are that having e.g. notifications
disabled globally is now visible in the menu again and that the event history is grouped by days again.

### What's New in Version 2.11.1

You can find all issues related to this release on our [Roadmap](https://github.com/Icinga/icingaweb2/milestone/75?closed=1).

This update's main focus is to solve the issue that all history views didn't work correctly or showed invalid
time and dates. ([#4853](https://github.com/Icinga/icingaweb2/issues/4853))

### What's New in Version 2.11.0

You can find all issues related to this release on our [Roadmap](https://github.com/Icinga/icingaweb2/milestone/70?closed=1).

#### Enhancements, Some

Many of you were waiting for it: PHP 8.1 Support. This means that Icinga Web should be available soon on e.g.
Ubuntu 22.04. You'll also notice that we changed the sidebar, as the user menu went to the very bottom of it.
With it moved the less frequently used menu entries (system and configuration) to a section that pops up by
hovering over the :gear: icon. We did that in order to prepare an area where we can add further functionality
in the future. Oh, and announcements are now visible in fullscreen mode. :upside_down_face:

* Support for PHP 8.1 [#4609](https://github.com/Icinga/icingaweb2/issues/4609)
* Redesign User Menu [#4651](https://github.com/Icinga/icingaweb2/issues/4651)
* &showFullscreen suppresses announcements [#4596](https://github.com/Icinga/icingaweb2/issues/4596)

#### Fixes, More

There are also bug fixes of course. The first mentioned here is one we fixed *accidentally*, as by adding support for
PHP 8.1 we avoided a common PHP quirk responsible for it. If you have a host or service with an asterisk in the name,
it will show up correctly in the detail view now. There was also a remaining issue with the theme mode selection in the
user preferences which is fixed now.

* Navigation item filter `*` not working [#4772](https://github.com/Icinga/icingaweb2/issues/4772)
* Objects with a `*` in the name are not found [#4682](https://github.com/Icinga/icingaweb2/issues/4682)
* Theme mode switch disabled on theme with mode support [#4744](https://github.com/Icinga/icingaweb2/issues/4744)

#### When developers become cleaning maniacs

Usually I write a short note at the start of release notes to make you read the upgrading documentation. This time
however, a more prominent hint is required. We've removed so much (legacy) stuff, anyone tasked with upgrading is
obliged to read [the upgrading documentation](https://icinga.com/docs/icinga-web-2/latest/doc/80-Upgrading/#upgrading-to-icinga-web-211x).
The changes mentioned below only provide a glimpse at it.

* User preferences in INI files not supported anymore [#4765](https://github.com/Icinga/icingaweb2/pull/4765)
* mysql: use of utf8 vs utfmb4 [#4680](https://github.com/Icinga/icingaweb2/issues/4680)
* Remove Vagrant file and its assets [#4762](https://github.com/Icinga/icingaweb2/pull/4762)

### What's New in Version 2.10.1

It's a rather small update this time without any critical bugs. :tada: So let's get straight to the fixes:

* Clicking anywhere on a list item in the dashboard now opens the primary link again, instead of nothing [#4710](https://github.com/Icinga/icingaweb2/issues/4710)
* The `Check Now` and `Remove Acknowledgement` quick actions in an object's detail header are now working again [#4711](https://github.com/Icinga/icingaweb2/issues/4711)
* Clicking on the big number in the tactical overview if there are `UNKNOWN` services, shows `UNKNOWN` services now [#4714](https://github.com/Icinga/icingaweb2/issues/4714)
* The contrast of text in the sidebar, while in light mode, has been increased [#4720](https://github.com/Icinga/icingaweb2/issues/4720)
* A theme without mode support, which is set globally, now also prevents users from configuring the mode [#4723](https://github.com/Icinga/icingaweb2/issues/4723)

### What's New in Version 2.10.0

You can find all issues related to this release on our [Roadmap](https://github.com/Icinga/icingaweb2/milestone/63?closed=1).

Please make sure to also check the respective [upgrading section](https://icinga.com/docs/icinga-web-2/latest/doc/80-Upgrading/#upgrading-to-icinga-web-2-210x)
in the documentation.

#### The Appearance of Dark and Light

We have already spoken a lot about the [theme mode support](https://icinga.com/blog/2021/06/16/introducing-dark-and-light-theme-modes/)
that we were working on [for some time](https://icinga.com/blog/2022/02/10/icinga-web-not-just-black-and-white/) now.
It was planned for v2.9.0, but in respect of many modules and themes out there we gave it the deserved attention.
Below is a glimpse of what this looks like.

[![Icinga Web 2 Theme Mode Preview](https://icinga.com/wp-content/uploads/2022/03/theme-mode-demo-small.jpg "Icinga Web 2 Theme Mode Preview")](https://icinga.com/wp-content/uploads/2022/03/theme-mode-demo.jpg)

#### Custom Variables Shown Unaltered – Or not

Icinga Web 2 had some bad habits when displaying custom variables in the UI. We've driven out the last one regarding
names now. Uppercase characters are now shown as such. What Icinga Web 2 stopped doing though, can now be accomplished
by modules. A new hook that enables modules to influence the rendering of custom variables has been introduced.

* CustomVarNames should not be converted to lowercase [#4639](https://github.com/Icinga/icingaweb2/issues/4639)
* Display the Director Caption of a Custom Variable [#3479](https://github.com/Icinga/icingaweb2/issues/3479)

#### Surprising Beauty in Exported Places

Anyone who already attempted to export a list of services to PDF has seen the degradation of details in recent years.
Be it images, icons, colors or the general layout. We simply reached a technical limit with the builtin PDF export.
That is why we made [Icinga PDF Export](https://github.com/Icinga/icingaweb2-module-pdfexport). Icinga Web 2 has now
a much enhanced compatibility with it. Exporting a list of services while Icinga PDF Export is set up, will now lead
to a much better looking result.

* Enhance PDF export [#4685](https://github.com/Icinga/icingaweb2/pull/4685)
* Image not found when creating PDF view of objects [#4674](https://github.com/Icinga/icingaweb2/issues/4674)

### What's New in Version 2.9.6

**Notice**: This is a security release. It is recommended to upgrade immediately.

#### Security Fixes

This release includes three security related fixes. The first is a path traversal issue that affects installations
of v2.9.0 and above. Another one allows admins to run arbitrary PHP code just by accessing the UI. The last one may
disclose unwanted details to restricted users. Please check the advisories on GitHub for more details.

* Path traversal in static library file requests for unauthenticated users [GHSA-5p3f-rh28-8frw](https://github.com/Icinga/icingaweb2/security/advisories/GHSA-5p3f-rh28-8frw)
* SSH resources allow arbitrary code execution for authenticated users [GHSA-v9mv-h52f-7g63](https://github.com/Icinga/icingaweb2/security/advisories/GHSA-v9mv-h52f-7g63)
* Unwanted disclosure of hosts and related data, linked to decommissioned services [GHSA-qcmg-vr56-x9wf](https://github.com/Icinga/icingaweb2/security/advisories/GHSA-qcmg-vr56-x9wf)

### What's New in Version 2.9.5

This is a hotfix release which fixes the following issues:

* Some detail views of Icinga Director and other modules are broken with Web 2.9.4 [#4598](https://github.com/Icinga/icingaweb2/issues/4598)
* Error on skipping LDAP Discovery [#4603](https://github.com/Icinga/icingaweb2/issues/4603)

### What's New in Version 2.9.4

You can also find all issues related to this release on our [Roadmap](https://github.com/Icinga/icingaweb2/milestone/68?closed=1).

#### Broken Preference Configuration

The preferences configuration broke with the release of v2.9 in some cases. Previously it was possible to access this
and the general configuration without any configuration at all on disk. This is now possible again. The preferences of
some users, which have a theme of a disabled module enabled, also showed an error. This doesn't happen anymore now.

* Config/Preferences not accessible without config.ini [#4504](https://github.com/Icinga/icingaweb2/issues/4504)
* "My Account" broken after Upgrade from 2.8.2 to 2.9.3 [#4512](https://github.com/Icinga/icingaweb2/issues/4512)

#### Notable Fixes in the UI

For a long time now, comments in lists had the bad habit to spread erratically if their content was large. They're
limited to two lines now in lists and are still shown in full glory in their respective detail area. While talking
of lines... Plugin output with subsequent empty lines erroneously showed only one of them. This is now fixed.

* Proposal for new Feature make comments collapsible [#4515](https://github.com/Icinga/icingaweb2/issues/4515)
* new line character is being removed in the plugin output [#4522](https://github.com/Icinga/icingaweb2/issues/4522)

#### Less Notable But No Less Important Fixes

We are actually very committed to provide a good experience for restricted users. So I'm happy to tell you that a nasty
bug is fixed that resulted in the focus being lost randomly. Third party integrations are also important to us, hence
I'm happy that this release fixes an issue where module specific JavaScript didn't load properly. Are you happy now?

* `announcements` request clears focus [#4543](https://github.com/Icinga/icingaweb2/issues/4543)
* js: Fix regression for loading dependent modules for sub-containers [#4533](https://github.com/Icinga/icingaweb2/issues/4533)

### What's New in Version 2.9.3

You can also find the issues related to this release on our [Roadmap](https://github.com/Icinga/icingaweb2/milestone/66?closed=1).

#### Staying remembered on RHEL/CentOS 7 now possible

RHEL/CentOS 7 still relies on OpenSSL v1.0.2 by default. A change in v2.9.1 resulted in an error in combination with
this when ticking `Stay Logged In` during authentication. Staying logged in now works fine also on this platform.

* Stay Logged In - Unknown cipher algorithm [#4493](https://github.com/Icinga/icingaweb2/issues/4493)

#### Missing icons with SLES/OpenSUSE 15

If you're running Icinga Web 2 Version 2.9.x on a SLES/OpenSUSE 15.x, you may have noticed some missing icons in the UI.
This is due to a missing PHP extension `fileinfo`. By upgrading to this release using packages, this dependency will now
be installed automatically.

* Missing fileinfo php extension on SLES/OpenSUSE 15+ [#4503](https://github.com/Icinga/icingaweb2/issues/4503)

#### Child downtimes for services are now removed automatically

With Icinga v2.13, Icinga Web 2 will now make sure that service downtimes that were created automatically are also
removed automatically. This will only work for downtimes you create with the `All Services` option after upgrading
to this release. It will not work for downtimes created with earlier versions of Icinga Web 2.

* If appropriate, set the API parameter all_services for schedule-downtime [#4501](https://github.com/Icinga/icingaweb2/pull/4501)

### What's New in Version 2.9.2

This is a hotfix release. v2.9.1 included a change that wasn't compatible with PostgreSQL again. This has been fixed
in this release. (#4490)

### What's New in Version 2.9.1

You can find all issues related to this release on our [Roadmap](https://github.com/Icinga/icingaweb2/milestone/64?closed=1).

Please make sure to also check the respective [upgrading section](https://icinga.com/docs/icinga-web-2/latest/doc/80-Upgrading/#upgrading-to-icinga-web-2-291)
in the documentation.

This release is accompanied by the minor releases v2.7.6 and v2.8.4 which include the fix for the flattened custom variables.

#### Pancakes everywhere

One of the security fixes included in v2.7.5, v2.8.3 and v2.9.0 went rampant and let you see similarities between custom
variables and pancakes. These are gone now. Also, the login allowed some users to bake pancakes on their CPUs. However,
we'd still recommend not to. What we do recommend, is to use graphical details to ease recognition. A pancake 🥞 in
performance data labels for example.

* Nested custom variables are flattened [#4439](https://github.com/Icinga/icingaweb2/issues/4439)
* Disable login orb animation and all orbs for themes [#4468](https://github.com/Icinga/icingaweb2/pull/4468)
* SVG chart library doesn't process input as UTF-8 [#4462](https://github.com/Icinga/icingaweb2/issues/4462)

#### Staying remembered too difficult

We all have sometimes difficulties remembering people we rarely meet. Especially obvious is this on those that slip
through because they don't do the same things we do. With v2.9.0 this has happened for PostgreSQL, PHP v5.6-v7.0 and
setup wizard users. Now they get their deserved attention, and Icinga Web 2 will remember them just like all others.

* RememberMe not working with only PostgreSQL [#4441](https://github.com/Icinga/icingaweb2/issues/4441)
* RememberMe compatibility with php version 5.6+ [#4472](https://github.com/Icinga/icingaweb2/pull/4472)
* RememberMe fails after running the wizard for grants [#4434](https://github.com/Icinga/icingaweb2/issues/4434)

#### Being picky pays off

A custom datetime picker was introduced with v2.9.0. It had it's issues, but we didn't anticipate that much headwind.
After careful reconsideration, we chose to only show the custom datetime picker for Firefox and IE users. Other browsers
have their own capable enough native implementation which, in Chrome's case, may even be superior. If it is now used,
it also closes automatically and doesn't swallow unrelated key presses.

* Datetimepicker not usable by keyboard [#4442](https://github.com/Icinga/icingaweb2/issues/4442)
* Close the datepicker automatically [#4461](https://github.com/Icinga/icingaweb2/issues/4461)
* Paragraphs in Acknowledge/Downtime not possible [#4443](https://github.com/Icinga/icingaweb2/issues/4443)

### What's New in Version 2.9.0

You can find all issues related to this release on our [Roadmap](https://github.com/Icinga/icingaweb2/milestone/59?closed=1).

Please make sure to also check the respective [upgrading section](https://icinga.com/docs/icinga-web-2/latest/doc/80-Upgrading/#upgrading-to-icinga-web-2-29x)
in the documentation.

This release is accompanied by the minor releases v2.7.5 and v2.8.3 which include the security fixes mentioned below.

#### Icinga DB

We continue our endeavour soon. Icinga Web 2 is still a crucial part of it and this update is again required
for Icinga DB. If you like to participate again, don't forget to update Icinga Web 2 as well.

#### Security Fixes

This release includes two security related fixes. Both were published as part of a security advisory on Github.
They allow the circumvention of custom variable protection rules and blacklists as well as a path traversal if
the `doc` module is enabled. Please check the respective advisory for details.

* Custom variable protection and blacklists can be circumvented [GHSA-2xv9-886q-p7xx](https://github.com/Icinga/icingaweb2/security/advisories/GHSA-2xv9-886q-p7xx)
* Possible path traversal by use of the `doc` module [GHSA-cmgc-h4cx-3v43](https://github.com/Icinga/icingaweb2/security/advisories/GHSA-cmgc-h4cx-3v43)

#### RBAC, The Elephant In Icinga Web 2

Role Based Access Control, for the non-initiated. I'll make it short: Permission refusals, Role inheritance,
Privilege Audit. Icinga DB will also solve the long-standing issue [#2455](https://github.com/Icinga/icingaweb2/issues/2455)
and also allows [#3349](https://github.com/Icinga/icingaweb2/issues/3349) and [#3550](https://github.com/Icinga/icingaweb2/issues/3550).
I've also written a blog post about this very topic: https://icinga.com/blog/2021/04/07/web-access-control-redefined/

* Authorization enhancements [#4306](https://github.com/Icinga/icingaweb2/pull/4306)
* Audit View [#4336](https://github.com/Icinga/icingaweb2/pull/4336)
* Highlight modules with permissions set inside a role [#4241](https://github.com/Icinga/icingaweb2/issues/4241)

#### Support for PHP 8

PHP 8 is released and with Icinga Web 2.9 it will now (hopefully) work flawlessly. We also took the chance
to prepare to drop the support of some legacy PHP versions. We now require PHP 7.3 at a minimum and all
versions below that will not be supported anymore with the release of v2.11.

* Support PHP 8 [#4289](https://github.com/Icinga/icingaweb2/pull/4289)
* Raise minimum required PHP version to 7.3 [#4397](https://github.com/Icinga/icingaweb2/pull/4397)

#### Stay, Be Remembered

Have you ever been disappointed that Icinga Web 2 always forgets you after closing your browser? This is in
your hands now! Just tick the new checkbox on the login screen and Icinga Web 2 doesn't forget your presence
anymore. Unless of course the administrator or you on a different device clears your session.

* Implement a "remember me" feature [#2495](https://github.com/Icinga/icingaweb2/issues/2495)

#### It Does Matter, When

Browsers are bad when it's about date and time inputs. (I'm looking at you Mozilla!) Now we've given our hopes
up and use a specifically invented solution to show you a date and time picker throughout every browser. With
Icinga v2.13 onwards you will also be able to use this when defining an expiry date for comments! Though, you
might not necessarily use it that often once you've configured new custom defaults for downtime endings.

* Add datetime picker widget [#4354](https://github.com/Icinga/icingaweb2/pull/4354)
* Expire Option for Comments [#3447](https://github.com/Icinga/icingaweb2/issues/3447)
* Custom defaults for downtime end, comment and duration [#4364](https://github.com/Icinga/icingaweb2/issues/4364)

### What's New in Version 2.8.2

**Notice**: This is a security release. It is recommended to immediately upgrade to this release.

You can find all issues related to this release on the respective [milestone](https://github.com/Icinga/icingaweb2/milestone/62?closed=1).

#### Path Traversal Vulnerability

The vulnerability in question allows an attacker to access arbitrary files which are readable by the process running
Icinga Web 2. Technical details can be found at the corresponding [CVE-2020-24368](https://cve.mitre.org/cgi-bin/cvename.cgi?name=CVE-2020-24368)
and in the issue below.

* Possible path traversal when serving static image files [#4226](https://github.com/Icinga/icingaweb2/issues/4226)

#### Broken Negated Filters with PostgreSQL

We've also included a small non-security related fix. Searching for e.g. `servicegroup!=support` leads to an error
instead of the desired result when using a PostgreSQL database.

* Single negated membership filter fails with PostgreSQL [#4196](https://github.com/Icinga/icingaweb2/issues/4196)

### What's New in Version 2.8.1

You can find all issues related to this release on the respective [milestone](https://github.com/Icinga/icingaweb2/milestone/61?closed=1).

#### Case Sensitivity Problems

A fix in v2.8.0 led to users being not able to login if they got their username's case wrong. A hostgroup name's case
has also been incorrectly taken into account despite using a `CI` labelled column in the servicegrid and other lists.

* Login usernames now case sensitive in 2.8 [#4184](https://github.com/Icinga/icingaweb2/issues/4184)
* Case insensitive hostgroup filter in service grid not working [#4178](https://github.com/Icinga/icingaweb2/issues/4178)

#### Issues With Numbers

An attempt to avoid misrepresenting environments in the tactical overview had an opposite effect by showing negative
numbers. Filtering for timestamps in the event history also showed no results because our filters couldn't cope with
plain numbers anymore.

* Tactical overview showing "-1 pending" hosts [#4174](https://github.com/Icinga/icingaweb2/issues/4174)
* Timestamp filters not working correctly in history views [#4182](https://github.com/Icinga/icingaweb2/issues/4182)

### What's New in Version 2.8.0

You can find all issues related to this release on our [Roadmap](https://github.com/Icinga/icingaweb2/milestone/60?closed=1).

#### Icinga DB

It's happening. Yes. Our latest achievement is now available for those who are willing to participate in this enormous
endeavour. Icinga Web 2 is also a crucial part of it and accompanies the first release of Icinga DB. If you like
to participate, don't forget to update Icinga Web 2 as well.

#### Support for PHP 7.4 and MySQL 8

We also made sure that you won't be disappointed by Icinga Web 2 if you're running PHP 7.4 or trying to access a MySQL
database with version 8+. These should pose no issues anymore now. But if you still somehow managed to get issues
please let us now and we'll fix it asap.

* Exceptions with MySQL 8 [#3740](https://github.com/Icinga/icingaweb2/issues/3740)
* Support for PHP 7.4 [#4009](https://github.com/Icinga/icingaweb2/issues/4009)

#### Find What You Search For

It's been previously not possible to properly filter for range values. This was especially true for custom variables
where, if you searched for e.g. `_host_interfaces>=20`, you wouldn't find the correct results. If you often copy some
values in our search fields you may also been a victim of extraneous spaces which are now automatically trimmed.

* Filter: more/less than doesn't seem to working [#3974](https://github.com/Icinga/icingaweb2/issues/3974)
* Search object followed by a space finds no results [#4002](https://github.com/Icinga/icingaweb2/issues/4002)

#### Don't Leave Your Little Sheep Unattended

It's time again to further restrict your users. It's now possible to completely block any access to contacts and
contactgroups for specific roles. These won't ever see again who's notified and who's not. Also, if you are using
single accounts for a group of people you can now disable password changes for those.

* Prohibit access to contacts and contactgroups [#3973](https://github.com/Icinga/icingaweb2/issues/3973)
* Allow to forbid password changes on specific user accounts [#3286](https://github.com/Icinga/icingaweb2/issues/3286)

#### In and Out, Access Control Done Right

While we have no burgers (but cookies!) you are nevertheless welcome to visit Icinga Web 2. And now you can also
successfully leave while being externally authenticated and unsuccessfully enter while being unable to not add
extraneous spaces to your username.

* External logout not working from the navigation dashboard [#3995](https://github.com/Icinga/icingaweb2/issues/3995)
* Username with extraneous spaces are not invalid [#4030](https://github.com/Icinga/icingaweb2/pull/4030)

### Changes in Packaging and Dependencies

Valid for distributions:

* RHEL / CentOS 7
  * Upgrade to PHP 7.3 via RedHat SCL
  * See [Upgrading to Icinga Web 2 2.8.x](doc/80-Upgrading.md#upgrading-to-icinga-web-2-28x)
    for manual steps that are required

#### Discontinued Package Updates

Icinga Web 2 v2.8+ is not supported on these platforms:

* RHEL / CentOS 6
* Debian 8 Jessie
* Ubuntu 16.04 LTS (Xenial Xerus)

Please consider an upgrade of your central Icinga system to a newer distribution release.

[icinga.com](https://icinga.com/subscription/support-details/) provides an overview about
currently supported distributions.

### What's New in Version 2.7.3

This is a hotfix release and fixes the following issue:

* Servicegroups for roles with filtered objects not available [#3983](https://github.com/Icinga/icingaweb2/issues/3983)

### What's New in Version 2.7.2

You can find all issues related to this release on our [Roadmap](https://github.com/Icinga/icingaweb2/milestone/57?closed=1).

#### Less Smoky Database Servers

The release of v2.7.1 introduced a change which revealed an inefficient part of our database queries. We made some
general optimizations on our queries and changed the way we utilize them in some views. The result are faster
response times by less work for the database server.

* Consuming more CPU resources since upgraded to 2.7.1 [#3928](https://github.com/Icinga/icingaweb2/issues/3928)

#### Anarchism Infested Dashboards

Recent history already showed signs of anarchism. (Pun intended) A similar mindset now infested default dashboards
which appeared in a different way than before v2.7.0. We taught their dashlets a lesson and order has been reestablished
as previously.

* Recently Recovered Services in dashboard "Current Incidents" seems out of order [#3931](https://github.com/Icinga/icingaweb2/issues/3931)

#### Solitary Downtimes

We improved the host and service distinction with v2.7.0. The downtimes list however got confused by this and didn't
knew anymore how to combine multiple downtimes. If you now instruct the list to select multiple downtimes this works
again as we removed the confusing parts.

* Selection of multiple downtimes fails [#3920](https://github.com/Icinga/icingaweb2/issues/3920)

### What's New in Version 2.7.1

You can find all issues related to this release on our [Roadmap](https://github.com/Icinga/icingaweb2/milestone/56?closed=1).

#### Sneaky Solution for Sneaky Links

Usually we try to include only bugs in minor-releases. Sorry, bug-fixes, of course. But thanks to
[@winem_](https://twitter.com/winem_/status/1156531270521896960) we have also a little enhancement this time:
Links in comments, notes, etc. are now [highlighted](https://github.com/Icinga/icingaweb2/pull/3893) as such.

* Highlight links in the notes of an object [#3888](https://github.com/Icinga/icingaweb2/issues/3888)

#### Nobody's Perfect, Not Even Developers

We knew it. We saw it coming. And forgot about it. Some views, especially histories, showed an anarchic behavior
since v2.7.0. The change responsible for this has been undone and history's order is reestablished now.

* Default sort rules no longer work in 2.7.0 [#3891](https://github.com/Icinga/icingaweb2/issues/3891)

#### Restrictions Gone ~~Wild~~ Cagey

A [fix](https://github.com/Icinga/icingaweb2/pull/3868) unfortunately caused restrictions using wildcards to show no
results anymore. This is now solved and such restrictions are as permissive as ever.

* Wildcard filters in chains broken [#3886](https://github.com/Icinga/icingaweb2/issues/3886)

### What's New in Version 2.7.0

You can find issues related to this release on our [Roadmap](https://github.com/Icinga/icingaweb2/milestone/52?closed=1).

#### Icinga's Amazingness Spreads Further

All the Japanese and Ukrainian monitoring enthusiasts can now appreciate our web-frontend in their native tongue. Being
so late to the party is also of their advantage, though. Because they can adjust their dashboard without worrying it gets
broke with the next update. (All other admins with non-english users, please have a look at our
[upgrading documentation](doc/80-Upgrading.md#upgrading-to-icinga-web-2-27x-))

* Add Japanese language support [#3776](https://github.com/Icinga/icingaweb2/pull/3776)
* Add Ukrainian language support [#3828](https://github.com/Icinga/icingaweb2/pull/3828)
* Don't translate pane and dashlet names in configs [#3837](https://github.com/Icinga/icingaweb2/pull/3837)

#### Modules - Bonus Functionality Unleashed

With this release module developers got additional ways to customize Icinga Web 2. Whether you ever wanted to hook into
a configuration form's handling, to perform your very own Ajax requests or enhance our multi-select views with fancy
graphs. All is possible now.

* Allow to hook into a configuration form's handling [#3862](https://github.com/Icinga/icingaweb2/pull/3862)
* Allow to fully customize click and submit handling [#3794](https://github.com/Icinga/icingaweb2/issues/3767)
* Integrate DetailviewExtension into multi-select views [#3304](https://github.com/Icinga/icingaweb2/pull/3304)

#### UI - Your Daily Routine and Incident Management, Enhanced

Users with color deficiencies now have a built-in theme to ease navigating within Icinga Web 2. Also, our forms got
a long overdue re-design and now look less boring. Though, the best of all features is that clicking while holding
the Ctrl-key now actually opens a new browser tab! Lost comments? No more. Defining an expiry date again? No more!

* Add colorblind theme [#3743](https://github.com/Icinga/icingaweb2/pull/3743)
* Improve the look of forms [#3416](https://github.com/Icinga/icingaweb2/issues/3416)
* Make ctrl-click open new tab [#3723](https://github.com/Icinga/icingaweb2/pull/3723)

#### Stay Focused - More Room for More Important Stuff

Some of you know that some checks tend to produce walls of text or measure (too) many interfaces. Now, plugin output
and performance data will collapse if they exceed a certain height. If necessary they can of course be expanded and
keep that way across browser restarts. The same is also true for the sidebar. (Though, this one stays *collapsed*)

* Persistent Collapsible Containers [#3638](https://github.com/Icinga/icingaweb2/pull/3638)
* Collapsible plugin output [#3870](https://github.com/Icinga/icingaweb2/pull/3870)
* Collapsed sidebar should stay collapsed [#3682](https://github.com/Icinga/icingaweb2/issues/3628)

#### Markdown - Tables, Lists and Emphasized Text The Easy Way

Since we now have the possibility to collapse large content dynamically, we allow you to add entire wiki pages to hosts
and services. Though, if you prefer to use a real wiki to maintain those (what we'd strongly suggest) it's now easier
than ever before to link to it. Copy url, paste url, submit comment, Done.

* Make notes, comments and announcements markdown aware [#3814](https://github.com/Icinga/icingaweb2/pull/3814)
* Transform any URL in a Comment to a clickable Link [#3441](https://github.com/Icinga/icingaweb2/issues/3441)
* Support relative links in plugin output [#2916](https://github.com/Icinga/icingaweb2/issues/2916)

#### Things You Have Missed Previously

The tactical overview, our fancy pie charts, is now the very first result when you search something in the sidebar.
If you'll see two entirely green circles there, relax. Also overdue or unreachable checks are now appropriately marked
in list views and the service grid now allows you to switch between everything or problems only.

* Add tactical overview to global search [#3845](https://github.com/Icinga/icingaweb2/pull/3845)
* Servicegrid: Add toggle to show problems only [#3871](https://github.com/Icinga/icingaweb2/pull/3871)
* Make overdue/unreachable checks better visible [#3860](https://github.com/Icinga/icingaweb2/pull/3860)

#### Authorization - Knowing and Controlling What's Going On

Roles can now be even more tailored to users since the introduction of a new placeholder. This placeholder allows to
use a user's name in restrictions. Things like `_service_responsible_person=$user:local_name$` are now possible. The
audit log now receives failed login-attempts, that's been made possible since hooks can now run for anonymous users.

* Allow roles to filter for the currently logged in user [#3493](https://github.com/Icinga/icingaweb2/issues/3493)
* Add possibility to disable permission checks for hooks [#3849](https://github.com/Icinga/icingaweb2/pull/3849)
* Send failed login-attempts to the audit log [#3856](https://github.com/Icinga/icingaweb2/pull/3856)

See also the [audit module](https://github.com/Icinga/icingaweb2-module-audit/releases) which got an update and is
required for [#3856](https://github.com/Icinga/icingaweb2/pull/3856) to work.

### What's New in Version 2.6.3

You can find issues related to this release on our [Roadmap](https://github.com/Icinga/icingaweb2/milestone/54?closed=1).

#### PHP 7.3

Now supported. :tada:

#### LDAP - Community contributions, that's the spirit

With the help of our users we've finally fixed the issue that defining multiple hostnames and enabling STARTTLS has
never properly worked. Also, they've identified that defining multiple hostnames caused a customized port not being
utilized and fixed it themselves.

There has also a rare case been fixed that caused no group members being found in case object classes had a different
casing than what we expected. (Good news for all the non-OpenLdap and non-MSActiveDirectory users) 

* LDAP connection fails with multiple servers using STARTTLS [#3639](https://github.com/Icinga/icingaweb2/issues/3639)
* LDAPS authentication ignores custom port setting [#3713](https://github.com/Icinga/icingaweb2/issues/3713)
* LDAP group members not found [#3650](https://github.com/Icinga/icingaweb2/issues/3650)

#### We take care about your data even better now

With this are newlines and HTML entities (such as `&nbsp;`) in plugin output and custom variables meant.
Sorry if I've teased some data security folks now. :innocent:

* Newlines in plugin output disappear [#3662](https://github.com/Icinga/icingaweb2/issues/3662)
* Windows path separators are converted to newlines in custom variables [#3636](https://github.com/Icinga/icingaweb2/issues/3636)
* HTML entities in plugin output are not resolved if no other HTML is there [#3707](https://github.com/Icinga/icingaweb2/issues/3707)

#### You've wondered how you got into a famous blue police box?

Don't worry, not only you and the european union are sometimes unsure what's the correct time.

* Set client timezone on DB connection [#3525](https://github.com/Icinga/icingaweb2/issues/3525)
* Ensure a valid default timezone is set in any case [#3747](https://github.com/Icinga/icingaweb2/pull/3747)
* Fix that the event detail view is not showing times in correct timezone [#3660](https://github.com/Icinga/icingaweb2/pull/3660)

#### UI - The portal to your monitoring environment, improved

The collapsible sidebar introduced with v2.5 has been plagued by some issues since then. They're now fixed. Also,
the UI should now flicker less and properly preserve the scroll position when interacting with action links. (This
also allows the business process module to behave more stable when using drag and drop in large configurations.)

* Collapsible Sidebar Issues [#3187](https://github.com/Icinga/icingaweb2/issues/3187)
* Fix title when closing right column [#3654](https://github.com/Icinga/icingaweb2/issues/3654)
* Preserve scroll position upon form submits [#3661](https://github.com/Icinga/icingaweb2/pull/3661)

#### Corrected things we've broke recently

That's due to preemptive changes to protect you from bad individuals. Unfortunately this meant that some unforeseen
side-effects appeared after the release of v2.6.2. These are now fixed.

* Multiline values in ini files broken [#3705](https://github.com/Icinga/icingaweb2/issues/3705)
* PHP ini parser doesn't strip trailing whitespace [#3733](https://github.com/Icinga/icingaweb2/issues/3733)
* Escaped characters in INI values are not unescaped [#3648](https://github.com/Icinga/icingaweb2/issues/3648)

Though, if you've faced issue [#3705](https://github.com/Icinga/icingaweb2/issues/3705) you still need to take manual
action (if not already done) as the provided fix does only prevent further occurrences of the resulting error. The
required changes involve the transformation of all real newlines in Icinga Web 2's INI files to literal `\n` or `\r\n`
sequences. (Files likely having such are the `roles.ini` and `announcements.ini`)

### What's New in Version 2.6.2

You can find issues and features related to this release on our [Roadmap](https://github.com/Icinga/icingaweb2/milestone/53?closed=1).

This bugfix release addresses the following topics:

* Database connections to MySQL 8 no longer fail
* LDAP connections now have a timeout configuration which defaults to 5 seconds
* User groups are now correctly loaded for externally authenticated users
* Filters are respected for all links in the host and service group overviews
* Fixed permission problems where host and service actions provided by modules were missing
* Fixed an SQL error in the contact list view when filtering for host groups
* Fixed time zone (DST) detection
* Fixed the contact details view if restrictions are active
* Doc parser and documentation fixes

### What's New in Version 2.6.1

You can find issues and features related to this release on our [Roadmap](https://github.com/Icinga/icingaweb2/milestone/51?closed=1).

The command audit now logs a command's payload as JSON which fixes a
[bug](https://github.com/Icinga/icingaweb2/issues/3535) that has been introduced in version 2.6.0.

### What's New in Version 2.6.0

You can find issues and features related to this release on our [Roadmap](https://github.com/Icinga/icingaweb2/milestone/48?closed=1).

#### Enabling you to do stuff you couldn't before

* Support for PHP 7.2 added
* Support for SQLite resources added
* Login and Command (monitoring) auditing added with the help of a dedicated [module](https://github.com/Icinga/icingaweb2-module-audit)
* Pluginoutput rendering is now hookable by modules which allows to render custom icons, emojis and .. cute kitties :octocat:

#### Avoiding that you miss something

* It's now possible to toggle between list- and grid-mode for the host- and servicegroup overviews
* The servicegrid now supports to flip its axes which allows it to be put into a [landscape mode](https://github.com/Icinga/icingaweb2/pull/3449#issue-185415579)
* Contacts only associated with services are visible now when restricted based on host filters
* Negated and combined membership filters now work as expected ([#2934](https://github.com/Icinga/icingaweb2/issues/2934))
* A more prominent error message in case the monitoring backend goes down
* The filter editor doesn't get cleared anymore upon hitting Enter

#### Making your life a bit easier

* The tactical overview is now filterable and can be safely put into [the dashboard](https://github.com/Icinga/icingaweb2/pull/3446#issue-185379142)
* It is now possible to register new announcements over the [REST Api](https://github.com/Icinga/icingaweb2/issues/2749#issuecomment-279667189)
* Filtering for custom variables now works in UTF8 environments

#### Ensuring you understand everything

* The monitoring health is now beautiful to look at and properly behaves in [narrow environments](https://github.com/Icinga/icingaweb2/pull/3515#issue-200075373)
* Updated German localization
* Updated Italian localization

#### Freeing you from unrealiable things

* Removed support for PHP < 5.6
* Removed support for persistent database connections

### What's New in Version 2.5.3

You can find issues and features related to this release on our [Roadmap](https://github.com/Icinga/icingaweb2/milestone/50?closed=1).

#### Fixes

A fix for an issue introduced with v2.5.2 that prevented service-only contacts from appearing in the UI resulted in long
database response times and has been reverted.

### What's New in Version 2.5.2

You can find issues and features related to this release on our [Roadmap](https://github.com/Icinga/icingaweb2/milestone/49?closed=1).

#### UI Changes

The sidebar's search behaviour has been changed so that it does only react to user-input after the user stopped typing.
Also, the cursor does not jump to the end of form-inputs anymore in case of an auto-refresh. We've also fixed an issue
that caused [custom icons](https://github.com/Icinga/icingaweb2/issues/3181#issuecomment-378875462) to be inverted when
placed in the sidebar. Last but not least, the header now expands its width beyond the 3840px mark and single dashlets
do not show a horizontal scrollbar anymore.

#### PHP7 MSSQL Compatibility

Support for Microsoft's `sqlsrv` extension has been added. Also, it's now possible to setup MSSQL resources in the
front-end using the `dblib` extension.

#### Proper Error Responses

An issue introduced with v2.5.1 has been resolved where some errors (especially HTTP 404 Not Found) were masked
by another subsequent error.

#### Broken LDAP Group Memberships

An issue introduced with v2.5.1 has been resolved where users with a domain in their name were not associated with any
LDAP groups.

#### Monitoring Module

Issuing a check using the "Check Now" action now properly causes a check being made by Icinga 2 even if outside the
timeperiod. (Note: This issue was only present if using the Icinga 2 Api as command transport.)

#### Login/Logout Expandability

It's now possible for modules to provide hooks for the user authorization. This for example allows to transparently
authenticate users in third-party applications such as [Grafana](https://github.com/Icinga/icingaweb2/pull/3401#issue-178030542).

### What's New in Version 2.5.1

You can find issues and features related to this release on our [Roadmap](https://github.com/Icinga/icingaweb2/milestone/47?closed=1).

Besides many other bug fixes, Icinga Web 2 v2.5.1 fixes an issue where it was no longer possible to filter by host
custom variables in service related views. Also, this release introduces detail views for the event history and
improved upgrading docs. Furthermore, this version censors sensitive information (e.g. LDAP passwords) in exception
stack traces.

### What's New in Version 2.5.0

You can find issues and features related to this release on our [Roadmap](https://github.com/Icinga/icingaweb2/milestone/45?closed=1).

#### Raised PHP Version Dependency

Icinga Web 2 now requires at least PHP 5.6.

#### UI Changes

The style of the login screen and menu have been changed. Also, the menu of Icinga Web 2 is now collapsible.
Browser tabs will not auto-refresh if they are inactive. Users are now allowed to change the default pagination limit
via their preferences.

#### Domain-aware Authentication for Active Directory and LDAP Backends

If there are multiple AD/LDAP authentication backends with distinct domains, you are now able to make Icinga Web 2
aware of the domains. This can be done by configuring each AD/LDAP backend's domain. You can also use the GUI for this
purpose. Please read our [documentation](doc/05-Authentication.md#domain-aware-auth) for more information about this
feature.

#### Changes in Packaging and Dependencies

Valid for distributions:

* RHEL / CentOS 6 + 7
  * Upgrading to PHP 7.0 / 7.1 via RedHat SCL (new dependency)
  * See [Upgrading to FPM](doc/02-Installation.md#upgrading-to-fpm) for manual steps that are required
* SUSE SLE 12
  * Upgrading PHP to >= 5.6.0 via the alternative packages.
    You might have to confirm the replacement of PHP < 5.6 - but that should work with any other PHP app as well
  * Make sure to enable the new Apache module `a2enmod php7` and restart `apache2`

#### Discontinued Package Updates

For the following distributions Icinga Web 2 won't be updated past 2.4.x anymore:

* Debian 7 wheezy
* Ubuntu 14.04 LTS (trusty)
* SUSE SLE 11 (all service packs)

Please think about replacing your central Icinga system to a newer distribution release.

Also see [packages.icinga.com](https://packages.icinga.com) for the currently supported distributions.

### What's New in Version 2.4.2

#### Bugfixes

* Bug 2965: Transport config: Default port not changing upon auto-submit
* Bug 2926: Wrong order when sorting by host_severity
* Bug 2923: Number fields should be valid when empty
* Bug 2919: Fix cached loading of module config
* Bug 2911: Acknowledgements are not working without an expiry time
* Bug 2878: process-check-result Button is visible even when user isn't allowed to use it
* Bug 2850: Link to acknowledgements is wrong in the timeline
* Bug 2841: Wrong menu height when switching back from mobile layout
* Bug 2806: Wrong service state count in hostgroup overview
* Bug 2805: Response from the Icinga 2 API w/ an empty result set leads to exception
* Bug 2801: Wrong help text for the director in the icingacli
* Bug 2784: Module and gravatar images are not served with their proper MIME type
* Bug 2776: Defaults not respected when acknowledging problems
* Bug 2767: Monitoring module: Config field protected vars not updated after zeroing config.ini
* Bug 2728: Gracefully handle invalid Icinga 2 API response types
* Bug 2718: Hide check attempt for hard states in history views
* Bug 2716: Web 2 doesn't detect the browser time zone if the time zone offset is negative
* Bug 2714: icingacli module disable fails on consecutive calls
* Bug 2695: Macros cannot be used for a navigation item's url-port
* Bug 2684: [dev.icinga.com #14027] Translation module should not write absolute path to .po files
* Bug 2683: [dev.icinga.com #14025] Translation module should remove temp files
* Bug 2661: [dev.icinga.com #13651] Don't offer the Icinga 2 API as transport if PHP cURL is missing
* Bug 2660: [dev.icinga.com #13649] Make the Icinga 2 API the default command transport
* Bug 2656: [dev.icinga.com #13627] Wrong count of handled critical service in the hover text
* Bug 2645: [dev.icinga.com #13539] Improve error handling and validation of multiple LDAP URIs
* Bug 2598: [dev.icinga.com #12977] Adding an empty user backend fails
* Bug 2545: [dev.icinga.com #12640] MSSQL ressource not working
* Bug 2523: [dev.icinga.com #12410] Click on Host in Service Grid can cause "Invalid Filter" error
* Bug 2519: [dev.icinga.com #12330] Filter editor may show wrong values after searching
* Bug 2509: [dev.icinga.com #12295] group_name_attribute should be "sAMAccountName" by default

### What's New in Version 2.4.1

Our public repositories and issue tracker have been migrated to GitHub.

#### Bugfixes

* Bug 2651: [dev.icinga.com #13607] Displayed times messed up in Icinga Web 2.4.0 w/ PostgreSQL
* Bug 2654: [dev.icinga.com #13615] Setup wizard: Not possible to setup Icinga Web 2 with an external database
* Bug 2663: [dev.icinga.com #13691] Hook::all() is broken on CLI
* Bug 2669: [dev.icinga.com #13735] Setup wizard: Progress bar isn't shown correctly, if setup is at finish step
* Bug 2681: [dev.icinga.com #13957] Support failover API command transport configuration
* Bug 2686: Granular module permissions do not work for hooks
* Bug 2687: Update URLs to icinga.com, remove wiki & update to GitHub

### What's New in Version 2.4.0

#### Feature

* Feature 12598 (Authentication & Authorization): Support nested AD groups for Roles and not just login
* Feature 11809 (Authentication & Authorization): Test and document multiple LDAP-URIs separated by space in LDAP ressources
* Feature 10616 (Authentication & Authorization): Users w/o administrative permissions should be allowed to change their password
* Feature 13381 (CLI): Allow to configure the default listen address for the CLI command web serve
* Feature 11820 (Configuration): Check whether chosen locale is available
* Feature 11214 (Configuration): Logger: Allow to configure the Syslog Facility
* Feature 13117 (Framework): Add charset UTF-8 to default content type
* Feature 12634 (Framework): Possibitlity to fold and unfold filter by click
* Feature 11198 (Framework): Announce banner
* Feature 11115 (Framework): Add SSL support to MySQL database resources
* Feature 8270 (Installation): Add SELinux policy for Icinga Web 2
* Feature 13187 (Monitoring): Command toolbar in the host and service detail views
* Feature 12873 (Monitoring): Change default for sticky option of acknowledgements from true to false
* Feature 12820 (Monitoring): Export detail views to JSON
* Feature 12766 (Monitoring): Show flapping events in the host and service history views
* Feature 12764 (Monitoring): Display downtime end even if it hasn't been started yet
* Feature 12125 (Monitoring): Allow th in plugin output
* Feature 11952 (Monitoring): Allow changing default of 'sticky' in acknowledgement and other command options
* Feature 11398 (Monitoring): Send commands over Icinga 2's API
* Feature 11835 (UI): Add clear button to search field
* Feature 11792 (UI): Show hint if notifications are disabled globally
* Feature 11664 (UI): Show git HEAD for modules if available
* Feature 13461 (Vendor Libraries): Use Icinga's fork of Zend Framework 1 icingaweb2-vendor-zf1

#### Bugfixes

* Bug 12396 (Authentication & Authorization): Hooks don't respect module permissions
* Bug 12164 (Authentication & Authorization): REDIRECT_REMOTE_USER not evaluated during external auth
* Bug 12108 (Authentication & Authorization): assertPermission allows everything for unauthenticated requests
* Bug 13357 (Configuration): Persistent database resources cannot be made non-persistent
* Bug 12848 (Configuration): Empty "Protected Custom Variables" falls back to defaults
* Bug 12655 (Configuration): Permission application/log is not configurable
* Bug 12170 (Configuration): Adding a DB resource via webinterface requires one to enter a password
* Bug 10401 (Configuration): LdapUserGroupBackendForm: user_* settings not purged
* Bug 9804 (Configuration): Renaming the resource used for the config backend does not update the global configuration
* Bug 11920 (Dashboard): Add to dashboard: wrong url makes whole dashboard unusable
* Bug 13387 (Documentation): Can't display documentation of disabled modules
* Bug 12923 (Framework): Navigation Item name must be of type string or NavigationItem
* Bug 12852 (Framework): Hosts without any services are hidden from roles with monitoring/filter/objects set
* Bug 12760 (Framework): Do not log exceptions other than those resulting in a HTTP 500 status-code
* Bug 12583 (Framework): Unhandled exceptions while handling REST requests will silently drop the http response code
* Bug 12580 (Framework): REST requests cannot be anonymous
* Bug 12557 (Framework): Module description cannot be on a single line
* Bug 12299 (Framework): FilterExpression renders a&!b as a=1&b!=1
* Bug 12161 (Framework): Icinga Web 2 doesn't set Content-Type
* Bug 12065 (Framework): IniRepository: update/delete not possible with iterator
* Bug 11743 (Framework): INI writer must not persist section keys with a null value
* Bug 11185 (Framework): SummaryNavigationItemRenderer should show worst state
* Bug 10361 (Framework): Handle E_RECOVERABLE_ERROR
* Bug 13459 (Installation): Setup: Can't view monitoring config summary with Icinga 2 API as command transport
* Bug 13467 (JavaScript): renderLayout has  side-effects
* Bug 13115 (JavaScript): actiontable should not clear active row in case there is no newer one
* Bug 12541 (JavaScript): Menu not reloaded in case no search is available
* Bug 12328 (JavaScript): Separate vendor JavaScript libraries w/ semicolons and newlines on import
* Bug 10704 (JavaScript): JS: Always use the jQuery find method w/ node context when selecting elements
* Bug 10703 (JavaScript): JS: Don't use var self = this, but var _this = this
* Bug 11431 (Modules): Modules can't require permission on menu items
* Bug 10870 (Modules): Refuse erroneous module folder names when enabling the module
* Bug 13243 (Monitoring): Inconsistent host and service flags
* Bug 12889 (Monitoring): Timeline broken
* Bug 12810 (Monitoring): Scheduling a downtime for all services of a host does not work w/ the Icinga 2 API as command transport
* Bug 12313 (Monitoring): Multi-line strings within host.notes are being displayed as single line
* Bug 12223 (Monitoring): State not highlighted in plugin output if it contains HTML
* Bug 12019 (Monitoring): Contact view shows service filters with 'Downtime' even if not set
* Bug 11915 (Monitoring): Performance data: negative values not handled
* Bug 11859 (Monitoring): Can't separate between SOFT and HARD states in the history views
* Bug 11766 (Monitoring): Performance data: Fit label column to show as much text as possible
* Bug 11744 (Monitoring): Empty user groups are not displayed
* Bug 10774 (Monitoring): Scheduling downtimes for child hosts doesn't work w/ Icinga 2.x (waiting for Icinga 2)
* Bug 10537 (Monitoring): Filtering with not-equal on custom variable doesn't show hosts without this cv
* Bug 7755 (Monitoring): Remove autosubmit in eventgrid
* Bug 12133 (Navigation): Username and password not being passed in navigation item URLs
* Bug 12776 (Print & Export): dompdf fails when border-style is set to auto
* Bug 12723 (Print & Export): Allowed memory size exhausted when exporting the history view to CSV
* Bug 12660 (QA): Choosing the Icinga theme floods the log with error messages
* Bug 12774 (UI): Lot's of <span style="visibility:hidden; display:none;"></span> in Output
* Bug 12134 (UI): Copy and paste: Plugin output contains unicode zero-width space characters
* Bug 10691 (UI): Closing the detail area does not update the rows selected counter
* Bug 13095 (Vagrant VM): TicketSalt constant missing
* Bug 12717 (Vagrant VM): PluginContribDir constant removed during vagrant provisioning

### What's New in Version 2.3.4/2.3.3

#### Bugfixes

* Bug 11267: Links in plugin output don't behave as expected
* Bug 11348: Host aliases are not shown in detail area
* Bug 11728: First non whitespace character after comma stripped from plugin output
* Bug 11729: Sort by severity depends on state type
* Bug 11737: Zero width space characters destroy state highlighting in plugin output
* Bug 11796: Zero width space characters may destroy links in plugin output
* Bug 11831: module.info parsing fails in case it contains newlines that are not part of the module's description
* Bug 11850: "Add to menu" tab unnecessarily appears in command forms
* Bug 11871: Colors used in the timeline are not accessible
* Bug 11883: Delete action on comments and downtimes in list views not accessible because they lack context
* Bug 11885: Database: Asterisk filters ignored when combined w/ other filters
* Bug 11910: Web 2 lacks mobile meta tags
* Fix remote code execution via remote command transport

### What's New in Version 2.3.2

#### Feature

* Feature 11629: Simplified event-history date and time representation

#### Bugfixes

* Fix a privilege escalation issue in the monitoring module for authenticated users
* Bug 10486: Menu rendering fails when no monitoring backend was configured
* Bug 10847: Warn about illogical dates
* Bug 10848: Can't change items per page if filter is in modify state
* Bug 11392: Can't configure monitoring backend via the web interface when no monitoring backend was configured

### What's New in Version 2.3.1

#### Bugfixes

* Bug 11598: Invalid SQL queries for PostgreSQL

### What's New in Version 2.3.0

#### Features

* Feature 10887: lib: Provide User::getRoles()
* Feature 10965: Roles: Restrict visibility of custom variables
* Feature 11404: Add is_reachable filter column to host and service data views
* Feature 11485: lib/LDAP: Support scopes base and one
* Feature 11495: Support data URIs in href
* Feature 11529: Don't offer command disable notifications /w expire time if backend is Icinga 2

#### Bugfixes

* Bug 9386: Improve order of documentation chapters
* Bug 10820: Style problems with long plugin output lines
* Bug 11078: Can't remove default dashboards
* Bug 11099: Mobile menu icon is mispositioned
* Bug 11128: Menu stops refreshing when there is text in the search field
* Bug 11145: Pagination compontents should not float around
* Bug 11171: Icinga Web 2 tries to load an ifont which results in 404
* Bug 11245: icingacli monitoring list --problems throws an exception
* Bug 11264: Cannot execute queries while other unbuffered queries are active
* Bug 11277: external auth with PHP internal webserver still buggy
* Bug 11279: Restrict access to Applicationlog
* Bug 11299: Icon images no longer prepend img/icons
* Bug 11391: External auth reads REMOTE_USER from process environment instead of request
* Bug 11414: Doc module does not render images with relative path
* Bug 11465: Stylesheet remains unchanged when module CSS/LESS files have been changed
* Bug 11489: lib/LDAP: ordering does explicitly set fields
* Bug 11490: lib/LDAP: LdapUtils::explodeDN replace deprecated use of eval in preg_replace
* Bug 11516: Accessibility: Focus in Tactical Overview barely visible
* Bug 11558: Missing ) in the documentation
* Bug 11568: Docs: Global permissions table is broken

### What's New in Version 2.2.0

#### Features

* Feature 8487: Number headings in the documentation module
* Feature 8963: Feature commands in the multi select views
* Feature 10654: Render links in acknowledgements, comments and downtimes
* Feature 11062: Allow style classes in plugin output
* Feature 11238: Puppet/Vagrant: Install mod_ssl and forward port 443

#### Bugfixes

* Bug 7350: Tabs are missing if JS is disabled
* Bug 9800: Debian packaging: Ship translation module w/ the icingaweb2 package and install its config.ini
* Bug 10173: Failed commands give no useful error any more
* Bug 10251: Icinga Web 2 fails to run with PHP7
* Bug 10277: Special characters are incorrectly escaped for tooltips in the service grid
* Bug 10289: Doc module: Headers are cut off when clicking on TOC links
* Bug 10309: Move auth backend configuration to app config
* Bug 10310: Monitoring details: information/action ordering
* Bug 10362: Debian packaging: Separate package for CLI missing
* Bug 10366: Text plugin output treated as HTML in too many occasions
* Bug 10369: Accessibility: Focus not visible and lost after refresh
* Bug 10397: Users with no permissions can check multiple services
* Bug 10442: Edit user control should be more prominent
* Bug 10469: "Remove Acknowledgement" text missing in multi-select views
* Bug 10506: HTTP basic auth request is sent when using Kerberos authentication with Apache2 and mod_php
* Bug 10625: Return local date and time when lost connection to the web server
* Bug 10640: Respect protected_variables in nested custom variables too
* Bug 10778: Filters in the host group and service group overview not applied to state links
* Bug 10786: Whitespace characters are ignored in the plugin output in list views
* Bug 10805: Setup Wizard: Obsolete PHP sockets requirement
* Bug 10856: Benchmark is not rendered on many pages
* Bug 10871: Get rid of padding in controls
* Bug 10878: Dashboards different depending on username casing
* Bug 10881: Move iframe from modules to framework
* Bug 10917: Event grid tiles: The filter column "from" is not allowed here
* Bug 10918: Error on logout when using external authentication
* Bug 10921: icingacli monitoring list --format=csv throws error
* Bug 11000: Change license header to only reflect a file's year of creation/initial commit
* Bug 11008: Wobbling spinners
* Bug 11021: Global default theme is not applied while not authenticated
* Bug 11032: Fix icon_image size and provide a CSS class for theming
* Bug 11039: Misleading tooltip in Tactical Overview
* Bug 11051: Preferences and navigation items stored in INI files rely on case sensitive usernames
* Bug 11073: Active row is flickering on refresh
* Bug 11091: Custom navigation items: URL is not escaped/encoded
* Bug 11100: Comments are always persistent
* Bug 11114: Validate that a proper root DN is set for LDAP resources
* Bug 11117: Vendor: Update dompdf to version 0.6.2
* Bug 11119: icingacli shows ugly exception when unable to access the config directory
* Bug 11120: icingacli: command and action shortcuts have been broken
* Bug 11126: Invalid cookie value in cookie icingaweb2-tzo
* Bug 11142: LDAP User Groups backend group_filter
* Bug 11143: Layout: Tabs should be left-aligned
* Bug 11151: Having basic authentication on the webserver but not in Icinga Web 2 causes Web 2 to require basic auth
* Bug 11168: Debian packaging: Don't patch HTMLPurifier loading and install HTMLPurifier*.php files from the library/vendor root
* Bug 11187: Session cookie: Path too broad and unset secure flag on HTTPS
* Bug 11197: Menu items without url should ignore the target configuration
* Bug 11260: Scheduling downtimes through the API not working

### What's New in Version 2.1.1

#### Features

* Feature 10488: Use _ENV variables with built-in PHP webserver
* Feature 10705: Theming
* Feature 10898: Winter theme

#### Bugfixes

* Bug 9685: Deprecate Module::registerHook() in favor of Hook::provideHook()
* Bug 9957: Sort hosts and services by last state change
* Bug 10123: CSS loading may fail w/ mkdir(): File exists in FileCache.php
* Bug 10126: setup config directory --config should use mkdir -p instead of mkdir()
* Bug 10166: library/vendor/HTMLPurifier tree is incorrectly unpacked
* Bug 10170: Link to service downtimes from multiple selected services includes host downtimes aswell
* Bug 10338: Debian: Failed to open stream HTMLPurifier/HTMLPurifier.php
* Bug 10603: Line breaks are not respected in acknowledgements, comments and downtimes
* Bug 10658: SUSE packages have the wrong dependencies
* Bug 10659: LDAP group members are shown with their DN and membership registration does not work
* Bug 10670: State not highlighted in plugin output
* Bug 10671: Auto-focus the username field on the login page
* Bug 10683: lib/CLI command web serve: rename variable basedir to something meaningful
* Bug 10702: Host- and Service-Actions configured in Web 2 do not resolve any macros
* Bug 10749: XHR application-state requests pollute the URL if not authenticated
* Bug 10771: Login shows "Anmelden........" upon login with the german locale
* Bug 10781: LoggingConfigForm.php complains about whitespace but checks with /^[^\W]+$/
* Bug 10790: "Problems - Service Grid" does not work with host names that contain only digits
* Bug 10884: Tabs MUST throw an exception when activating an inexistant tab
* Bug 10886: "impacted" container is no longer fading out
* Bug 10892: Wrong mask for FileCache's temp directory

### What's New in Version 2.1.0

#### Features

* Feature 10613: Extend and simplify Hook api

#### Bugfixes

* Bug 8713: Invalid filter "host_name=*(test)*", unexpected ) at pos 17
* Bug 8999: Navigation and search bar is not available using a small width
* Bug 10229: Dashboard requests do not refresh the session
* Bug 10268: Unhandled services in the hosts overview list don't stand out
* Bug 10287: Redirect after login no longer working
* Bug 10288: The order for the limit links is incorrect
* Bug 10292: Hovered links in hover menu are unreadable
* Bug 10293: Hover menu is missing it's arrow for menu entries providing badges
* Bug 10295: Reset static line-height on body
* Bug 10296: Scrolling to the bottom of the page does not load more events
* Bug 10299: Badges are overridden by menu text
* Bug 10301: Format helpers like timeSince are polluted with text-small
* Bug 10303: Zooming in, or having another layout destroys the hover menu
* Bug 10304: Cannot access a host's customvars for service actions
* Bug 10305: Hover menu arrow color no longer fits background color
* Bug 10316: Not all Servicegroups / Hostgroups are shown
* Bug 10317: Event history style broken
* Bug 10319: Recursive sharing navigation items doesn't work.
* Bug 10321: Module iframe doesn't show website with parameters as a single column
* Bug 10328: ZendFramework packages missing for SLES12
* Bug 10359: Charset option not passed thru PDO adapter
* Bug 10364: PostgreSQL queries apply LOWER() on selected columns
* Bug 10367: Broken user- and group-management
* Bug 10389: Host overview: vsprintf(): Too few arguments
* Bug 10402: LdapUserGroupBackend: user_base_dn not used from UserBackend
* Bug 10419: Swapped icon image order in service header
* Bug 10490: Unhandled service counter in the hosts overview shows incorrect values
* Bug 10533: Form notifications of type information are green
* Bug 10567: Member user name used for basedn when querying usergroup members
* Bug 10597: Empty PDO charset option is invalid
* Bug 10614: Class loader: hardcode module and Zend prefixes
* Bug 10623: Acknowledging multiple selected objects erroneous

### What's New in Version 2.0.0

#### Changes


Upgrading to Icinga Web 2 2.0.0

Icinga Web 2 installations from package on RHEL/CentOS 7 now depend on php-ZendFramework which is available through the EPEL repository. Before, Zend was installed as Icinga Web 2 vendor library through the package icingaweb2-vendor-zend. After upgrading, please make sure to remove the package icingaweb2-vendor-zend.

Icinga Web 2 version 2.0.0 requires permissions for accessing modules. Those permissions are automatically generated for each installed module in the format module/<moduleName>. Administrators have to grant the module permissions to users and/or user groups in the roles configuration for permitting access to specific modules. In addition, restrictions provided by modules are now configurable for each installed module too. Before, a module had to be enabled before having the possibility to configure restrictions.

The instances.ini configuration file provided by the monitoring module has been renamed to commandtransports.ini. The content and location of the file remains unchanged.

The location of a user's preferences has been changed from config-dir/preferences/username.ini to config-dir/preferences/username/config.ini. The content of the file remains unchanged.

#### Features

* Feature 5600: User specific menu entries
* Feature 5647: GUI for permission and restriction assignment
* Feature 5786: Namespace all web controllers
* Feature 6144: Provide additional dashboard panes per default
* Feature 6677: Allow to extend the content of a dashlet on the right
* Feature 7180: Show active cluster hostname in the monitoring health view
* Feature 7367: GUI for adding action and notes URLs
* Feature 7570: Document installation
* Feature 7773: Interpret links in custom variables
* Feature 8336: IDO: Double check that we always add the is_active = 1 condition in our queries
* Feature 8369: Show an indicator when automatic form submission is ongoing
* Feature 8378: Indicate when check results are being late
* Feature 8407: Document example commands for installing from source
* Feature 8642: Show acknowledgement expire time (if any) in the host and service detail view
* Feature 8645: Generic iFrame module
* Feature 8758: Add support for file uploads
* Feature 8848: Show activity indicator for dashlets
* Feature 8884: Move the menu entry for notifications beneath history
* Feature 8981: Combo backend for command transports (fallback mechanism)
* Feature 8985: Visually separate enabled and disabled modules in the modules view
* Feature 9029: Provide a complete list of available filter columns plus custom variables (where appropriate) in the filter editor
* Feature 9030: Service grid: Add limit control
* Feature 9247: Show Icinga Web 2's version in the frontend
* Feature 9364: Apply sort rules for ldap queries on the server's side
* Feature 9381: List installed modules, versions and state in the about page
* Feature 9453: Vagrant: Upgrade to CentOS 7
* Feature 9460: IDO resource configuration: Ensure that the user is running PostgreSQL 9.1+
* Feature 9524: Improve setup wizard
* Feature 9525: Configuration enhancements
* Feature 9591: IP Address Search
* Feature 9604: Add Inspection API for Connections
* Feature 9605: LDAP Connection add Test Function
* Feature 9630: Inspectable: Add inspectable API to LDAP connections
* Feature 9641: Add Inspection API for DB Connections
* Feature 9644: Permit access to modules
* Feature 9645: Support for address6
* Feature 9651: Automatically use the correct instance configuration based on a host's or service's instance
* Feature 9660: Basic access authentication
* Feature 9661: Query for limit+1 for "Show more results" candidates
* Feature 9683: Allow to create MSSQL and Oracle DB resources
* Feature 9702: Allow module developers to define additional static files
* Feature 9761: Store active menu item as HTML5 history state information
* Feature 9772: Allow to list groups from a LDAP backend
* Feature 9826: Allow to select text in the host and service detail area header via double click
* Feature 9830: Monitoring: Support the wildcard restriction for "administrative" roles
* Feature 9888: Display a host's and service's check timeperiod as well as notification timeperiod in the detail view
* Feature 9908: Use better icons for resources, backends and module state
* Feature 9942: Add a warning to the navigition if the last IDO update is older than 5 minutes
* Feature 9943: Offer instance_name as query column
* Feature 9945: Show instance_name in a host's and service's detail view
* Feature 10033: Provide "Counter"-View

#### Bugfixes

* Bug 6644: Default sort order is not applied
* Bug 7383: This webpage has a redirect loop without cookies
* Bug 7486: Instance Configuration: Instance must NOT be a GET parameter when creating an instance
* Bug 7488: Instance Configuration: Instance parameter must be mandatory for updating and removing instances
* Bug 7489: Instance Configuration: Custom validation errors must be shown in the form not as notification
* Bug 7490: Instance Configuration: HTTP response code flaws
* Bug 7818: Incorrect language & timezone detection w/ Safari
* Bug 7930: Hide external commands which are not supported by Icinga 2
* Bug 8312: Don't show last and next check information and schedule check controls for passive only checks
* Bug 8620: Searching in the downtimes list view throws an exception
* Bug 8623: Selected row lost after auto-refresh in every overview except for hosts and services
* Bug 8703: Do not show computer accounts for Active Directory
* Bug 8768: Range multiselection not working in IE11
* Bug 8845: Missing downtime end information in host and service detail views
* Bug 8954: Document and rename Ldap\Connection to Ldap\LdapConnection
* Bug 8955: Document and rename Ldap\Query to Ldap\LdapQuery
* Bug 8969: Tooltips hidden after auto refresh
* Bug 8975: Error messages disappear after auto refresh #2
* Bug 8983: Remove yellow boxes from forms and wherever else used
* Bug 9024: Form autosubmits cause autorefreshs to not run anymore
* Bug 9036: Plugin output HTML tags are always escaped
* Bug 9042: Browser address bar gets not updated when closing the detail area while a request for the url that has just been closed is pending
* Bug 9054: Multiselection not visible until a subsequent auto-refresh has been completed
* Bug 9168: Can't use Icinga Web 2 w/ IDO version 1.7
* Bug 9179: LDAP discovery relies on anonymous access and does not respect encryption
* Bug 9266: Downtimes show "Starts in" for objects with non-problem state
* Bug 9306: Installation Wizard complains about "required and must not be empty"-fields when the user changes the database type first
* Bug 9314: RPM packages do not require Zend PDO packages which results in missing 'php-pdo' exception
* Bug 9330: Uncaught TypeError: Cannot read property 'id' of undefined when deleting comments or downtimes via their respective overview
* Bug 9333: Sorting the service grid by service description fails w/ PostgreSQL
* Bug 9346: Potential active rows not deselected when navigating by browser history
* Bug 9347: Service names with round bracket fail w/ innvalid filter exception when selecting multiple services
* Bug 9348: LDAP filter input errors w/ "The filter must not be wrapped in parantheses"
* Bug 9349: Duplicate headers from Controller::postDispatch()
* Bug 9360: service matrix does not show all intersections
* Bug 9374: Non-existent modules can be disabled
* Bug 9375: Fatal error in icingacli (icingacli-2.0.0-3.beta3.el7.centos.noarch)
* Bug 9376: INI writer must not persist section keys with a null value
* Bug 9398: Rename menu "authentication" to "security"
* Bug 9402: A command form's view script cannot be found if benchmark is enabled
* Bug 9418: DB resources: Do not allow to configure table prefixes
* Bug 9421: Sort controls misbehavior
* Bug 9449: The use statement with non-compound name ... has no effect w/ PHP 5.6.9+
* Bug 9454: Ghost host- and servicegroups
* Bug 9472: Fetch object statistics only if they're actually displayed
* Bug 9473: Inconsistent counters for service problems
* Bug 9477: Command forms have no tabs
* Bug 9483: Icinga\Web\Widget\Paginator should not require a full query interface
* Bug 9484: Document that the web server has to be restarted after adding the web server user to the icingaweb2 system group
* Bug 9494: Refresh button loads invalid links for views with complex filters
* Bug 9497: Eventhistory: Quick search not working
* Bug 9498: Service overview: Cannot quick search for hosts
* Bug 9499: Hostgroup overview: Cannot quick search for hosts
* Bug 9500: Servicegroup overview: Cannot quick search for services
* Bug 9502: Comment overview: Cannot quick search
* Bug 9503: Comment overview shows duplicate entries when filtering for services
* Bug 9504: Contactgroup overview: Cannot quick search
* Bug 9505: Contact overview: Cannot quick search
* Bug 9506: Notification overview: Cannot quick search
* Bug 9509: Setup: Authentication backend validation broken
* Bug 9511: Setup: Cannot select an existing user as admin account when I've configured an authentication backend of type msldap
* Bug 9516: Improve request processing for all monitoring config forms
* Bug 9517: Behave nicely in case no monitoring instance resources are configured
* Bug 9519: Monitoring backend configuration does not validate IDO resources
* Bug 9529: RPM: Apache config ist not defined as configuration file
* Bug 9530: Creating a dashlet with "()" in dashboard title affects all dashboards
* Bug 9538: Use display_name for host and service names in the service grid
* Bug 9553: User- and Group-Management broken on PHP > 5.3
* Bug 9572: Cannot remove a user group from a MariaDB backend
* Bug 9573: Selecting multiple services not working while being restricted
* Bug 9574: Multiviews do not only display the chosen objects but everything, if a restriction is active
* Bug 9582: icon_image does not allow to use an icon from our ifont
* Bug 9597: Clicking on the row of a service notification will show the host
* Bug 9607: Ignoring LDAP connection certificate errors does not have any effect
* Bug 9608: LDAP connection must fail when the configured encryption is not possible
* Bug 9611: generictts integration fails if regular expression is empty
* Bug 9615: Hardcoded PHP and gettext tools path
* Bug 9616: Security config form shows no tabs
* Bug 9626: Tactical overview does not auto-refresh
* Bug 9633: Icinga\Cli\Command is unable to detect exact action names
* Bug 9646: If a CLI command fails, crucial exception information missing w/o --trace
* Bug 9668: Browser history issues
* Bug 9672: Invalid host passive check result state: unreachable
* Bug 9674: Don't show comment(s) of acknowledgement(s) in the comment list of a host or service but next to whether the host or service problem is acknowledged
* Bug 9687: @import rules not working in a module's module.less
* Bug 9688: Icinga Web 2 ignores Cache-Control:no-cache
* Bug 9692: Can't filter for custom variables
* Bug 9694: Lib: Weird interface for creating problem menu entries
* Bug 9695: IDO: Empty programstatus table not indicated as problem in the menu
* Bug 9696: Logged exceptions for custom menu item renderers are missing crucial exception information
* Bug 9719: Monitoring backend validation cannot be skipped
* Bug 9739: DbUserBackend inspection unsuccessful for backends with just a single user
* Bug 9751: Bad performance for quick searches
* Bug 9765: instances.ini: transport is undocumented
* Bug 9787: It's not possible to use Unix socket to connect to PostgreSQL
* Bug 9790: Do not suggest to enable modules if it's not possible
* Bug 9815: Multiview detail: controls have wrong link target
* Bug 9817: Documentation: Required parameter 'chapter' missing
* Bug 9819: JS Behaviors: Selection not updated when using multi detail controls
* Bug 9828: Wrong count for queries having a group by clause
* Bug 9837: Documentation: Don't suggest to install icingacli on Debian
* Bug 9844: url anchors not working if a column hash (#!) is also part of the url
* Bug 9869: A module's rendered event is not called upon initialization
* Bug 9892: Module styles not visible for anonymous users
* Bug 9901: Use the DN to fetch group memberships from LDAP
* Bug 9932: Url to extend the timeline is pushed to history
* Bug 9954: PostgreSQL queries use LOWER(...) for non-collated columns which have a collated counterpart
* Bug 9955: PostgreSQL queries ordered by collated columns don't use LOWER
* Bug 9956: Unnecessary GROUP BY clauses
* Bug 9959: Authentication documentation suggests outdated backend identifier "ad"
* Bug 9963: Service history is disordered and shows service and host history
* Bug 9965: format=json does not respect the filter objects
* Bug 9971: Seleting multiple objects at once doesn't work anymore
* Bug 9995: "Show More" links broken in the Alert Summary
* Bug 9998: Can't use custom variables as restriction filter
* Bug 10009: Prettify page layout when accessing a non-existent route while not being authenticated
* Bug 10016: config/* does not permit access to the application and authentication configuration
* Bug 10025: Filter, submitting form via keyboard doesn't work on chrome
* Bug 10031: Navigation by history is broken
* Bug 10046: Menu is somehow confusing top/sub-level entries
* Bug 10082: Adding an entry to a menu section influences it's position
* Bug 10150: IniParser should unescape escaped sections automatically
* Bug 10151: Do not validate section names in forms
* Bug 10155: Multiselection disapperears when issuing commands
* Bug 10160: Notifications/Alert Summary: Grouping errors w/ PostgreSQL
* Bug 10163: Search for hostname does not work in snapshot release
* Bug 10169: Multiselect URLs broken where base url != /icingaweb2
* Bug 10172: Customvar filters are mostly broken, completely for Icinga 1.x
* Bug 10218: Notes URL isn't showing properly
* Bug 10236: notes_url and action_url target is always icinga.domain.de
* Bug 10246: Use a separate configuration file for each type of navigation item
* Bug 10263: Forms with target=_next remain unusable after first submission

### What's New in Version 2.0.0-rc1

#### Changes

* Improve layout and look and feel in many ways
* Apply host, service and custom variable restrictions to all monitoring objects
* Add fullscreen mode (?showFullscreen)
* User and group management
* Comment and Downtime Detail View
* Show icon_image in host/service views
* Show Icinga program version in monitoring health

#### Features

* Feature 4139: Notify monitoring backend availability problems
* Feature 4498: Allow to add columns to monitoring views via URL
* Feature 6392: Resolve Icinga 2 runtime macros in action and notes URLs
* Feature 6729: Fullscreen mode
* Feature 7343: Fetch user groups from LDAP
* Feature 7595: Remote connection resource configuration
* Feature 7614: Right-align icons
* Feature 7651: Add module information (module.info) to all core modules
* Feature 8054: Host Groups should list number of hosts (as well as services)
* Feature 8235: Show host and service notes in the host and service detail view
* Feature 8247: Move notifications to the bottom of the page
* Feature 8281: Improve layout of comments and downtimes in the host and service detail views
* Feature 8310: Improve layout of performance data and check statistics in the host and service detail views
* Feature 8565: Improve look and feel of the monitoring multi-select views
* Feature 8613: IDO queries related to concrete objects should not depend on collations
* Feature 8665: Show icon_image in the host and service detail views
* Feature 8781: Automatically deselect rows when closing the detail area
* Feature 8826: User and group management
* Feature 8849: Show only three (or four) significant digits (e.g. in check execution time)
* Feature 8877: Allow module developers to implement new/custom authentication methods
* Feature 8886: Require mandatory parameters in controller actions and CLI commands
* Feature 8902: Downtime detail view
* Feature 8903: Comment detail view
* Feature 9009: Apply host and service restrictions to related views as well
* Feature 9203: Wizard: Validate that a resource is actually an IDO instance
* Feature 9207: Show icinga program version in Monitoring Health
* Feature 9223: Show the active ido endpoint in the monitoring health view
* Feature 9284: Create a ServiceActionsHook
* Feature 9300: Support icon_image_alt
* Feature 9361: Refine UI for RC1
* Feature 9377: Permission and restriction documentation
* Feature 9379: Provide an about.md

#### Bugfixes

* Bug 6281: ShowController's hostAction() and serviceAction() do not respond with 400 for invalid/missing parameters and with 404 if the host or service wasn't found
* Bug 6778: Duration and history time formatting isn't correct
* Bug 6952: Unauthenticated users are provided helpful error messages
* Bug 7151: Play nice with form-button-double-clickers
* Bug 7165: Invalid host address leads to exception w/ PostgreSQL
* Bug 7447: Commands sent over SSH are missing the -i option when using a ssh user aside from the webserver's user
* Bug 7491: Switching from MySQL to PostgreSQL and vice versa doesn't change the port in the resource configuration
* Bug 7642: Monitoring menu renderers should be moved to the monitoring module
* Bug 7658: MenuItemRenderer is not so easy to extend
* Bug 7876: Not all views can be added to the dashboard w/o breaking the layout
* Bug 7931: Can't acknowledge multiple selected services which are in downtime
* Bug 7997: Service-Detail-View tabs are changing their context when clicking the Host-Tab
* Bug 7998: Navigating to the Services-Tab in the Service-Detail-View displays only the selected service
* Bug 8006: Beautify command transport error exceptions
* Bug 8205: List views should not show more than the five worst pies
* Bug 8241: Take display_name into account when searching for host and service names
* Bug 8334: Perfdata details partially hidden depending on the resolution
* Bug 8339: Lib: SimpleQuery::paginate() must not fetch page and limit from request but use them from parameters
* Bug 8343: Status summary does not respect restrictions
* Bug 8363: Updating dashlets corrupts their URLs
* Bug 8453: The filter column "_dev" is not allowed here
* Bug 8472: Missing support for command line arguments in the format --arg=<value>
* Bug 8474: Improve layout of dictionaries in the host and service detail views
* Bug 8624: Delete multiple downtimes and comments at once
* Bug 8696: Can't search for Icinga 2 custom variables
* Bug 8705: Show all shell commands required to get ready in the setup wizard
* Bug 8706: INI files should end with a newline character and should not contain superfluous newlines
* Bug 8707: Wizard: setup seems to fail with just one DB user
* Bug 8711: JS is logging "ugly" side exceptions
* Bug 8731: Apply host restrictions to service views
* Bug 8744: Performance data metrics with value 0 are not displayed
* Bug 8747: Icinga 2 boolean variables not shown in the host and service detail views
* Bug 8777: Server error: Service not found exception when service name begins or ends with whitespaces
* Bug 8815: Only the first external command is sent over SSH when submitting commands for multiple selected hosts or services
* Bug 8847: Missing indication that nothing was found in the docs when searching
* Bug 8860: Host group view calculates states from service states; but states should be calculated from host states instead
* Bug 8927: Tactical overview does not respect restrictions
* Bug 8928: Host and service groups views do not respect restrictions
* Bug 8929: Setup wizard does not validate whether the PostgreSQL user for creating the database owns the CREATE ROLE system privilege
* Bug 8930: Error message about refused connection to the PostgreSQL database server displayed twice in the setup wizard
* Bug 8934: Status text for ok/up becomes white when hovered
* Bug 8941: Long plugin output makes the whole container horizontally scrollable instead of just the row containing the long plugin output
* Bug 8950: Improve English for "The last one occured %s ago"
* Bug 8953: LDAP encryption settings have no effect
* Bug 8956: Can't login when creating the database connection for the preferences store fails
* Bug 8957: Fall back on syslog if the logger's type directive is misconfigured
* Bug 8958: Switching LDAP encryption to LDAPS doesn't change the port in the resource configuration
* Bug 8960: Remove exclamation mark from the notification "Authentication order updated!"
* Bug 8966: Show custom variables visually separated in the host and service detail views
* Bug 8967: Remove right petrol border from plugin output in the host and service detail views
* Bug 8972: Can't view Icinga Web 2's log file
* Bug 8994: Uncaught exception on empty session.save_path()
* Bug 9000: Only the first line of a stack trace is shown in the applications log view
* Bug 9007: Misspelled host and service names in commands are not accepted by icinga
* Bug 9008: Notification overview does not respect restrictions
* Bug 9022: Browser title does not change in case of an error
* Bug 9023: Toggling feature...
* Bug 9025: A tooltip of the service grid's x-axe makes it difficult to click the title of the currently hovered column
* Bug 9026: Add To Dashboard ... on the dashboard
* Bug 9046: Detail View: Downtimes description misses space between duration and comment text
* Bug 9056: Filter for host/servicegroup search doesn't work anymore
* Bug 9057: contact_notify_host_timeperiod
* Bug 9059: Can't initiate an ascending sort by host or service severity
* Bug 9198: monitoring/command/feature/object does not grant the correct permissions
* Bug 9202: The config\* permission does not permit to navigate to the configuration
* Bug 9211: Empty filters are being rendered to SQL which leads to syntax errors
* Bug 9214: Detect multitple icinga_instances entries and warn the user
* Bug 9220: Centralize submission and apply handling of sort rules
* Bug 9224: Allow anonymous LDAP binding
* Bug 9281: Problem with Icingaweb 2 after PHP Upgrade 5.6.8 -> 5.6.9
* Bug 9317: Web 2's ListController inherits from the monitoring module's base controller
* Bug 9319: Downtimes overview does not respect restrictions
* Bug 9350: Menu disappears in user group management view
* Bug 9351: Timeline links are broken
* Bug 9352: User list should be sorted
* Bug 9353: Searching for users fails, at least with LDAP backend
* Bug 9355: msldap seems not to be a first-class citizen
* Bug 9378: Rpm calls usermod w/ invalid option on openSUSE
* Bug 9384: Timeline+Role problem
* Bug 9392: Command links seem to be broken
