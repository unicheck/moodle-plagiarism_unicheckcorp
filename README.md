# moodle-plagiarism_unicheck  

[![Build Status](https://travis-ci.org/unicheck/moodle-plagiarism_unicheckcorp.svg?branch=master)](https://travis-ci.org/unicheck/moodle-plagiarism_unicheckcorp)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/unicheck/moodle-plagiarism_unicheckcorp/badges/quality-score.png?b=release%2F1.x)](https://scrutinizer-ci.com/g/unicheck/moodle-plagiarism_unicheckcorp/?branch=release%2F1.x)

Unicheck Plagiarism plugin for Moodle

**Supported Moodle versions:** 2.7 - 3.2  
**Supported PHP versions:** 5.4 - 7.1  
**Moodle plugins directory:** https://moodle.org/plugins/plagiarism_unicheckcorp

Author: Ben Larson <developer@unicheck.com>  
Copyright: UKU Group, LTD, https://www.unicheck.com  

 > Unicheck is a commercial Plagiarism Prevention product owned by UKU Group, LTD - you must have a paid subscription to be able to use this plugin.  

QUICK INSTALL  
==============  

1. Get latest release (zip file) on [GitHub](https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases) or 
[Moodle plugins directory](https://moodle.org/plugins/plagiarism_unicheckcorp)
2. Follow the instructions described [here](https://docs.moodle.org/31/en/Installing_plugins#Installing_via_uploaded_ZIP_file) to install plugin
3. Enable the Plagiarism API under admin > Advanced Features  
4. Configure the Unicheck plugin under admin > plugins > Plagiarism > Unicheck  

## Dependencies  

1. For supporting RAR archives you have to install php-ext using command bellow 
```sh
pecl install rar
```

## Changelog

| Version | Date | Changelog |
| ------- | ---- | --------- |
| 1.2.0 | Dec 1, 2017 | <ul><li>Async upload</li><li>Max supported archive files setting</li><li>Change base urls</li></ul>|
| 1.1.0 | Sept 21, 2017 | Added support RAR files |