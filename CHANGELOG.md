# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [2.2.7] - 2018-02-07
### Added
- Permissions to change plugin settings
- Show a notification if there is a limitation in the archive
- Add comments table
- Add file metadata column

### Changed
- Settings descriptions
- Sources for comparison display only those that are available on unicheck.com for the used API keys

## [2.2.1] - 2017-12-14
### Added
- Add unit tests

### Fixed
- Catch and skip deleted files after assignment resubmit, while cron hasn't run

## [2.2.0] - 2017-12-01
### Added
- Async upload
- Upload/Check adhoc tasks
- Max supported archive files setting
- File states
- File upload callback handle

### Changed
- Change base urls

### Fixed
- https://github.com/unicheck/moodle-plagiarism_unicheckcorp/issues/5

## [2.1.0] - 2017-11-08
### Added
- Added .rar archives support

## [2.0.1] - 2017-09-12
### Removed
- Remove old db migration 1.x (#2)

## [2.0.0] - 2017-09-07
### Changed
- Renaming, based on [Unplag Plagiarism plugin for Moodle](https://moodle.org/plugins/plagiarism_unplag)
- Change validation mode
- Massive refactoring comparing to Unplag plugin

[2.2.5]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v2.2.5
[2.2.1]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v2.2.1
[2.2.0]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v2.2.0
[2.1.0]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v2.1.0
[2.0.1]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v2.0.1
[2.0.0]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v2.0.0