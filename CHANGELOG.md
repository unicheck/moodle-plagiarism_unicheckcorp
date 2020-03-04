# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [2.5.2] - 2020-03-04
### Fixed
- The similarity report uses the role that is defined in the course context

## [2.5.1] - 2020-01-21
### Added
- Australia region support

## [2.5.0] - 2019-11-21
### Added
- Moodle 3.8 support
- mustache templates

### Changed
- cheating detect block design
- css class names
- remove relative includes starting with "../"
- don't query the database in loops in some locations

### Fixed
-  string definition for cachedef_debugging/plagiarism_unicheck
-  environment test (php_extension:rar)

### Removed
- ttl value from db/caches.php

### Security
- capability checks in track_progress endpoint
- parameterize sql code
- clean data output in various locations
- OAuth verify of Unicheck callback requests

## [2.4.3] - 2019-09-30
### Changed
- Avoid the use of PARAM_RAW
- Use $OUTPUT->image_icon() instead of $OUTPUT->pix_icon()

### Removed
- Unused functions

## [2.4.2] - 2019-06-20
### Added
- Unicheck Availability Status in debugging tab
- Pagination and filter in debugging table
- Unicheck API Region select in plugin settings tab
- MOODLE_37_STABLE in .travis.yml matrix

### Changed
- The students do not see any mention about the service Unicheck when both settings
`Show similarity scores to student` and `Show similarity reports to student` within the meaning of **NO**
- resource_id in Unicheck callbacks can be NULL

### Fixed
- `Please use file_data parameter` with an empty "Online text" field
- `Please use file_data parameter` when the file cannot be read from the file system

## [2.4.1] - 2019-02-21
### Added
- Handle SIMILARITY.CHECK.RECALCULATED event for live recalculating in moodle plugin from Unicheck service
- Saving all Unicheck callbacks in the database

### Fixed
- Using the correct access rights when viewing the similarity report after changing API keys
- The similarity report is sent to at the correct email address after the student has changed his email address.

## [2.4.0] - 2019-01-22
### Added
- Show cheating indicator
- The ability to run scans on online text submissions in an assign that was already submitted before unicheck plugin was turned on
- Privacy API

### Changed
- Plugin design

### Fixed
- Incorrect check type display for teachers

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

[2.5.2]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v2.5.2
[2.5.1]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v2.5.1
[2.5.0]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v2.5.0
[2.4.3]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v2.4.3
[2.4.2]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v2.4.2
[2.4.1]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v2.4.1
[2.4.0]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v2.4.0
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