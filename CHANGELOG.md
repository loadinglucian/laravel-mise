# Changelog

## [1.0.0] - 2026-01-20

### Added
- Initial functionality with pint, larastan and ide-helper
- Prettier code formatter
- Horizon monitoring package
- Pulse and other improvements
- Command options support
- --force parameter to override existing files
- Pest GitHub workflow
- GitHub branch protection ruleset file distribution
- db:migrate command as an alias for the migrate command
- laravel-debugbar and laravel-query-detector packages
- post-dist command
- Post-payload commands for package configuration
- Conditional installation based on package dependencies
- Dynamic command options injection system
- Interactive confirmation and output streaming
- Output streaming for real-time command feedback
- Comprehensive unit tests for BaseCommand output helpers
- Deferred composer scripts until autoloader is regenerated

### Fixed
- Ignore third party php files from phpstan
- Composer.json Laravel 11 compatibility
- Dependabot semver-major handling
- Command typo in .cursorrules
- Accidental readme issue
- Dist workflows
- Missing test coverage support
- Command options leaking to package manager commands
- Scope command options per-package instead of globally
- Exclude config/database.php from rector and phpstan
- Reduce tool analysis scope

### Changed
- Make menu leaner
- Rename Omakase to Buffet to Laravel Mise
- Complete package restructure with new namespace
- Remove integration test suite from configurations
- Consistent comments with rules
- Remove cursor rules and commands configuration
- Updated README with comprehensive package documentation
- Updated distribution files with improvements
- Clean dependencies and improve tooling configuration
- Simplify mise command signature from laravel:mise to mise
- Use glob patterns for rector CICanary skip
- Refactor omakase command and tests
- Improve testing suite with multiple enhancements
- Modernize ProcessService TTY handling and tests

### Security
- Add roave/security-advisories package
