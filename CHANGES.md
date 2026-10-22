## 1.2.3 ##

* BWS Uster 2026: Added scheduled task (db/tasks.php, classes/task/cron_task.php)
  because Moodle 5.x no longer executes the legacy plugin cron ($plugin->cron).
* Local change in locallib.php: the cleanup query is no longer tied to the
  instance that created the membership (gm.itemid = e.id). It now removes a
  sync-managed membership whenever the user is not covered by ANY active cohort
  mapping for the group. This heals leftovers from previous instance generations
  and correctly handles groups mapped from multiple cohorts.

## 1.2.2 ##

* Travis-CI update only.

## 1.2.1 ##

* Privacy API updated to fulfil requirements of latest Moodle versions (credit goes
  to Amaia Anabitarte).
* Behat test updated for Moodle 3.4, 3.5 and 3.6.

## 1.2.0 ##

* Implement the privacy API

## 1.1.2 ##

* Behat test updated for Moodle 3.3 and 3.4

## 1.1.1 ##

* Coding guidelines violations fixed.

## 1.1.0 ##

* New version for Moodle 3.1 and higher.
* Fixed missing param type of the custom name at the instance editing form and
  added validation of it.
* Fixed missing `can_hide_show_instance()` implementation.
* Added ability to delete existing instances of the enrolment plugin in
  courses.
* Legacy event handlers migrated to current event observers.
* Added Behat tests.
* Added Travis-CI support.
* David Mudrák <david@moodle.com> became the lead maintainer of the plugin.
