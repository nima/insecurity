#include <stdio.h>
#include <sys/stat.h>
#include <stdlib.h>
#include <string.h>
#include <ctype.h>

/* From bash-3.2 / general.h / line 228 */
#define FS_EXISTS         0x1
#define FS_EXECABLE       0x2
#define FS_EXEC_PREFERRED 0x4
#define FS_EXEC_ONLY      0x8
#define FS_DIRECTORY      0x10
#define FS_NODIRS         0x20
#define FS_READABLE       0x40

/* These are non-standard, but are used in builtins.c$symbolic_umask() */
#define S_IRUGO		(S_IRUSR | S_IRGRP | S_IROTH)
#define S_IWUGO		(S_IWUSR | S_IWGRP | S_IWOTH)
#define S_IXUGO		(S_IXUSR | S_IXGRP | S_IXOTH)


char *extract_colon_unit(char const *string, int *p_index) {
    int i, start, len;

    char *value;

    if(string == 0)
        return NULL;

    len = strlen(string);
    if(*p_index >= len)
        return ((char *)NULL);

    i = *p_index;

    /* Each call to this routine leaves the index pointing at a colon if
     * there is more to the path.  If I is > 0, then increment past the
     * `:'.  If I is 0, then the path has a leading colon.  Trailing colons
     * are handled OK by the `else' part of the if statement; an empty
     * string is returned in that case. */
    if(i && string[i] == ':') i++;

    for(start = i; string[i] && string[i] != ':'; i++);
    *p_index = i;

    if(i == start) {
        if(string[i])
            (*p_index)++;
        /* Return "" in the case of a trailing `:'. */
        value = malloc(1);
        value[0] = '\0';
    } else {
        int end = i;
        register int len;
        len = end - start;
        value = malloc(len + 1);
        strncpy(value, string + start, len);
        value[len] = '\0';
    }

    return (value);
}

/* From bash-3.2 / shell.h / line 105 */

/* Information about the current user. */
struct user_info {
    uid_t uid, euid;
    gid_t gid, egid;
    char *user_name;
    char *shell;                /* shell from the password file */
    char *home_dir;
};

/* From bash-3.2 / shell.c / line 111 */

static gid_t *group_array = (gid_t *)NULL;
/* Information about the current user. */
struct user_info current_user = {
    (uid_t) - 1, (uid_t) - 1, (gid_t) - 1, (gid_t) - 1,
    (char *)NULL, (char *)NULL, (char *)NULL
};

static int ngroups, maxgroups;
/* From bash-3.2 / general.c / line 879 */
static void initialize_group_array () {
  register int i;

  if (maxgroups == 0)
    maxgroups = getmaxgroups ();

  ngroups = 0;
  group_array = (gid_t *)realloc (group_array, maxgroups * sizeof (gid_t));

  ngroups = getgroups (maxgroups, group_array);

  /* If getgroups returns nothing, or the OS does not support getgroups(),
     make sure the groups array includes at least the current gid. */
  if (ngroups == 0)
    {
      group_array[0] = current_user.gid;
      ngroups = 1;
    }

  /* If the primary group is not in the groups array, add it as group_array[0]
     and shuffle everything else up 1, if there's room. */
  for (i = 0; i < ngroups; i++)
    if (current_user.gid == (gid_t)group_array[i])
      break;
  if (i == ngroups && ngroups < maxgroups) {
      for (i = ngroups; i > 0; i--)
        group_array[i] = group_array[i - 1];
      group_array[0] = current_user.gid;
      ngroups++;
    }

  /* If the primary group is not group_array[0], swap group_array[0] and
     whatever the current group is.  The vast majority of systems should
     not need this; a notable exception is Linux. */
  if (group_array[0] != current_user.gid) {
      for (i = 0; i < ngroups; i++)
        if (group_array[i] == current_user.gid)
          break;
      if (i < ngroups) {
          group_array[i] = group_array[0];
          group_array[0] = current_user.gid;
        }
    }
}
int group_member(gid_t gid) {
  register int i;

  /* Short-circuit if possible, maybe saving a call to getgroups(). */
  if (gid == current_user.gid || gid == current_user.egid)
    return (1);

  if (ngroups == 0)
    initialize_group_array ();

  /* In case of error, the user loses. */
  if (ngroups <= 0)
    return (0);

  /* Search through the list looking for GID. */
  for (i = 0; i < ngroups; i++)
    if (gid == (gid_t)group_array[i])
      return (1);

  return (0);
}


int file_status(char const *name) {
    struct stat finfo;

    int r;

    /* Determine whether this file exists or not. */
    if(stat(name, &finfo) < 0)
        return (0);

    /* If the file is a directory, then it is not "executable" in the
     * sense of the shell. */
    if(S_ISDIR(finfo.st_mode))
        return (FS_EXISTS | FS_DIRECTORY);

    r = FS_EXISTS;

    /* Find out if the file is actually executable.  By definition, the
     * only other criteria is that the file has an execute bit set that
     * we can use.  The same with whether or not a file is readable. */

    /* Root only requires execute permission for any of owner, group or
     * others to be able to exec a file, and can read any file. */
    if(current_user.euid == (uid_t) 0) {
        r |= FS_READABLE;
        if(finfo.st_mode & S_IXUGO)
            r |= FS_EXECABLE;
        return r;
    }

    if(current_user.euid == finfo.st_uid) {
        /* If we are the owner of the file, the owner bits apply. */
        if(finfo.st_mode & S_IXUSR)
            r |= FS_EXECABLE;
        if(finfo.st_mode & S_IRUSR)
            r |= FS_READABLE;
    } else if(group_member(finfo.st_gid)) {
        /* If we are in the owning group, the group permissions apply. */
        if(finfo.st_mode & S_IXGRP)
            r |= FS_EXECABLE;
        if(finfo.st_mode & S_IRGRP)
            r |= FS_READABLE;
    } else {
        /* Else we check whether `others' have permission to execute the file */
        if(finfo.st_mode & S_IXOTH)
            r |= FS_EXECABLE;
        if(finfo.st_mode & S_IROTH)
            r |= FS_READABLE;
    }

    return r;
}

