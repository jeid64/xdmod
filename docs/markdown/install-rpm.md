RPM Installation Guide
======================

Install Prerequisites
---------------------

See the [Software Requirements](software-requirements.md) for details.

Install the RPM
---------------

    # rpm -Uvh xdmod-x.x.x-x.el6.noarch.rpm

Configure Open XDMoD
--------------------

Be sure MySQL is running before using the setup command.

    # xdmod-setup

See the [Configuration Guide](configuration.md) for more details.

Shred Data
----------

PBS Example:

    $ /opt/xdmod/bin/xdmod-shredder -v -r *resource* -f pbs \
          -d /var/spool/pbs/server_priv/accounting

SGE Example:

    $ /opt/xdmod/bin/xdmod-shredder -v -r *resource* -f sge \
          -i /var/lib/gridengine/default/common/accounting

Slurm Example:
(Only works if Open XDMoD is on a machine with the sacct command)

    $ /opt/xdmod/bin/xdmod-slurm-helper -v -r *resource*

The resource name here must match the one supplied to the setup script.

See the [Shredder Guide](shredder.md) for more details.

Ingest Data
-----------

See the [Ingestor Guide](ingestor.md) for more details.

Reload Apache
-------------

    # service httpd reload

Now you should be able to view the Open XDMoD portal at the URL used
during the configuration process.

