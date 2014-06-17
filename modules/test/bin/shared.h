/*****************************************************************************
 *
 * SHARED.H - Include file for shared functions and structs
 *
 * Copyright (c) 2010-2011 Nagios Core Development Team and Community Contributors
 * Copyright (c) 2010-2011 Icinga Development Team (http://www.icinga.org)
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


#ifndef INCLUDE__shared_h__
#define INCLUDE__shared_h__

#include <time.h>
/* mmapfile structure - used for reading files via mmap() */
typedef struct mmapfile_struct {
	char *path;
	int mode;
	int fd;
	unsigned long file_size;
	unsigned long current_position;
	unsigned long current_line;
	void *mmap_buf;
} mmapfile;

/* only usable on compile-time initialized arrays, for obvious reasons */
#define ARRAY_SIZE(ary) (sizeof(ary) / sizeof(ary[0]))

extern char *my_strtok(char *buffer, char *tokens);
extern char *my_strsep(char **stringp, const char *delim);
extern mmapfile *mmap_fopen(char *filename);
extern int mmap_fclose(mmapfile *temp_mmapfile);
extern char *mmap_fgets(mmapfile *temp_mmapfile);
extern char *mmap_fgets_multiline(mmapfile * temp_mmapfile);
extern void strip(char *buffer);
extern int hashfunc(const char *name1, const char *name2, int hashslots);
extern int compare_hashdata(const char *val1a, const char *val1b, const char *val2a,
			    const char *val2b);
extern void get_datetime_string(time_t *raw_time, char *buffer,
				int buffer_length, int type);
extern void get_time_breakdown(unsigned long raw_time, int *days, int *hours,
				   int *minutes, int *seconds);
#endif

