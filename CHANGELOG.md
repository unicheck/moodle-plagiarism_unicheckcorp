# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [3.0.0] - 2020-09-01
### Fixed
- stop using deprecated setting `unicheck_use` (already use `enabled`) in `config_plugins` table
- migrate all settings in `config_plugins` table from `plugin=plagiarism` to `plugin=plagiarism_unicheck`
- stop using deprecated methods: `\plagiarism_plugin_unicheck::get_form_elements_module` => `plagiarism_unicheck_coursemodule_standard_elements`
    and `\plagiarism_plugin_unicheck::save_form_elements` => `plagiarism_unicheck_coursemodule_edit_post_actions`

### Added
- quiz support

[3.0.0]: https://github.com/unicheck/moodle-plagiarism_unicheckcorp/releases/tag/v3.0.0
