# moodle-plagiarism_unicheck  

[![Build Status](https://travis-ci.org/unicheck/moodle-plagiarism_unicheckcorp.svg?branch=release%2F2.x)](https://travis-ci.org/unicheck/moodle-plagiarism_unicheckcorp)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/unicheck/moodle-plagiarism_unicheckcorp/badges/quality-score.png?b=release%2F2.x)](https://scrutinizer-ci.com/g/unicheck/moodle-plagiarism_unicheckcorp/?branch=release%2F2.x)

## Unicheck Plagiarism plugin for Moodle

**Supported versions:**

| Moodle | PHP | Unicheck plugin | Branch | Changelog
| :---: | :---: | :---: | :---: | :---: |
| 3.3 - 3.5 | 5.6 - 7.1 | 2.x.x | [release/2.x](/unicheck/moodle-plagiarism_unicheckcorp/tree/release/2.x) | [link](/unicheck/moodle-plagiarism_unicheckcorp/blob/release/2.x/CHANGELOG.md)
| 2.7 - 3.2 | 5.4 - 7.1 | 1.x.x | [release/1.x](/unicheck/moodle-plagiarism_unicheckcorp/tree/release/1.x) | [link](/unicheck/moodle-plagiarism_unicheckcorp/blob/release/1.x/CHANGELOG.md)


**Moodle plugins directory:** https://moodle.org/plugins/plagiarism_unicheck

Author: Ben Larson <developer@unicheck.com>  
Copyright: UKU Group, LTD, https://www.unicheck.com  

 > Unicheck is a commercial Plagiarism Prevention product owned by UKU Group, LTD - you must have a paid subscription to be able to use this plugin.  

#### Quick install

1. Get latest release (zip file) on [GitHub](https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases) or 
[Moodle plugins directory](https://moodle.org/plugins/plagiarism_unicheck)
2. Follow the instructions described [here](https://docs.moodle.org/31/en/Installing_plugins#Installing_via_uploaded_ZIP_file) to install plugin
3. Enable the Plagiarism API under *Site Administration > Advanced Features*
4. Configure the Unicheck plugin under *Site Administration > Plugins > Plagiarism > Unicheck*  

#### Requirements  

 - Unicheck plugin requires Moodle cron to be properly configured. See [documentation](https://docs.moodle.org/33/en/Cron).
 We recommend scheduling cron as often as possible (more often than once per hour).

 - For supporting RAR archives you have to install php-ext using command 
`pecl install rar`. Find more info on [php.net](http://php.net/manual/en/rar.installation.php).

 - We use URL `https://<moodle_server_domain>/plagiarism/unicheck/callback.php?token={token}>` as listener for HTTP callbacks 
that receive notification messages for events (FILE_UPLOAD_ERROR, etc). If this address is available only in private network, 
you must open it for the network _5.39.49.208/28_

#### Upgrading from *Unplag Plagiarism plugin for Moodle*

1. Update [Unplag Plagiarism plugin for Moodle](https://moodle.org/plugins/plagiarism_unplag) to version 3.0.0 or higher 
2. Finish all adhoc tasks
3. Install *Unicheck Plagiarism plugin for Moodle*.
During installation all Unplag plugin data is copied to Unicheck plugin database tables.
4. Uninstall *Unplag Plagiarism plugin for Moodle* (lose data from old plugin) or disable it (without lose data)