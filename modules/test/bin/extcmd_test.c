/*****************************************************************************
 *
 * COMMANDS.C - Extacted comamnd handling from the icinga core, supports testing external
 *              commands without requiring a full running system
 *
 * Copyright (c) 1999-2008 Ethan Galstad (egalstad@nagios.org)
 * Copyright (c) 2009-2012 Nagios Core Development Team and Community Contributors
 * Copyright (c) 2009-2012 Icinga Development Team (http://www.icinga.org)
 *
 * License:
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 *****************************************************************************/
#include "common.h"
#include <stdlib.h>
#include <stdio.h>
#include <string.h>
int dummy;  /* reduce compiler warnings */
/* fix the problem with strtok() skipping empty options between tokens */
char *my_strtok(char *buffer, char *tokens) {
    char *token_position = NULL;
    char *sequence_head = NULL;
    static char *my_strtok_buffer = NULL;
    static char *original_my_strtok_buffer = NULL;

    if (buffer != NULL) {
        my_free(original_my_strtok_buffer);
        if ((my_strtok_buffer = (char *)strdup(buffer)) == NULL)
            return NULL;
        original_my_strtok_buffer = my_strtok_buffer;
    }

    sequence_head = my_strtok_buffer;

    if (sequence_head[0] == '\x0')
        return NULL;

    token_position = strchr(my_strtok_buffer, tokens[0]);

    if (token_position == NULL) {
        my_strtok_buffer = strchr(my_strtok_buffer, '\x0');
        return sequence_head;
    }

    token_position[0] = '\x0';
    my_strtok_buffer = token_position + 1;

    return sequence_head;
}

/* fixes compiler problems under Solaris, since strsep() isn't included */
/* this code is taken from the glibc source */
char *my_strsep(char **stringp, const char *delim) {
    char *begin, *end;

    begin = *stringp;
    if (begin == NULL)
        return NULL;

    /* A frequent case is when the delimiter string contains only one
     * character.  Here we don't need to call the expensive `strpbrk'
     * function and instead work using `strchr'.  */
    if (delim[0] == '\0' || delim[1] == '\0') {
        char ch = delim[0];

        if (ch == '\0' || begin[0] == '\0')
            end = NULL;
        else {
            if (*begin == ch)
                end = begin;
            else
                end = strchr(begin + 1, ch);
        }
    } else {
        /* find the end of the token.  */
        end = strpbrk(begin, delim);
    }

    if (end) {
        /* terminate the token and set *STRINGP past NUL character.  */
        *end++ = '\0';
        *stringp = end;
    } else
        /* no more delimiters; this is the last token.  */
        *stringp = NULL;

    return begin;
}



/* strip newline, carriage return, and tab characters from beginning and end of a string */
void strip(char *buffer) {
    register int x;
    register int y;
    register int z;

    if (buffer == NULL || buffer[0] == '\x0')
        return;

    /* strip end of string */
    y = (int)strlen(buffer);
    for (x = y - 1; x >= 0; x--) {
        if (buffer[x] == ' ' || buffer[x] == '\n' || buffer[x] == '\r' || buffer[x] == '\t' || buffer[x] == 13)
            buffer[x] = '\x0';
        else
            break;
    }

    /* strip beginning of string (by shifting) */
    y = (int)strlen(buffer);
    for (x = 0; x < y; x++) {
        if (buffer[x] == ' ' || buffer[x] == '\n' || buffer[x] == '\r' || buffer[x] == '\t' || buffer[x] == 13)
            continue;
        else
            break;
    }
    if (x > 0) {
        for (z = x; z < y; z++)
            buffer[z-x] = buffer[z];
        buffer[y-x] = '\x0';
    }

    return;
}


