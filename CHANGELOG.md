# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [3.2.0] - 2021-05-12
### Fixed
- Some quiz answers are not getting checked

## [3.1.1] - 2020-12-16
### Added
- `CURLOPT_TIMEOUT` for all requests to Unicheck (The maximum number of seconds to allow cURL functions to execute).
- Added unicheck_users `api_data_hash` DB field

### Changed
- Data transfer type when uploading a file to Unicheck. (from base64 to `multipart/form-data`)

### Fixed
- Duplicate requests to update user rights in Unicheck
- https://github.com/unicheck/moodle-plagiarism_unicheckcorp/issues/103

## [3.0.1] - 2020-11-24
### Added
- Tracking moodle version by submitted files

## [3.0.0] - 2020-09-01
### Fixed
- stop using deprecated setting `unicheck_use` (already use `enabled`) in `config_plugins` table
- migrate all settings in `config_plugins` table from `plugin=plagiarism` to `plugin=plagiarism_unicheck`
- stop using deprecated methods: `\plagiarism_plugin_unicheck::get_form_elements_module` => `plagiarism_unicheck_coursemodule_standard_elements`
    and `\plagiarism_plugin_unicheck::save_form_elements` => `plagiarism_unicheck_coursemodule_edit_post_actions`

### Added
- quiz support

[3.1.1]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v3.1.1
[3.1.0]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v3.1.0
[3.0.1]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v3.0.1
[3.0.0]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v3.0.0
