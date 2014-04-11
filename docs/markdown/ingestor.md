Ingestor Guide
==============

This guide will attempt to outline the used of the Open XDMoD ingestor
command line utility.  The ingestor is responsible for preparing data
that has already been loaded into the Open XDMoD databases so that is
can be queried by the Open XDMoD portal.  This process also includes
aggregating the data in the Open XDMoD database to increase the
performance of the queries performed by the Open XDMoD portal.

General Usage
-------------

By default, the ingestor with process new job data entered into the
Open XDMoD database.

    $ xdmod-ingestor

The ingestor should be run after you have shredded your data.  If you
have multiple clusters, you may run the shredder multiple times followed
by a single use of the ingestor.

Help
----

To display the shredder help text from the command line:

    $ xdmod-ingestor -h

Verbose Output
--------------

By default the Open XDMoD ingestor only outputs what it considers to be
warnings or errors. If you would like to see informational output about
what is being performed, use the verbose option:

    $ xdmod-ingestor -v

Debugging output is also available:

    $ xdmod-ingestor --debug

Start and End Date
------------------

If you have changed any data in the Open XDMoD database it is necessary
to re-ingest that data.  This can be accomplished by specifying a start
and end date that include the dates associated with the modified data.

    $ xdmod-ingestor --start-date *start-date* --end-date *end-date*

