# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [1.3.5] - 2018-10-18
### Changed
- Handle zero value of sensitivity settings

### Fixed
- Validate field with zero value
- Show similarity result on forum posts with moodle images

## [1.3.2] - 2018-08-01
### Changed
- Reset plagiarism detection status in workshop when switched from Assessment phase to Submission phase

## [1.3.1] - 2018-06-29
### Fixed
- Default value for type of check comparison

## [1.3.0] - 2018-04-18
### Added
- Self-plagiarism excluding
- Add comments table
- Add file metadata column
- Permissions to change plugin settings
- Show a notification if there is a limitation in the archive

###Changed 
- RAR,ZIP archive supporting is now OPTIONAL
- Rename settings
- Event observers
- Settings descriptions
- Sources for comparison display only those that are available on unicheck.com for the used API keys

### Fixed
- Cron crash when RAR or ZIP php extension is't installed

## [1.2.2] - 2018-04-11
### Fixed
- Incorrect processing of large files
- Error handled trigger

### Changed 
- Skipping files larger than 70 MB (Unicheck max file upload size)
- Event observe validation
- Show Unicheck ID new the student file
- Similarity score colorful background
- Logo and link image quality
- Rename settings

## [1.2.1] - 2017-12-14
### Added
- Add unit tests

### Fixed
- Catch and skip deleted files after assignment resubmit, while cron hasn't run

## [1.2.0] - 2017-12-01
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

## [1.1.0] - 2017-11-08
### Added
- Added .rar archives support

## [1.0.1] - 2017-09-12
### Removed
- Remove old db migration 1.x (#2)

## [1.0.0] - 2017-09-07
### Changed
- Renaming, based on [Unplag Plagiarism plugin for Moodle](https://moodle.org/plugins/plagiarism_unplag)
- Change validation mode
- Massive refactoring comparing to Unplag plugin

[1.3.5]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v1.3.5
[1.3.2]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v1.3.2
[1.3.1]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v1.3.1
[1.3.0]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v1.3.0
[1.2.2]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v1.2.2
[1.2.1]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v1.2.1
[1.2.0]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v1.2.0
[1.1.0]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v1.1.0
[1.0.1]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v1.0.1
[1.0.0]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v1.0.0