# Change log for SMR Auto Check-in Plugin for Joomla!

## v1.1.1 build 20 - 2017-02-13

### Fixed

* An error in a regular expression caused `verifyTable()` to always fail.

## v1.1.0 build 18 - 2017-02-09

### Added
- Supports now other extensions that use the same check-in/check-out method as
  Joomla!, meaning there are two columns in a table: one storing the user ID of
  the user who checked out the item and the other the check-out time.