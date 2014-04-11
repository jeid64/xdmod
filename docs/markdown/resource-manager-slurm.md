Slurm Notes
===========

Helper Script
-------------

Open XDMoD includes a helper script to pull data from Slurm's `sacct`
into Open XDMoD's shredder system. This script can be used in place of
the shredder to import data. To shred data for all Slurm clusters, use
this command:

    $ xdmod-slurm-helper

If you have multiple Slurm clusters, but only want to shred data from
one of them, then use this command with the name of a single cluster
that would be used with `sacct`'s `--clusters` option:

    $ xdmod-slurm-helper -r mycluster

The helper script doesn't update the aggregate tables, so that must be
done after the data has been shredded:

    $ xdmod-ingestor

If your `sacct` executable isn't in the path of the user that will be
running the `xdmod-slurm-helper` command, you can specify the path by
adding the following to your `portal_settings.ini` file.

    [slurm]
    sacct = /path/to/sacct

Use this command to display the help text for the Slurm helper script:

    $ xdmod-slurm-helper -h

Input Format
------------

If you'd prefer to not use the helper script, you can export data from
Slurm into a file manually using the `sacct` command and then shred that
file. The format must be the same as below. Also, the `--parsable2`,
`--noheader` and `--allocations` are all required. If you don't want to
import data from all clusters, the `--allclusters` option can be
replaced with `--clusters` and the list of clusters. It may also be
possible to use other options that limit the output.

    $ sacct --allusers --parsable2 --noheader --allocations --allclusters \
            --format jobid,cluster,partition,account,group,user,submit,eligible,start,end,elapsed,exitcode,nnodes,ncpus,nodelist,jobname \
            --state CANCELLED,COMPLETED,FAILED,NODE_FAIL,PREEMPTED,TIMEOUT \
            --starttime 2013-01-01T00:00:00 --endtime 2013-01-02T00:00:00 \
            >/tmp/slurm.log

    $ xdmod-shredder -r mycluster -f slurm -i /tmp/slurm.log

Unsupported Shredder Features
-----------------------------

The `xdmod-shredder` `-d`/`--dir` option was designed to work with the
accounting log naming convention used by PBS/TORQUE. If you are not
using the same convention (files are named `YYYYMMDD` corresponding to
the date jobs ended), do not use this option.

