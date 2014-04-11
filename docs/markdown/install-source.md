Source Installation Guide
=========================

Install Source Package
----------------------

    $ tar zxvf xdmod-x.y.z.tar.gz
    $ cd xdmod-x.y.z
    # ./install --prefix=/opt/xdmod

Change the prefix as desired.  The default installation prefix is
`/usr/local/xdmod`.  These instructions assume you are installing Open
XDMoD in `/opt/xdmod`.

Run Configuration Script
------------------------

    # /opt/xdmod/bin/xdmod-setup

Complete each setup section with the required information.

See the [Configuration Guide](configuration.md) for more details.

Symlink (or Copy) Configuration Files
-------------------------------------

    # ln -s /opt/xdmod/etc/apache.d/xdmod /etc/apache2/conf.d/xdmod.conf

    # ln -s /opt/xdmod/etc/cron.d/xdmod /etc/cron.d/xdmod

    # ln -s /opt/xdmod/etc/logrotate.d/xdmod /etc/logrotate.d/xdmod

The directories where these files are needed may differ depending on
your operating system.  By default, the Apache configuration creates a
virtual host on port 8080.

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

    $ /opt/xdmod/bin/xdmod-ingestor -v

See the [Ingestor Guide](ingestor.md) for more details.

Restart Apache
--------------

    # /etc/init.d/apache2 restart

This command may be different depending on your operating system.

Check Portal
------------

    http://localhost:8080/

