# Icinga Web 2 Changelog

Please make sure to always read our [Upgrading](doc/80-Upgrading.md) documentation before switching to a new version.

## What's New

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

