Documentation Conventions
=========================

The following conventions are used in the Open XDMoD documentation.

Command Examples
----------------

Examples of commands that may be run as an unprivileged user are
prefixed with a dollar sign (`$`). e.g.:

    $ xdmod-ingestor -v

Examples of commands that must be run with root privileges are prefixed
with a number sign (`#`). e.g.:

    # ./install --prefix=/opt/xdmod

These commands may be run with `sudo` or `su`.

Many of the examples commands may assume that the Open XDMoD command
line utilities are in your `PATH`, you will need to adjust these if this
is not the case. For example, the following:

    # xdmod-setup

Would need to be changed to:

    # /opt/xdmod/bin/xdmod-setup

If you installed Open XDMoD in `/opt/xdmod`.

If you installed the Open XDMoD RPM or DEB package, these commands will
be placed in `/usr/bin` which is most likely already in your `PATH`.

Some command examples include values that must be changed.  These values
will be surrounded with astericks (`*`).  For example:

    $ xdmod-shredder -f *format* -r *resource* -i *input*

You would need to replace `*format*`, `*resource*` and `*input*` with
appropriate values.

Examples of incomplete commands will end with three dots (`...`).  You
may need to add additional options to these examples to for a working
command invocation.  For example:

    $ xdmod-shredder -f sge ...

This command requires additional options to be useful.

