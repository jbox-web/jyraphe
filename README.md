Jyraphe, your web file repository
=================================

Jyraphe is a web application of file repository, easy to install and easy to
use. Jyraphe is an entirely free application, it is distributed under the
terms of the GNU Affero General Public License, version 3 or later. See the
COPYING file in this directory.

For more information, see:
http://home.gna.org/jyraphe/

Jyraphe 0.7 (04 octobre 2013)
-----------------------------

  - FEATURE: Add JQuery 1.10
  - FEATURE: Add JQuery File Upload
  - FEATURE: Add Email notification on upload
  - FEATURE: Ajax rendering
  - FEATURE: Render raw text if called with Curl
  - BUG: CSS fixes


Jyraphe 0.6a1 (31 janvier 2010)
-------------------------------

The Jyraphe team is pleased to announce the release of Jyraphe 0.6 alpha1.

As the name points out, this version is an early release and may not work properly.

We haven't remained idle all this time, and this new version bundles the following new features:

  - Switch from functional code to object-oriented structure.
  - Changed the configuration to the Unix conf file structure (or .ini for Windows users).
  - Changed the file descriptors from bare text files to conf files.
  - Improved visual appearance and added a new style.
  - Removed the Trash directory. Outdated files are now deleted.
  - Added a cleaning script that can be run by cron to keep the files'pool clean.
  - The infinite validity option can be disabled.
  - The validity period is now defaulted to one week (the default can be changed.)

Please also note that this version is not compatible with the 0.5 release, and the uploaded files would be lost using this one.


Jyraphe 0.5 (26 juin 2009)
--------------------------

  - Corrected installer
  - Easier use of the installer (the enter key can be used to validate a step)
  - The installer script (install.php) must be deleted for the jyraphe to work. This is done for security reasons.
  - All errors now appear in one big error box instead of separate error boxes.


Jyraphe 0.4 (20 april 2009)
---------------------------

  - SECURITY: possible path traversal by uploading a file R0...0 (32 zeroes)
    and calling file.php?h=../files/R0..0
  - BUG: possibility to download a protected file without a key
  - FEATURE: better form (without tables)


Jyraphe 0.3 (28 may 2008)
-------------------------

  - SECURITY: a forged link file could be uploaded and accessed with
    get.php?h=../files/forgedfile so that any readable file could be accessed
  - FEATURE: install.php script with randomised name of the var/ directory
  - FEATURE: password protection
  - FEATURE: time limit
  - FEATURE: Javascript to show the extended options
  - FEATURE: support for multiple CSS and better handling of images in the css
  - FEATURE: better Content-Type handling and XHTML validation
  - SECURITY: now prevent .php from upload, rename it in .phps
  - FEATURE: renamed get.php in file.php (in prevision of thumb.php)


Jyrahe 0.2 (22 april 2008)
--------------------------

  - SECURITY: .htaccess could be uploaded and change the access of var/
  - BUG: infinite loop when renaming a file in case of a collision
  - BUG: warning of the substr_compare when null mime-type
  - BUG: substr_compare not defined for old PHP4


Jyraphe 0.1 (12 april 2008)
---------------------------

  - First release of Jyraphe
  - KNOWN BUG: when not defining $cgf['web_root'] in config.local.php, and
    having $cfg['use_redirect'] = true, the CSS does not appear in case of 404
    error. Workaround: define $cgf['web_root'] in your config.local.php
