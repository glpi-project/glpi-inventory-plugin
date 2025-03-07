# Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [unreleased]

### Fixed

- Fix implementation of link in task log

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
