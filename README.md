# moodle-plagiarism_unicheck  

[![Build Status](https://travis-ci.org/unicheck/moodle-plagiarism_unicheckcorp.svg?branch=release%2F2.x)](https://travis-ci.org/unicheck/moodle-plagiarism_unicheckcorp)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/unicheck/moodle-plagiarism_unicheckcorp/badges/quality-score.png?b=release%2F2.x)](https://scrutinizer-ci.com/g/unicheck/moodle-plagiarism_unicheckcorp/?branch=release%2F2.x)

Unicheck Plagiarism plugin for Moodle

**Supported Moodle versions:** 3.3-3.4  
**Supported PHP versions:** 5.6 - 7.1  
**Moodle plugins directory:** https://moodle.org/plugins/plagiarism_unicheckcorp

Author: Ben Larson <developer@unicheck.com>  
Copyright: UKU Group, LTD, https://www.unicheck.com  

 > Unicheck is a commercial Plagiarism Prevention product owned by UKU Group, LTD - you must have a paid subscription to be able to use this plugin.  

#### QUICK INSTALL

1. Get latest release (zip file) on [GitHub](https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases) or [Moodle plugins directory](https://moodle.org/plugins/plagiarism_unicheckcorp)
2. Follow the instructions described [here](https://docs.moodle.org/31/en/Installing_plugins#Installing_via_uploaded_ZIP_file) to install plugin
3. Enable the Plagiarism API under admin > Advanced Features  
4. Configure the Unicheck plugin under admin > plugins > Plagiarism > Unicheck  

#### Dependencies  

1. For supporting RAR archives you have to install php-ext using command bellow 
```sh
pecl install rar
```

#### Upgrading from *Unplag Plagiarism plugin for Moodle*

1. Update [Unplag Plagiarism plugin for Moodle](https://moodle.org/plugins/plagiarism_unicheckcorp) to version 3.0.0 or higher 
2. Finish all adhoc tasks
3. Install *Unicheck Plagiarism plugin for Moodle* [Read our installation guide](#quick-install).
After installation all checked files info moved to new plugin tables
4. Uninstall *Unplag Plagiarism plugin for Moodle* (lose data from old plugin) or disable it (without lose data)