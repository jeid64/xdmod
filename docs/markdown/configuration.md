Configuration Guide
===================

Setup Script
------------

Open XDMoD includes a setup script to help you configure your
installation.  This script will prompt you for information needed to
configure Open XDMoD and update your configuration files accordingly.
If you have modified your configuration files manually, be sure to make
backups before running this command:

    # xdmod-setup

### General Settings

The general settings include:

- Site address (The URL you will use to access Open XDMoD)
- E-Mail address (The email address Open XDMoD will use)
- Java Path
- Javac Path
- PhantomJS Path
- Header Logo (see [Logo Image Guide](logo-image.md) for details)

These settings are stored in `portal_settings.ini`.

### Database Settings

Will create and initialize database as well as storing these settings:

- DB Host
- DB Port
- DB Username
- DB Password

These settings are stored in `portal_settings.ini`.

You will be requried to supply a username and password for a user that
has privileges to create databases and users.  If you don't want to use
this process and would prefer to manually create the databases, see the
[Database Guide](databases.md).

### Organization Settings

The organization settings require a name and abbreviation for your
organization.  These will be used in the portal to refer to anything
relating to your organization's data.

### Resources

For each resource (cluster) that you will be loading data into Open
XDMoD, you will need to supply a name and core count.  The name supplied
here will be used during the shredding process.  If you are using the
Slurm helper script, this name must the cluster name used by Slurm.

In addition to this name another format name is required.  This is the
name that will be displayed in the Open XDMoD portal.

You will also be required to supply the number of nodes and cores in
your cluster.  These number are used to display the utilization charts
(the percentage of your cluster that is being used).  If these numbers
are not accurate, these charts will likewise be inaccurate.  Note that
changes in cluster size are not supported at this time, historical data
will be compared with the current cluster size.

### Create Admin User

This will allow you to create and admistrative user that can log into
the Open XDMoD portal and create other users.  You will need to supply a
username and password for this user along with the first name, last name
and email address of your admin.

### Hierarchy

Open XDMoD supports a three level hierarchy that can be used to generate
charts.  Here you can supply a name and description for each level.

See the [Hierarchy Guide](hierarchy.md) for more details.

Apache Configuration
--------------------

Uses port 8080 by default, if changed, must also be changed in
`portal_settings.ini`.

    Listen 8080
    <VirtualHost *:8080>
        DocumentRoot /usr/share/xdmod/html
        <Directory /usr/share/xdmod/html>
            Options FollowSymLinks
            DirectoryIndex index.php
            RewriteEngine On
        </Directory>
    </VirtualHost>

Logrotate Configuration
-----------------------

A logrotate config file is included for the Open XDMoD log files.

Cron Configuration
------------------

A cron config file is included that runs the script that sends out
scheduled reports.  You can also use this file to schedule shredding and
ingestion.

Location of Configuration Files
-------------------------------

The Open XDMoD config files (excluding the apache, logrotate and cron
files) are located in the `etc` directory of the installation prefix or
`/etc/xdmod` for the RPM distribution.

portal_settings.ini
-------------------

Primary configuration file.

- site address
- email address
- databases

datawarehouse.json
------------------

Defines realms, group bys, statistics.

processor_buckets.json
----------------------

Defines ranges used  in "Job Size" charts.

    [
        [1,       1,          1, "1"],
        [2,       2,          2, "2"],
        [3,       3,          4, "3 - 4"],
        [4,       5,          8, "5 - 8"],
        [5,       9,         16, "9 - 16"],
        [6,      17,         32, "17 - 32"],
        [7,      33,         64, "33 - 64"],
        [8,      65,        128, "65 - 128"],
        [9,     129,        256, "129 - 256"],
        [10,    257,        512, "257 - 512"],
        [11,    513,       1024, "513 - 1024"],
        [12,   1025,       2048, "1k - 2k"],
        [13,   2049,       4096, "2k - 4k"],
        [14,   4097, 2147483647, "> 4k"]
    ]

roles.json
----------

Defines roles and associated statistics.

node_attributes.json
--------------------

Used to define data needed for sub-resources.

organization.json
-----------------

Defines the organization name and abbreviation.

    {
        "name": "Example Organization",
        "abbrev": "EO"
    }

resources.json
--------------

    {
        "0": {
            "host": "resource1",
            "resource_type_id": 1,
            "name": "Resource 1"
            "processors": 1024,
            "nodes": 64
        },
        "1": {
            "host": "resource2",
            "resource_type_id": 2,
            "name": "Resource 2",
            "processors": 256,
            "nodes": 32
        }
    }

resource_types.json
-------------------

Defines resource types.  If you have multiple resources you may assign
types to each resources.

    [
        {
            "id": 1,
            "abbrev": "T1",
            "description": "Example Type 1"
        },
        {
            "id": 2,
            "abbrev": "T2",
            "description": "Example Type 2"
        }
    ]

