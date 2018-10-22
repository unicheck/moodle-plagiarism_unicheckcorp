# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [2.3.5] - 2018-10-18
### Changed
- Handle zero value of sensitivity settings

### Fixed
- Validate field with zero value
- Show similarity result on forum posts with moodle images

## [2.3.3] - 2018-08-01
### Changed
- Reset plagiarism detection status in workshop when switched from Assessment phase to Submission phase

## [2.3.2] - 2018-06-29
### Fixed
- Default value for type of check comparison

## [2.3.1] - 2018-06-18
### Added
- Ability to handle files and checks that froze

### Changed
- Default teacher's capabilities for plugin settings

## [2.3.0] - 2018-04-16
### Added
- Self-plagiarism excluding

### Changed 
- RAR,ZIP archive supporting is now OPTIONAL

### Fixed
- Cron crash when RAR or ZIP php extension is't installed

## [2.2.18] - 2018-04-11
### Fixed
- Incorrect processing of large files
- Error handled trigger

### Changed 
- Skipping files larger than 70 MB (Unicheck max file upload size)
- Event observe validation

## [2.2.17] - 2018-04-06
### Fixed
- Similarity background color in 0.00%
- CSS code prechecks

## [2.2.16] - 2018-04-03
### Changed
- Show Unicheck ID new the student file
- Similarity score colorful background
- Logo and link image quality

## [2.2.14] - 2018-03-16
### Changed
- Rename setting "Sent student report" to "Notify students via email"

## [2.2.12] - 2018-03-07
### Added
- Plugin events
- Setting "Enable API logging"
- Setting "Sent students report"
- Javascript AMD (Asynchronous Module Definition)

### Changed
- Event observers

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

[2.3.5]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v2.3.5
[2.3.3]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v2.3.3
[2.3.2]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v2.3.2
[2.3.1]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v2.3.1
[2.3.0]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v2.3.0
[2.2.18]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v2.2.18
[2.2.17]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v2.2.17
[2.2.16]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v2.2.16
[2.2.14]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v2.2.14
[2.2.12]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v2.2.12
[2.2.7]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v2.2.7
[2.2.1]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v2.2.1
[2.2.0]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v2.2.0
[2.1.0]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v2.1.0
[2.0.1]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v2.0.1
[2.0.0]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v2.0.0