/* top-level external command processor */
int main(int argv, char **argc) {
    if(argv < 2) {
        printf("Not enough arguments!\n"); 
        return 1;
    }
    char *cmd = argc[1];
    char *temp_buffer = NULL;
    char *command_id = NULL;
    char *args = NULL;
    time_t entry_time = 0L;
    int command_type = CMD_NONE;
    char *temp_ptr = NULL;

    if (cmd == NULL) {
        printf("No command given\n");
        return ERROR;
    }

    /* strip the command of newlines and carriage returns */
    strip(cmd);

    /* get the command entry time */
    if ((temp_ptr = my_strtok(cmd, "[")) == NULL) {
        printf("No entry time given\n");
        return ERROR;
    }
    if ((temp_ptr = my_strtok(NULL, "]")) == NULL) {
        printf("Missing ] character at entry time\n");
        return ERROR;
    }
    entry_time = (time_t)strtoul(temp_ptr, NULL, 10);

    /* get the command identifier */
    if ((temp_ptr = my_strtok(NULL, ";")) == NULL) {
        printf("Missing command identifier\n");
        return ERROR;
    }
    if ((command_id = (char *)strdup(temp_ptr + 1)) == NULL) {
        printf("Missing command identifier\n");
        return ERROR;
    }

    /* get the command arguments */
    if ((temp_ptr = my_strtok(NULL, "\n")) == NULL)
        args = (char *)strdup("");
    else
        args = (char *)strdup(temp_ptr);

    if (args == NULL) {
        printf("No arguments given\n");
        my_free(command_id);
        return ERROR;
    }

    /* decide what type of command this is... */

    /**************************/
    /**** PROCESS COMMANDS ****/
    /**************************/

    if (!strcmp(command_id, "ENTER_STANDBY_MODE") || !strcmp(command_id, "DISABLE_NOTIFICATIONS"))
        command_type = CMD_DISABLE_NOTIFICATIONS;
    else if (!strcmp(command_id, "ENTER_ACTIVE_MODE") || !strcmp(command_id, "ENABLE_NOTIFICATIONS"))
        command_type = CMD_ENABLE_NOTIFICATIONS;
    else if (!strcmp(command_id, "DISABLE_NOTIFICATIONS_EXPIRE_TIME"))
        command_type = CMD_DISABLE_NOTIFICATIONS_EXPIRE_TIME;

    else if (!strcmp(command_id, "SHUTDOWN_PROGRAM") || !strcmp(command_id, "SHUTDOWN_PROCESS"))
        command_type = CMD_SHUTDOWN_PROCESS;
    else if (!strcmp(command_id, "RESTART_PROGRAM") || !strcmp(command_id, "RESTART_PROCESS"))
        command_type = CMD_RESTART_PROCESS;

    else if (!strcmp(command_id, "SAVE_STATE_INFORMATION"))
        command_type = CMD_SAVE_STATE_INFORMATION;
    else if (!strcmp(command_id, "READ_STATE_INFORMATION"))
        command_type = CMD_READ_STATE_INFORMATION;
    else if (!strcmp(command_id, "SYNC_STATE_INFORMATION"))
        command_type = CMD_SYNC_STATE_INFORMATION;

    else if (!strcmp(command_id, "ENABLE_EVENT_HANDLERS"))
        command_type = CMD_ENABLE_EVENT_HANDLERS;
    else if (!strcmp(command_id, "DISABLE_EVENT_HANDLERS"))
        command_type = CMD_DISABLE_EVENT_HANDLERS;

    else if (!strcmp(command_id, "FLUSH_PENDING_COMMANDS"))
        command_type = CMD_FLUSH_PENDING_COMMANDS;

    else if (!strcmp(command_id, "ENABLE_FAILURE_PREDICTION"))
        command_type = CMD_ENABLE_FAILURE_PREDICTION;
    else if (!strcmp(command_id, "DISABLE_FAILURE_PREDICTION"))
        command_type = CMD_DISABLE_FAILURE_PREDICTION;

    else if (!strcmp(command_id, "ENABLE_PERFORMANCE_DATA"))
        command_type = CMD_ENABLE_PERFORMANCE_DATA;
    else if (!strcmp(command_id, "DISABLE_PERFORMANCE_DATA"))
        command_type = CMD_DISABLE_PERFORMANCE_DATA;

    else if (!strcmp(command_id, "START_EXECUTING_HOST_CHECKS"))
        command_type = CMD_START_EXECUTING_HOST_CHECKS;
    else if (!strcmp(command_id, "STOP_EXECUTING_HOST_CHECKS"))
        command_type = CMD_STOP_EXECUTING_HOST_CHECKS;

    else if (!strcmp(command_id, "START_EXECUTING_SVC_CHECKS"))
        command_type = CMD_START_EXECUTING_SVC_CHECKS;
    else if (!strcmp(command_id, "STOP_EXECUTING_SVC_CHECKS"))
        command_type = CMD_STOP_EXECUTING_SVC_CHECKS;

    else if (!strcmp(command_id, "START_ACCEPTING_PASSIVE_HOST_CHECKS"))
        command_type = CMD_START_ACCEPTING_PASSIVE_HOST_CHECKS;
    else if (!strcmp(command_id, "STOP_ACCEPTING_PASSIVE_HOST_CHECKS"))
        command_type = CMD_STOP_ACCEPTING_PASSIVE_HOST_CHECKS;

    else if (!strcmp(command_id, "START_ACCEPTING_PASSIVE_SVC_CHECKS"))
        command_type = CMD_START_ACCEPTING_PASSIVE_SVC_CHECKS;
    else if (!strcmp(command_id, "STOP_ACCEPTING_PASSIVE_SVC_CHECKS"))
        command_type = CMD_STOP_ACCEPTING_PASSIVE_SVC_CHECKS;

    else if (!strcmp(command_id, "START_OBSESSING_OVER_HOST_CHECKS"))
        command_type = CMD_START_OBSESSING_OVER_HOST_CHECKS;
    else if (!strcmp(command_id, "STOP_OBSESSING_OVER_HOST_CHECKS"))
        command_type = CMD_STOP_OBSESSING_OVER_HOST_CHECKS;

    else if (!strcmp(command_id, "START_OBSESSING_OVER_SVC_CHECKS"))
        command_type = CMD_START_OBSESSING_OVER_SVC_CHECKS;
    else if (!strcmp(command_id, "STOP_OBSESSING_OVER_SVC_CHECKS"))
        command_type = CMD_STOP_OBSESSING_OVER_SVC_CHECKS;

    else if (!strcmp(command_id, "ENABLE_FLAP_DETECTION"))
        command_type = CMD_ENABLE_FLAP_DETECTION;
    else if (!strcmp(command_id, "DISABLE_FLAP_DETECTION"))
        command_type = CMD_DISABLE_FLAP_DETECTION;

    else if (!strcmp(command_id, "CHANGE_GLOBAL_HOST_EVENT_HANDLER"))
        command_type = CMD_CHANGE_GLOBAL_HOST_EVENT_HANDLER;
    else if (!strcmp(command_id, "CHANGE_GLOBAL_SVC_EVENT_HANDLER"))
        command_type = CMD_CHANGE_GLOBAL_SVC_EVENT_HANDLER;

    else if (!strcmp(command_id, "ENABLE_SERVICE_FRESHNESS_CHECKS"))
        command_type = CMD_ENABLE_SERVICE_FRESHNESS_CHECKS;
    else if (!strcmp(command_id, "DISABLE_SERVICE_FRESHNESS_CHECKS"))
        command_type = CMD_DISABLE_SERVICE_FRESHNESS_CHECKS;

    else if (!strcmp(command_id, "ENABLE_HOST_FRESHNESS_CHECKS"))
        command_type = CMD_ENABLE_HOST_FRESHNESS_CHECKS;
    else if (!strcmp(command_id, "DISABLE_HOST_FRESHNESS_CHECKS"))
        command_type = CMD_DISABLE_HOST_FRESHNESS_CHECKS;


    /*******************************/
    /**** HOST-RELATED COMMANDS ****/
    /*******************************/

    else if (!strcmp(command_id, "ADD_HOST_COMMENT"))
        command_type = CMD_ADD_HOST_COMMENT;
    else if (!strcmp(command_id, "DEL_HOST_COMMENT"))
        command_type = CMD_DEL_HOST_COMMENT;
    else if (!strcmp(command_id, "DEL_ALL_HOST_COMMENTS"))
        command_type = CMD_DEL_ALL_HOST_COMMENTS;

    else if (!strcmp(command_id, "DELAY_HOST_NOTIFICATION"))
        command_type = CMD_DELAY_HOST_NOTIFICATION;

    else if (!strcmp(command_id, "ENABLE_HOST_NOTIFICATIONS"))
        command_type = CMD_ENABLE_HOST_NOTIFICATIONS;
    else if (!strcmp(command_id, "DISABLE_HOST_NOTIFICATIONS"))
        command_type = CMD_DISABLE_HOST_NOTIFICATIONS;

    else if (!strcmp(command_id, "ENABLE_ALL_NOTIFICATIONS_BEYOND_HOST"))
        command_type = CMD_ENABLE_ALL_NOTIFICATIONS_BEYOND_HOST;
    else if (!strcmp(command_id, "DISABLE_ALL_NOTIFICATIONS_BEYOND_HOST"))
        command_type = CMD_DISABLE_ALL_NOTIFICATIONS_BEYOND_HOST;

    else if (!strcmp(command_id, "ENABLE_HOST_AND_CHILD_NOTIFICATIONS"))
        command_type = CMD_ENABLE_HOST_AND_CHILD_NOTIFICATIONS;
    else if (!strcmp(command_id, "DISABLE_HOST_AND_CHILD_NOTIFICATIONS"))
        command_type = CMD_DISABLE_HOST_AND_CHILD_NOTIFICATIONS;

    else if (!strcmp(command_id, "ENABLE_HOST_SVC_NOTIFICATIONS"))
        command_type = CMD_ENABLE_HOST_SVC_NOTIFICATIONS;
    else if (!strcmp(command_id, "DISABLE_HOST_SVC_NOTIFICATIONS"))
        command_type = CMD_DISABLE_HOST_SVC_NOTIFICATIONS;

    else if (!strcmp(command_id, "ENABLE_HOST_SVC_CHECKS"))
        command_type = CMD_ENABLE_HOST_SVC_CHECKS;
    else if (!strcmp(command_id, "DISABLE_HOST_SVC_CHECKS"))
        command_type = CMD_DISABLE_HOST_SVC_CHECKS;

    else if (!strcmp(command_id, "ENABLE_PASSIVE_HOST_CHECKS"))
        command_type = CMD_ENABLE_PASSIVE_HOST_CHECKS;
    else if (!strcmp(command_id, "DISABLE_PASSIVE_HOST_CHECKS"))
        command_type = CMD_DISABLE_PASSIVE_HOST_CHECKS;

    else if (!strcmp(command_id, "SCHEDULE_HOST_SVC_CHECKS"))
        command_type = CMD_SCHEDULE_HOST_SVC_CHECKS;
    else if (!strcmp(command_id, "SCHEDULE_FORCED_HOST_SVC_CHECKS"))
        command_type = CMD_SCHEDULE_FORCED_HOST_SVC_CHECKS;

    else if (!strcmp(command_id, "ACKNOWLEDGE_HOST_PROBLEM"))
        command_type = CMD_ACKNOWLEDGE_HOST_PROBLEM;
    else if (!strcmp(command_id, "ACKNOWLEDGE_HOST_PROBLEM_EXPIRE"))
        command_type = CMD_ACKNOWLEDGE_HOST_PROBLEM_EXPIRE;
    else if (!strcmp(command_id, "REMOVE_HOST_ACKNOWLEDGEMENT"))
        command_type = CMD_REMOVE_HOST_ACKNOWLEDGEMENT;

    else if (!strcmp(command_id, "ENABLE_HOST_EVENT_HANDLER"))
        command_type = CMD_ENABLE_HOST_EVENT_HANDLER;
    else if (!strcmp(command_id, "DISABLE_HOST_EVENT_HANDLER"))
        command_type = CMD_DISABLE_HOST_EVENT_HANDLER;

    else if (!strcmp(command_id, "ENABLE_HOST_CHECK"))
        command_type = CMD_ENABLE_HOST_CHECK;
    else if (!strcmp(command_id, "DISABLE_HOST_CHECK"))
        command_type = CMD_DISABLE_HOST_CHECK;

    else if (!strcmp(command_id, "SCHEDULE_HOST_CHECK"))
        command_type = CMD_SCHEDULE_HOST_CHECK;
    else if (!strcmp(command_id, "SCHEDULE_FORCED_HOST_CHECK"))
        command_type = CMD_SCHEDULE_FORCED_HOST_CHECK;

    else if (!strcmp(command_id, "SCHEDULE_HOST_DOWNTIME"))
        command_type = CMD_SCHEDULE_HOST_DOWNTIME;
    else if (!strcmp(command_id, "SCHEDULE_HOST_SVC_DOWNTIME"))
        command_type = CMD_SCHEDULE_HOST_SVC_DOWNTIME;
    else if (!strcmp(command_id, "DEL_HOST_DOWNTIME"))
        command_type = CMD_DEL_HOST_DOWNTIME;
    else if (!strcmp(command_id, "DEL_DOWNTIME_BY_HOST_NAME"))
        command_type = CMD_DEL_DOWNTIME_BY_HOST_NAME;
    else if (!strcmp(command_id, "DEL_DOWNTIME_BY_HOSTGROUP_NAME"))
        command_type = CMD_DEL_DOWNTIME_BY_HOSTGROUP_NAME;

    else if (!strcmp(command_id, "DEL_DOWNTIME_BY_START_TIME_COMMENT"))
        command_type = CMD_DEL_DOWNTIME_BY_START_TIME_COMMENT;

    else if (!strcmp(command_id, "ENABLE_HOST_FLAP_DETECTION"))
        command_type = CMD_ENABLE_HOST_FLAP_DETECTION;
    else if (!strcmp(command_id, "DISABLE_HOST_FLAP_DETECTION"))
        command_type = CMD_DISABLE_HOST_FLAP_DETECTION;

    else if (!strcmp(command_id, "START_OBSESSING_OVER_HOST"))
        command_type = CMD_START_OBSESSING_OVER_HOST;
    else if (!strcmp(command_id, "STOP_OBSESSING_OVER_HOST"))
        command_type = CMD_STOP_OBSESSING_OVER_HOST;

    else if (!strcmp(command_id, "CHANGE_HOST_EVENT_HANDLER"))
        command_type = CMD_CHANGE_HOST_EVENT_HANDLER;
    else if (!strcmp(command_id, "CHANGE_HOST_CHECK_COMMAND"))
        command_type = CMD_CHANGE_HOST_CHECK_COMMAND;

    else if (!strcmp(command_id, "CHANGE_NORMAL_HOST_CHECK_INTERVAL"))
        command_type = CMD_CHANGE_NORMAL_HOST_CHECK_INTERVAL;
    else if (!strcmp(command_id, "CHANGE_RETRY_HOST_CHECK_INTERVAL"))
        command_type = CMD_CHANGE_RETRY_HOST_CHECK_INTERVAL;

    else if (!strcmp(command_id, "CHANGE_MAX_HOST_CHECK_ATTEMPTS"))
        command_type = CMD_CHANGE_MAX_HOST_CHECK_ATTEMPTS;

    else if (!strcmp(command_id, "SCHEDULE_AND_PROPAGATE_TRIGGERED_HOST_DOWNTIME"))
        command_type = CMD_SCHEDULE_AND_PROPAGATE_TRIGGERED_HOST_DOWNTIME;

    else if (!strcmp(command_id, "SCHEDULE_AND_PROPAGATE_HOST_DOWNTIME"))
        command_type = CMD_SCHEDULE_AND_PROPAGATE_HOST_DOWNTIME;

    else if (!strcmp(command_id, "SET_HOST_NOTIFICATION_NUMBER"))
        command_type = CMD_SET_HOST_NOTIFICATION_NUMBER;

    else if (!strcmp(command_id, "CHANGE_HOST_CHECK_TIMEPERIOD"))
        command_type = CMD_CHANGE_HOST_CHECK_TIMEPERIOD;

    else if (!strcmp(command_id, "CHANGE_CUSTOM_HOST_VAR"))
        command_type = CMD_CHANGE_CUSTOM_HOST_VAR;

    else if (!strcmp(command_id, "SEND_CUSTOM_HOST_NOTIFICATION"))
        command_type = CMD_SEND_CUSTOM_HOST_NOTIFICATION;

    else if (!strcmp(command_id, "CHANGE_HOST_NOTIFICATION_TIMEPERIOD"))
        command_type = CMD_CHANGE_HOST_NOTIFICATION_TIMEPERIOD;

    else if (!strcmp(command_id, "CHANGE_HOST_MODATTR"))
        command_type = CMD_CHANGE_HOST_MODATTR;


    /************************************/
    /**** HOSTGROUP-RELATED COMMANDS ****/
    /************************************/

    else if (!strcmp(command_id, "ENABLE_HOSTGROUP_HOST_NOTIFICATIONS"))
        command_type = CMD_ENABLE_HOSTGROUP_HOST_NOTIFICATIONS;
    else if (!strcmp(command_id, "DISABLE_HOSTGROUP_HOST_NOTIFICATIONS"))
        command_type = CMD_DISABLE_HOSTGROUP_HOST_NOTIFICATIONS;

    else if (!strcmp(command_id, "ENABLE_HOSTGROUP_SVC_NOTIFICATIONS"))
        command_type = CMD_ENABLE_HOSTGROUP_SVC_NOTIFICATIONS;
    else if (!strcmp(command_id, "DISABLE_HOSTGROUP_SVC_NOTIFICATIONS"))
        command_type = CMD_DISABLE_HOSTGROUP_SVC_NOTIFICATIONS;

    else if (!strcmp(command_id, "ENABLE_HOSTGROUP_HOST_CHECKS"))
        command_type = CMD_ENABLE_HOSTGROUP_HOST_CHECKS;
    else if (!strcmp(command_id, "DISABLE_HOSTGROUP_HOST_CHECKS"))
        command_type = CMD_DISABLE_HOSTGROUP_HOST_CHECKS;

    else if (!strcmp(command_id, "ENABLE_HOSTGROUP_PASSIVE_HOST_CHECKS"))
        command_type = CMD_ENABLE_HOSTGROUP_PASSIVE_HOST_CHECKS;
    else if (!strcmp(command_id, "DISABLE_HOSTGROUP_PASSIVE_HOST_CHECKS"))
        command_type = CMD_DISABLE_HOSTGROUP_PASSIVE_HOST_CHECKS;

    else if (!strcmp(command_id, "ENABLE_HOSTGROUP_SVC_CHECKS"))
        command_type = CMD_ENABLE_HOSTGROUP_SVC_CHECKS;
    else if (!strcmp(command_id, "DISABLE_HOSTGROUP_SVC_CHECKS"))
        command_type = CMD_DISABLE_HOSTGROUP_SVC_CHECKS;

    else if (!strcmp(command_id, "ENABLE_HOSTGROUP_PASSIVE_SVC_CHECKS"))
        command_type = CMD_ENABLE_HOSTGROUP_PASSIVE_SVC_CHECKS;
    else if (!strcmp(command_id, "DISABLE_HOSTGROUP_PASSIVE_SVC_CHECKS"))
        command_type = CMD_DISABLE_HOSTGROUP_PASSIVE_SVC_CHECKS;

    else if (!strcmp(command_id, "SCHEDULE_HOSTGROUP_HOST_DOWNTIME"))
        command_type = CMD_SCHEDULE_HOSTGROUP_HOST_DOWNTIME;
    else if (!strcmp(command_id, "SCHEDULE_HOSTGROUP_SVC_DOWNTIME"))
        command_type = CMD_SCHEDULE_HOSTGROUP_SVC_DOWNTIME;


    /**********************************/
    /**** SERVICE-RELATED COMMANDS ****/
    /**********************************/

    else if (!strcmp(command_id, "ADD_SVC_COMMENT"))
        command_type = CMD_ADD_SVC_COMMENT;
    else if (!strcmp(command_id, "DEL_SVC_COMMENT"))
        command_type = CMD_DEL_SVC_COMMENT;
    else if (!strcmp(command_id, "DEL_ALL_SVC_COMMENTS"))
        command_type = CMD_DEL_ALL_SVC_COMMENTS;

    else if (!strcmp(command_id, "SCHEDULE_SVC_CHECK"))
        command_type = CMD_SCHEDULE_SVC_CHECK;
    else if (!strcmp(command_id, "SCHEDULE_FORCED_SVC_CHECK"))
        command_type = CMD_SCHEDULE_FORCED_SVC_CHECK;

    else if (!strcmp(command_id, "ENABLE_SVC_CHECK"))
        command_type = CMD_ENABLE_SVC_CHECK;
    else if (!strcmp(command_id, "DISABLE_SVC_CHECK"))
        command_type = CMD_DISABLE_SVC_CHECK;

    else if (!strcmp(command_id, "ENABLE_PASSIVE_SVC_CHECKS"))
        command_type = CMD_ENABLE_PASSIVE_SVC_CHECKS;
    else if (!strcmp(command_id, "DISABLE_PASSIVE_SVC_CHECKS"))
        command_type = CMD_DISABLE_PASSIVE_SVC_CHECKS;

    else if (!strcmp(command_id, "DELAY_SVC_NOTIFICATION"))
        command_type = CMD_DELAY_SVC_NOTIFICATION;
    else if (!strcmp(command_id, "ENABLE_SVC_NOTIFICATIONS"))
        command_type = CMD_ENABLE_SVC_NOTIFICATIONS;
    else if (!strcmp(command_id, "DISABLE_SVC_NOTIFICATIONS"))
        command_type = CMD_DISABLE_SVC_NOTIFICATIONS;

    else if (!strcmp(command_id, "PROCESS_SERVICE_CHECK_RESULT"))
        command_type = CMD_PROCESS_SERVICE_CHECK_RESULT;
    else if (!strcmp(command_id, "PROCESS_HOST_CHECK_RESULT"))
        command_type = CMD_PROCESS_HOST_CHECK_RESULT;

    else if (!strcmp(command_id, "ENABLE_SVC_EVENT_HANDLER"))
        command_type = CMD_ENABLE_SVC_EVENT_HANDLER;
    else if (!strcmp(command_id, "DISABLE_SVC_EVENT_HANDLER"))
        command_type = CMD_DISABLE_SVC_EVENT_HANDLER;

    else if (!strcmp(command_id, "ENABLE_SVC_FLAP_DETECTION"))
        command_type = CMD_ENABLE_SVC_FLAP_DETECTION;
    else if (!strcmp(command_id, "DISABLE_SVC_FLAP_DETECTION"))
        command_type = CMD_DISABLE_SVC_FLAP_DETECTION;

    else if (!strcmp(command_id, "SCHEDULE_SVC_DOWNTIME"))
        command_type = CMD_SCHEDULE_SVC_DOWNTIME;
    else if (!strcmp(command_id, "DEL_SVC_DOWNTIME"))
        command_type = CMD_DEL_SVC_DOWNTIME;

    else if (!strcmp(command_id, "ACKNOWLEDGE_SVC_PROBLEM"))
        command_type = CMD_ACKNOWLEDGE_SVC_PROBLEM;
    else if (!strcmp(command_id, "ACKNOWLEDGE_SVC_PROBLEM_EXPIRE"))
        command_type = CMD_ACKNOWLEDGE_SVC_PROBLEM_EXPIRE;
    else if (!strcmp(command_id, "REMOVE_SVC_ACKNOWLEDGEMENT"))
        command_type = CMD_REMOVE_SVC_ACKNOWLEDGEMENT;

    else if (!strcmp(command_id, "START_OBSESSING_OVER_SVC"))
        command_type = CMD_START_OBSESSING_OVER_SVC;
    else if (!strcmp(command_id, "STOP_OBSESSING_OVER_SVC"))
        command_type = CMD_STOP_OBSESSING_OVER_SVC;

    else if (!strcmp(command_id, "CHANGE_SVC_EVENT_HANDLER"))
        command_type = CMD_CHANGE_SVC_EVENT_HANDLER;
    else if (!strcmp(command_id, "CHANGE_SVC_CHECK_COMMAND"))
        command_type = CMD_CHANGE_SVC_CHECK_COMMAND;

    else if (!strcmp(command_id, "CHANGE_NORMAL_SVC_CHECK_INTERVAL"))
        command_type = CMD_CHANGE_NORMAL_SVC_CHECK_INTERVAL;
    else if (!strcmp(command_id, "CHANGE_RETRY_SVC_CHECK_INTERVAL"))
        command_type = CMD_CHANGE_RETRY_SVC_CHECK_INTERVAL;

    else if (!strcmp(command_id, "CHANGE_MAX_SVC_CHECK_ATTEMPTS"))
        command_type = CMD_CHANGE_MAX_SVC_CHECK_ATTEMPTS;

    else if (!strcmp(command_id, "SET_SVC_NOTIFICATION_NUMBER"))
        command_type = CMD_SET_SVC_NOTIFICATION_NUMBER;

    else if (!strcmp(command_id, "CHANGE_SVC_CHECK_TIMEPERIOD"))
        command_type = CMD_CHANGE_SVC_CHECK_TIMEPERIOD;

    else if (!strcmp(command_id, "CHANGE_CUSTOM_SVC_VAR"))
        command_type = CMD_CHANGE_CUSTOM_SVC_VAR;

    else if (!strcmp(command_id, "CHANGE_CUSTOM_CONTACT_VAR"))
        command_type = CMD_CHANGE_CUSTOM_CONTACT_VAR;

    else if (!strcmp(command_id, "SEND_CUSTOM_SVC_NOTIFICATION"))
        command_type = CMD_SEND_CUSTOM_SVC_NOTIFICATION;

    else if (!strcmp(command_id, "CHANGE_SVC_NOTIFICATION_TIMEPERIOD"))
        command_type = CMD_CHANGE_SVC_NOTIFICATION_TIMEPERIOD;

    else if (!strcmp(command_id, "CHANGE_SVC_MODATTR"))
        command_type = CMD_CHANGE_SVC_MODATTR;


    /***************************************/
    /**** SERVICEGROUP-RELATED COMMANDS ****/
    /***************************************/

    else if (!strcmp(command_id, "ENABLE_SERVICEGROUP_HOST_NOTIFICATIONS"))
        command_type = CMD_ENABLE_SERVICEGROUP_HOST_NOTIFICATIONS;
    else if (!strcmp(command_id, "DISABLE_SERVICEGROUP_HOST_NOTIFICATIONS"))
        command_type = CMD_DISABLE_SERVICEGROUP_HOST_NOTIFICATIONS;

    else if (!strcmp(command_id, "ENABLE_SERVICEGROUP_SVC_NOTIFICATIONS"))
        command_type = CMD_ENABLE_SERVICEGROUP_SVC_NOTIFICATIONS;
    else if (!strcmp(command_id, "DISABLE_SERVICEGROUP_SVC_NOTIFICATIONS"))
        command_type = CMD_DISABLE_SERVICEGROUP_SVC_NOTIFICATIONS;

    else if (!strcmp(command_id, "ENABLE_SERVICEGROUP_HOST_CHECKS"))
        command_type = CMD_ENABLE_SERVICEGROUP_HOST_CHECKS;
    else if (!strcmp(command_id, "DISABLE_SERVICEGROUP_HOST_CHECKS"))
        command_type = CMD_DISABLE_SERVICEGROUP_HOST_CHECKS;

    else if (!strcmp(command_id, "ENABLE_SERVICEGROUP_PASSIVE_HOST_CHECKS"))
        command_type = CMD_ENABLE_SERVICEGROUP_PASSIVE_HOST_CHECKS;
    else if (!strcmp(command_id, "DISABLE_SERVICEGROUP_PASSIVE_HOST_CHECKS"))
        command_type = CMD_DISABLE_SERVICEGROUP_PASSIVE_HOST_CHECKS;

    else if (!strcmp(command_id, "ENABLE_SERVICEGROUP_SVC_CHECKS"))
        command_type = CMD_ENABLE_SERVICEGROUP_SVC_CHECKS;
    else if (!strcmp(command_id, "DISABLE_SERVICEGROUP_SVC_CHECKS"))
        command_type = CMD_DISABLE_SERVICEGROUP_SVC_CHECKS;

    else if (!strcmp(command_id, "ENABLE_SERVICEGROUP_PASSIVE_SVC_CHECKS"))
        command_type = CMD_ENABLE_SERVICEGROUP_PASSIVE_SVC_CHECKS;
    else if (!strcmp(command_id, "DISABLE_SERVICEGROUP_PASSIVE_SVC_CHECKS"))
        command_type = CMD_DISABLE_SERVICEGROUP_PASSIVE_SVC_CHECKS;

    else if (!strcmp(command_id, "SCHEDULE_SERVICEGROUP_HOST_DOWNTIME"))
        command_type = CMD_SCHEDULE_SERVICEGROUP_HOST_DOWNTIME;
    else if (!strcmp(command_id, "SCHEDULE_SERVICEGROUP_SVC_DOWNTIME"))
        command_type = CMD_SCHEDULE_SERVICEGROUP_SVC_DOWNTIME;


    /**********************************/
    /**** CONTACT-RELATED COMMANDS ****/
    /**********************************/

    else if (!strcmp(command_id, "ENABLE_CONTACT_HOST_NOTIFICATIONS"))
        command_type = CMD_ENABLE_CONTACT_HOST_NOTIFICATIONS;
    else if (!strcmp(command_id, "DISABLE_CONTACT_HOST_NOTIFICATIONS"))
        command_type = CMD_DISABLE_CONTACT_HOST_NOTIFICATIONS;

    else if (!strcmp(command_id, "ENABLE_CONTACT_SVC_NOTIFICATIONS"))
        command_type = CMD_ENABLE_CONTACT_SVC_NOTIFICATIONS;
    else if (!strcmp(command_id, "DISABLE_CONTACT_SVC_NOTIFICATIONS"))
        command_type = CMD_DISABLE_CONTACT_SVC_NOTIFICATIONS;

    else if (!strcmp(command_id, "CHANGE_CONTACT_HOST_NOTIFICATION_TIMEPERIOD"))
        command_type = CMD_CHANGE_CONTACT_HOST_NOTIFICATION_TIMEPERIOD;

    else if (!strcmp(command_id, "CHANGE_CONTACT_SVC_NOTIFICATION_TIMEPERIOD"))
        command_type = CMD_CHANGE_CONTACT_SVC_NOTIFICATION_TIMEPERIOD;

    else if (!strcmp(command_id, "CHANGE_CONTACT_MODATTR"))
        command_type = CMD_CHANGE_CONTACT_MODATTR;
    else if (!strcmp(command_id, "CHANGE_CONTACT_MODHATTR"))
        command_type = CMD_CHANGE_CONTACT_MODHATTR;
    else if (!strcmp(command_id, "CHANGE_CONTACT_MODSATTR"))
        command_type = CMD_CHANGE_CONTACT_MODSATTR;

    /***************************************/
    /**** CONTACTGROUP-RELATED COMMANDS ****/
    /***************************************/

    else if (!strcmp(command_id, "ENABLE_CONTACTGROUP_HOST_NOTIFICATIONS"))
        command_type = CMD_ENABLE_CONTACTGROUP_HOST_NOTIFICATIONS;
    else if (!strcmp(command_id, "DISABLE_CONTACTGROUP_HOST_NOTIFICATIONS"))
        command_type = CMD_DISABLE_CONTACTGROUP_HOST_NOTIFICATIONS;

    else if (!strcmp(command_id, "ENABLE_CONTACTGROUP_SVC_NOTIFICATIONS"))
        command_type = CMD_ENABLE_CONTACTGROUP_SVC_NOTIFICATIONS;
    else if (!strcmp(command_id, "DISABLE_CONTACTGROUP_SVC_NOTIFICATIONS"))
        command_type = CMD_DISABLE_CONTACTGROUP_SVC_NOTIFICATIONS;


    /**************************/
    /****** MISC COMMANDS *****/
    /**************************/

    else if (!strcmp(command_id, "PROCESS_FILE"))
        command_type = CMD_PROCESS_FILE;



    /****************************/
    /****** CUSTOM COMMANDS *****/
    /****************************/

    else if (command_id[0] == '_')
        command_type = CMD_CUSTOM_COMMAND;



    /**** UNKNOWN COMMAND ****/
    else {
        /* free memory */
        printf("UNKNOWN COMMAND: %s;%s\n", command_id, args);
        my_free(command_id);
        my_free(args);

        return ERROR;
    }


    /* log the external command */
    printf("%s;%s\n", command_id, args);
    my_free(temp_buffer);

    my_free(command_id);
    my_free(args);

    return OK;
}