/* From bash-3.2 / general.h / line 69 */
#define savestring(x) strcpy(malloc(1 + strlen (x)), (x))

extern int file_status(const char *name);

#define absolute_program(string) ((char *)strchr(string, '/') ? 1 : 0)

char *get_next_path_element(char const *path_list, int *path_index_pointer) {
    char *path;

    path = extract_colon_unit(path_list, path_index_pointer);

    if(path == 0)
        return (path);

    if(*path == '\0') {
        free(path);
        path = savestring(".");
    }

    return (path);
}

char *make_full_pathname(const char *path, const char *name, int name_len) {
    char *full_path;

    int path_len;

    path_len = strlen(path);
    full_path = malloc(2 + path_len + name_len);
    strcpy(full_path, path);
    full_path[path_len] = '/';
    strcpy(full_path + path_len + 1, name);
    return (full_path);
}

#define FREE(s)  do { if (s) free (s); } while (0)

/******************************************************************************/

static const char *progname;

static char home[256];

static size_t homelen = 0;

static int absolute_path_given;

static int found_path_starts_with_dot;

static char *abs_path;

static char *find_command_in_path(const char *name, const char *path_list,
    int *path_index) {
    char *found = NULL, *full_path;

    int status, name_len;

    name_len = strlen(name);

    if(!absolute_program(name))
        absolute_path_given = 0;
    else {
        char *p;

        absolute_path_given = 1;

        if(abs_path)
            free(abs_path);

        if(*name != '.' && *name != '/' && *name != '~') {
            abs_path = malloc(3 + name_len);
            strcpy(abs_path, "./");
            strcat(abs_path, name);
        } else {
            abs_path = malloc(1 + name_len);
            strcpy(abs_path, name);
        }

        path_list = abs_path;
        p = strrchr(abs_path, '/');
        *p++ = 0;
        name = p;
    }

    while(path_list && path_list[*path_index]) {
        char *path;

        if(absolute_path_given) {
            path = savestring(path_list);
            *path_index = strlen(path);
        } else
            path = get_next_path_element(path_list, path_index);

        if(!path)
            break;

        found_path_starts_with_dot = (*path == '.');

        full_path = make_full_pathname(path, name, name_len);
        free(path);

        status = file_status(full_path);

        if((status & FS_EXISTS) && (status & FS_EXECABLE)) {
            found = full_path;
            break;
        }

        free(full_path);
    }

    return (found);
}

static char cwd[256];

static size_t cwdlen;

static void get_current_working_directory(void) {
    if(cwdlen)
        return;

    if(!getcwd(cwd, sizeof(cwd))) {
        const char *pwd = getenv("PWD");

        if(pwd && strlen(pwd) < sizeof(cwd))
            strcpy(cwd, pwd);
    }

    if(*cwd != '/') {
        fprintf(stderr, "Can't get current working directory\n");
        exit(-1);
    }

    cwdlen = strlen(cwd);

    if(cwd[cwdlen - 1] != '/') {
        cwd[cwdlen++] = '/';
        cwd[cwdlen] = 0;
    }
}

static char *path_clean_up(const char *path) {
    static char result[256];

    const char *p1 = path;

    char *p2 = result;

    int saw_slash = 0, saw_slash_dot = 0, saw_slash_dot_dot = 0;

    if(*p1 != '/') {
        get_current_working_directory();
        strcpy(result, cwd);
        saw_slash = 1;
        p2 = &result[cwdlen];
    }

    do {
        /*
         * Two leading slashes are allowed, having an OS implementation-defined meaning.
         * See http://www.opengroup.org/onlinepubs/009695399/basedefs/xbd_chap04.html#tag_04_11
         */
        if(!saw_slash || *p1 != '/' || (p1 == path + 1 && p1[1] != '/'))
            *p2++ = *p1;
        if(saw_slash_dot && (*p1 == '/'))
            p2 -= 2;
        if(saw_slash_dot_dot && (*p1 == '/')) {
            int cnt = 0;

            do {
                if(--p2 < result) {
                    strcpy(result, path);
                    return result;
                }
                if(*p2 == '/')
                    ++cnt;
            }
            while(cnt != 3);
            ++p2;
        }
        saw_slash_dot_dot = saw_slash_dot && (*p1 == '.');
        saw_slash_dot = saw_slash && (*p1 == '.');
        saw_slash = (*p1 == '/');
    }
    while(*p1++);

    return result;
}

struct function_st {
    char *name;
    size_t len;
    char **lines;
    int line_count;
};

int path_search(int indent, const char *cmd, const char *path_list) {
    char *result = NULL;

    int found_something = 0;

    if(path_list && *path_list != '\0') {
        int next;

        int path_index = 0;

        do {
            next = 0;
            result = find_command_in_path(cmd, path_list, &path_index);
            if(result) {
                const char *full_path = path_clean_up(result);

                fprintf(stdout, "%s\n", full_path);
                free(result);
                found_something = 1;
            } else
                break;
        }
        while(next);
    }

    return found_something;
}

int main(int argc, char *argv[]) {
    const char *path_list = getenv("PATH");
    progname = argv[0];
    path_search(0, *argv, path_list);
    return 0;
}
