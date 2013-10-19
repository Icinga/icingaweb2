# Commands

## Abstract

Commands are one important intersection between the monitoring core and the
frontend. This is the writable interface where you can control the core how
checks will be processed. Usually you can interact by buttons in the frontend.

This document describes the URL interface and what commands can be used.

## Configuration

**To be done.**

## URL Interface

The interface offers to options how to deal with commands:

1. Show html forms to enter information about the commands (GET requests)
2. Send commands when providing post data

### Endpoint

Endpoint of commands is specified as follow:

```
http://localhost:8080/icinga2-web/monitoring/command/<name_of_command>
```

### List of commands

To see which commands are support you can supply the **list** argument:

```
http://localhost:8080/icinga2-web/monitoring/command/list
```

### Examples of command urls:

```
# Schedule downtime for an object
http://localhost:8080/icinga2-web/monitoring/command/scheduledowntime

# Provide a new commend for an object
http://localhost:8080/icinga2-web/monitoring/command/addcomment
```

# Provide a global command for host or service checks

http://localhost:8080/icinga2-web/monitoring/command/disableactivechecks?global=host
http://localhost:8080/icinga2-web/monitoring/command/disableactivechecks?global=service

# Provide a object command globally

http://localhost:8080/icinga2-web/monitoring/command/disablenotifications?global=1

## List of commands

*Please note that the list is not complete yet, more commands will follow*

<p></p>

<table>
    <tr>
        <th>Command</th>
        <th>Description</th>
    </tr>
    <tr>
        <td>disableactivechecks</td>
        <td>Disable active checks for an object</td>
    </tr>
    <tr>
        <td>enableactivechecks</td>
        <td>Enable active checks for an object</td>
    </tr>
    <tr>
        <td>reschedulenextcheck</td>
        <td>Reschedule next active check</td>
    </tr>
    <tr>
        <td>submitpassivecheckresult</td>
        <td>Submit a passive result set for this check</td>
    </tr>
    <tr>
        <td>stopobsessing</td>
        <td>Stop obsessing over object</td>
    </tr>
    <tr>
        <td>startobsessing</td>
        <td>Start obsessing over object</td>
    </tr>
    <tr>
        <td>stopacceptingpassivechecks</td>
        <td>Stop accepting passive results for this object</td>
    </tr>
    <tr>
        <td>startacceptingpassivechecks</td>
        <td>Start accepting passive results for this object</td>
    </tr>
    <tr>
        <td>disablenotifications</td>
        <td>Disable sending messages for problems</td>
    </tr>
    <tr>
        <td>enablenotifications</td>
        <td>Enable sending messages for problems</td>
    </tr>
    <tr>
        <td>sendcustomnotification</td>
        <td>Send a custom notification for this object</td>
    </tr>
    <tr>
        <td>scheduledowntime</td>
        <td>Schedule a downtime for this object</td>
    </tr>
    <tr>
        <td>scheduledowntimeswithchildren</td>
        <td>Schedule a downtime for host and all services</td>
    </tr>
    <tr>
        <td>removedowntimeswithchildren</td>
        <td>Remove all downtimes from this host and its services</td>
    </tr>
    <tr>
        <td>disablenotificationswithchildren</td>
        <td>Disable all notification from this host and its services</td>
    </tr>
    <tr>
        <td>enablenotificationswithchildren</td>
        <td>Enable all notification from this host and its services</td>
    </tr>
    <tr>
        <td>reschedulenextcheckwithchildren</td>
        <td>Reschedule next check of host ans its services</td>
    </tr>
    <tr>
        <td>disableactivecheckswithchildren</td>
        <td>Disable all checks of this host and its services</td>
    </tr>
    <tr>
        <td>enableactivecheckswithchildren</td>
        <td>Disable all checks of this host and its services</td>
    </tr>
    <tr>
        <td>disableeventhandler</td>
        <td>Disable event handler for this object</td>
    </tr>
    <tr>
        <td>enableeventhandler</td>
        <td>Disable event handler for this object</td>
    </tr>
    <tr>
        <td>disableflapdetection</td>
        <td>Disable flap detection</td>
    </tr>
    <tr>
        <td>enableflapdetection</td>
        <td>Enable flap detection</td>
    </tr>
    <tr>
        <td>addcomment</td>
        <td>Add a new comment to this object</td>
    </tr>
    <tr>
        <td>resetattributes</td>
        <td>Reset all changed attributes</td>
    </tr>
    <tr>
        <td>acknowledgeproblem</td>
        <td>Acknowledge problem of this object</td>
    </tr>
    <tr>
        <td>removeacknowledgement</td>
        <td>Remove problem acknowledgement</td>
    </tr>
    <tr>
        <td>delaynotification</td>
        <td>Delay next object notification</td>
    </tr>
    <tr>
        <td>removedowntime</td>
        <td>Remove a specific downtime</td>
    </tr>
</table>