Command Reference
=================

Open XDMoD includes several command line utilities that are available to
users.

xdmod-setup
-----------

The `xdmod-setup` command is used to configure the Open XDMoD portal and
initialize the databases used by Open XDMoD.  See the
[Configuration Guide](configuration.md) for more details.

xdmod-shredder
--------------

The `xdmod-shredder` command is used to load data from log files into
the Open XDMoD databases.  This command writes data to the
`mod_shredder` database.  See the [Shredder Guide](shredder.md) for more
details.

xdmod-slurm-helper
------------------

The `xdmod-slurm-helper` command is used to load data from Slurm's
`sacct` command into the Open XDMoD databases.  See the
[Slurm Notes](resource-manager-slurm.md) for more details.

xdmod-ingestor
--------------

The `xdmod-ingestor` command is used to prepare data that has already
been loaded into the Open XDMoD database for querying by the Open XDMoD
portal.  This command reads from the `mod_shredder` database and writes
to `mod_hpcdb`, `modw` and `modw_aggregates` databases.  See the
[Ingestor Guide](ingestor.md) for more details.

xdmod-import-csv
----------------

The `xdmod-import-csv` command is used to load data from CSV files into
the Open XDMoD database.  This command writes data to the `mod_hpcdb`
database.  See the [User Name Guide](user-names.md) and
[Hierarchy Guide](hierarchy.md) for more details.

xdmod-check-config
------------------

The `xdmod-check-config` command is used to check your Open XDMoD
environment for any problems.  See the
[Troubleshooting Guide](troubleshooting.md) for more details.

