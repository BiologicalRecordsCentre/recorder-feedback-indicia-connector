# recorder-feedback-indicia-connector

A module which integrates Drupal user profiles with the Recorder Feedback service for Indicia
wildlife recording data.

For more information on the Recorder Feedback project see
* https://github.com/BiologicalRecordsCentre/recorder-feedback-controller
* https://github.com/BiologicalRecordsCentre/recorder-feedback

This module does the following:
* Adds checkboxes to the user edit or register form which allow the user to select which categories
  of Recorder Feedback emails they whish to receive.
* Handles registration of the user on the Recorder Feedback service and updates their settings.