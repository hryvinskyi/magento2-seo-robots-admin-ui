# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.2] - 2026-01-25
### Fixed
- Fixed disabled state handling in DirectiveMultiselect using proper getDisabled() method

### Changed
- Refactored DirectiveArray backend model to use constructor property promotion
- Reorganized system.xml configuration into separate groups for Meta Tag and X-Robots-Tag settings

### Added
- Comprehensive integration tests for backend models
- MFTF tests for admin configuration

## [2.0.1] - Previous Release
### Features
- Admin UI for SEO Robots module with flexible directive management
- Multiselect interface for directive configuration
