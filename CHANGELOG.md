# Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [1.6.2] - 2025-11-14

- Fix agent wakeup when no last_agent_wakeup has been set yet
- Prevent fatal error when a task references an item that no longer exist
- Fix for adding `targets` and `actors` to a task
- FIx extra menu entries links

## [1.6.1] - 2025-11-05

- Fix visibility issue on deploy dynamic groups with GLPI v11.0.2
- Fix deploy ajax action path
- Prevent fatal errors when ini_set is not allowed

## [1.6.0] - 2025-09-30

- GLPI v11 compatibility

## [1.5.4] - 2025-09-19

- PHP 8.5 compatibility
- Fix missing index in network inventory/discovery communication
- Fix bad `includes` for `ESX` module

## [1.5.3] - 2025-05-12

### Fixed

- Fix `getFilePart` for `deploy` module
- Fix `Target` drop-down list to filter on active target only (if is_active fields exist) #679 & #682
- Fix check right for `Task`

## [1.5.2] - 2025-04-30

### Fixed

- Fix warning about missing pics
- Fix JS loading detection

## [1.5.1] - 2025-04-29

### Fixed

- Fix implementation of link in task log
- Fix wrong name displayed in taskjob state page
- Fixed restart button appearing on non-error tasks in task status page

### Security

- [CVE-2025-32786] Strengthened parameter validation across the collect, deploy, and esx entry points to better prevent SQL injections

## [1.5.0] - 2025-02-25

### Fixed

- Ensure that the `Taskjob` identifier is used to extract the device IP.
- Isolate dynamic group criteria to prevent their global reapplication in GLPI.
- Fixes memory exhaustion when "extra-debug" is disabled
- Remove "server_upload_path" configuration from database. This is a BC break if you use that for package deployment.
- It now relies on `GLPI_PLUGIN_DOC_DIR/glpiinventory/upload/`; GLPI_PLUGIN_DOC_DIR by default set to `files/_plugins/` under your GLPI instance.

## [1.4.0] - 2024-09-06

### Fixed

- Fix pinned item (from `Job executions`)
- The columns could not be added in the computer search view (collect).
- Fix `invalid item` for obsolete agents.
- Fix upload directory set in configuration.
- Fix `cancel` count from job executions.
- Fix the management of the `include old jobs` parameter.
- Use Device IP in IP range set on task for Device NetInventory
- Fix pagination for `NetInventory` state page

### Feat

- Display all computers details for dynamics group
- Count agent handle by `Taskscheduler`

### Changes

- Encrypt credentials
- Displays ```Tasks / Groups``` tab only on computers linked to an agent


## [1.3.5] - 2024-02-26

### Changes

- ```Upload from server``` option is no longer available in a ```CLOUD``` context

### Fixed

- Prevents the task from being cancelled if the agent wakes up too early

### Added
