AC_DEFUN([AC_USER_GUESS],[
   $2=$3
   for x in $1; do
    AC_MSG_CHECKING([if user $x exists])
     AS_IF([ $GREP -q "^$x:" /etc/passwd ],
           [ AC_MSG_RESULT([found]); $2=$x ; break],
           [ AC_MSG_RESULT([not found]) ])
   done
  ])

AC_DEFUN([AC_CHECK_PHP_MODULE],[
  for x in $1;do
     AC_MSG_CHECKING([if php has $x module])
     AS_IF([ php -m | $GREP -iq "^$x$" ],
            [ AC_MSG_RESULT([found]) ],
            [ AC_MSG_ERROR([not found])])
  done
])

AC_DEFUN([AC_CHECK_PHP_VERSION],[
  AC_MSG_CHECKING([if php has at least version $1.$2.$3])
  AS_IF([   test $1 -le `php -r 'echo PHP_MAJOR_VERSION;'` && \
            test $2 -le `php -r 'echo PHP_MINOR_VERSION;'` && \
            test $3 -le `php -r 'echo PHP_RELEASE_VERSION;'`],
        [ AC_MSG_RESULT([PHP version is correct])],
        [ AC_MSG_ERROR([You need at least PHP version $1.$2.$3])])
])

AC_DEFUN([AC_CHECK_PHP_INCLUDE],[
  AC_MSG_CHECKING([if PHP runtime dependency '$2' is available])
  AS_IF([ php -r 'require "$1";' ],
    [ AC_MSG_RESULT([PHP runtime dependency fulfilled])],
    [  AC_MSG_ERROR([PHP runtime dependency '$2' is missing])])
])

AC_DEFUN([AC_GROUP_GUESS],[
   $2=$3
   for x in $1; do
     AC_MSG_CHECKING([if group $x exists])
     AS_IF([ $GREP -q "^$x:" /etc/group ],
           [ AC_MSG_RESULT([found]); $2=$x ; break],
           [ AC_MSG_RESULT([not found]) ])
   done
])

AC_DEFUN([AC_CHECK_BIN], [
   AC_PATH_PROG([$1],[$2],[not found])

   AS_IF([ test "XX${$1}" == "XXnot found" ],
     [ AC_MSG_WARN([binary $2 not found in PATH]) ])

   test "XX${$1}" == "XXnot found" && $1=""
])

AC_DEFUN([AC_PATH_GUESS], [
    $2=$3
    for x in $1; do
        AC_MSG_CHECKING([if path $x exists])
        AS_IF([test -d $x],
              [AC_MSG_RESULT([found]); $2=$x; break],
              [AC_MSG_RESULT([not found])]
        )
    done
])

# ICINGA_CHECK_DBTYPE(DBTYPE, ARGUMENT_NAME)
# ------------------------------------------
AC_DEFUN([ICINGA_CHECK_DBTYPE], [
    AC_MSG_CHECKING([Testing database type for $2])
    AS_IF(echo "$1" | $GREP -q "^\(my\|pg\)sql$",
        AC_MSG_RESULT([OK ($1)]),
        AC_MSG_ERROR([$1])
    )
])

# ICINGA_CHECK_BACKENDTYPE(BACKENDTYPE, ARGUMENT_NAME)
# ------------------------------------------
AC_DEFUN([ICINGA_CHECK_BACKENDTYPE], [
    AC_MSG_CHECKING([Testing backend type for $2])
    AS_IF(echo "$1" | $GREP -q "^\(ido\|statusdat\|livestatus\)$",
        AC_MSG_RESULT([OK ($1)]),
        AC_MSG_ERROR([$1])
    )
])
