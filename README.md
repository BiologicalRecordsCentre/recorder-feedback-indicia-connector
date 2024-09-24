![](https://github.com/BiologicalRecordsCentre/recorder-feedback/blob/main/Recorder%20feedback%20logo_small.png?raw=true)

# recorder-feedback-indicia-connector

Part of the Recorder Feedback system: https://github.com/BiologicalRecordsCentre/recorder-feedback

## Overview

A module which integrates Drupal user profiles with the Recorder Feedback service for Indicia wildlife recording data. https://github.com/BiologicalRecordsCentre/recorder-feedback-controller This module will not function fully without an operational Recorder Feedback Controller App.

This module does the following:
* Adds checkboxes to the user edit or register form which allow the user to select which categories
  of Recorder Feedback emails they whish to receive.
* Handles registration of the user on the Recorder Feedback service and updates their settings.

## Usage

### Installation

Install the Druapal module as normal. Requires: Indicia forms, IForm Inline JS, jQuery UI, jQuer yUI Tabs.
![image](https://github.com/user-attachments/assets/246e89fd-2d82-456b-bf2d-c44cdba83463)

### Configuration

You will be prompted to configure the module, you can also find these settings at `/admin/config/recorder_feedback_indicia_connector/settings`

You are then shown this form where you can configure the module:
![image](https://github.com/user-attachments/assets/f8fadb6c-7f0d-4397-af55-2e43d38693cf)

 * **Prefix for user external keys** - This setting is for if you are connecting multiple recording platforms to the same Controller App. You can provide a short string eg. "iRecord" and this will be added as a prefix the external ID when the users are created in the Controller App.
 * **API key** - This is the API key that you will have set in the Controller App
 * **API URL** - This is the base url for the API endpoints of the Controller App

### Subscribing

Once this has been configured, a user can go to the "edit" tab on their profile. They will then find a tick-list of subscriptions (based on those on the Controller App, if you have not added any to the Controller then nothing will appear here):
![image](https://github.com/user-attachments/assets/5e68fc52-647c-4136-b9a3-350b2dc7e7fa)

They can select which ones they wish to subscribe to and then press save. Please note that it will only actually add the subscriptions to the Controller if you are connected to an Indicia Warehouse and the user has a Indicia Warehouse ID. This is because the warehouse ID is used as the external key.

Users can edit their subscription at any time.




