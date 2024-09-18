# [DEPRECATED] This project has reached the end of its development #

# moodle-plagiarism_unicheck

[![Build Status](https://travis-ci.org/unicheck/moodle-plagiarism_unicheckcorp.svg?branch=release%2F2.x)](https://travis-ci.org/unicheck/moodle-plagiarism_unicheckcorp)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/unicheck/moodle-plagiarism_unicheckcorp/badges/quality-score.png?b=release%2F2.x)](https://scrutinizer-ci.com/g/unicheck/moodle-plagiarism_unicheckcorp/?branch=release%2F2.x)

## Unicheck Plagiarism plugin for Moodle

**Supported versions:**

| Moodle | PHP | Unicheck plugin | Branch | Changelog
| :---: | :---: | :---: | :---: | :---: |
| 3.9 - 4.0.1 | 7.2 - 7.4 | 3.x.x | [release/3.x](/unicheck/moodle-plagiarism_unicheckcorp/tree/release/3.x) | [link](/unicheck/moodle-plagiarism_unicheckcorp/blob/release/3.x/CHANGELOG.md)
| 3.3 - 3.9 | 5.6 - 7.4 | 2.x.x | [release/2.x](/unicheck/moodle-plagiarism_unicheckcorp/tree/release/2.x) | [link](/unicheck/moodle-plagiarism_unicheckcorp/blob/release/2.x/CHANGELOG.md)
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

 * Unicheck plugin requires Moodle cron to be properly configured. See [documentation](https://docs.moodle.org/39/en/Cron).
 We recommend scheduling cron as often as possible (more often than once per hour).

 * We use URL `https://<moodle_server_domain>/plagiarism/unicheck/callback.php?token={token}>` as listener for HTTP callbacks
that receive notification messages for events (FILE_UPLOAD_ERROR, etc). If this address is available only in private network,
You must open it for the following IP addresses:
    * US
        * 34.230.125.90
        * 3.211.200.10
        * 34.226.189.77
    * EU
        * 18.194.14.147
        * 52.29.163.23
        * 35.156.154.69
    * AU
        * 13.237.249.27
        * 13.238.234.1
        * 3.24.161.242

#### Upgrading from *Unplag Plagiarism plugin for Moodle*

1. Update [Unplag Plagiarism plugin for Moodle](https://moodle.org/plugins/plagiarism_unplag) to version 3.0.0 or higher
2. Finish all adhoc tasks
3. Install *Unicheck Plagiarism plugin for Moodle*.
During installation all Unplag plugin data is copied to Unicheck plugin database tables.
4. Uninstall *Unplag Plagiarism plugin for Moodle* (lose data from old plugin) or disable it (without lose data)

#### Quiz - Essay question support.
The latest version of this plugin provides support for essay questions within the quiz activity, however Moodle **less than version 3.11** doesn’t provide a way for you to view the score/report.
To allow the report to be viewed you must add a patch to the core Moodle code-base. 
More information on this is in the Moodle Tracker: MDL-32226
For a direct link to the patch required see: https://github.com/moodle/moodle/commit/dfe73fadfcf0bae603aa58707e48182a221eea5a
