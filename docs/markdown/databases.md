Database Guide
==============

Open XDMoD uses several MySQL databases.  These will be automatically be
automatically created by the database section of the `xdmod-setup`
command.

Manual Setup
------------

If you prefer to not give you root (or other privileged user's) username
and password to the setup command, you will need to create and
initialize the databases manually and add the database credentials to
`portal_settings.ini`.  These databases must be named as shown below.
Also, the credentials from `modw` and `modw_aggregates` must be the same
(note that `modw_aggregates` isn't listed in `portal_settings.ini`).

You can find the schema for each database in the `ddl` directory of the
source distribution or in the `/usr/share/xdmod/ddl` directory if you've
installed the RPM.  After you've created the databases, intialize them
with the corresponding file in this directory.

moddb
-----

Application data.  Stores data used by the portal, including user data
and reports.

modw
----

Data warehouse database.

modw_aggregates
---------------

Data warehouse aggregate database.

**NOTE: The tables in this database are dynamically generated and are
not created until the `xdmod-ingestor` command has performed
aggregation on the data in `modw`.**

mod_logger
----------

Logger database.  Stores warnings and errors from various processes.

mod_shredder
------------

Shredder database.  Stores data from resource managers.

mod_hpcdb
---------

Intermediate storage for data that has been normalized before being
loaded into the data warehouse.

