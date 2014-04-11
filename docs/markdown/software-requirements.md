Software Requirements
=====================

Open XDMoD requires the following software:

- [Apache][]
    - [mod_rewrite][]
- [MySQL][] 5.1+
- [PHP][] 5.3+
    - [PDO][]
    - [MySQL PDO Driver][pdo-mysql]
    - [PEAR Log Package][pear-log]
- [Java][]
- [PhantomJS][]
- [cron][]
- [logrotate][]
- `sendmail` compatible binary

[apache]:      http://httpd.apache.org/
[mod_rewrite]: http://httpd.apache.org/docs/current/mod/mod_rewrite.html
[mysql]:       http://www.mysql.com/
[php]:         http://www.php.net/
[pdo]:         http://www.php.net/manual/en/book.pdo.php
[pdo-mysql]:   http://www.php.net/manual/en/ref.pdo-mysql.php
[pear-log]:    http://pear.php.net/package/Log
[java]:        http://java.com/
[phantomjs]:   http://phantomjs.org/
[cron]:        https://en.wikipedia.org/wiki/Cron
[logrotate]:   http://linux.die.net/man/8/logrotate

Linux Distribution Packages
---------------------------

Open XDMoD can be run on any Linux distribution, but has been tested on
Ubuntu 12.04 and CentOS 6.

Most of the requirements can be installed with the package managers
available from these distributions.

### Ubuntu 12.04

    # apt-get install apache2 php5 php5-cli php5-mysql php-pear php-log \
                      php-mdb2 php-mdb2-driver-mysql openjdk-6-jre \
                      openjdk-6-jdk phantomjs mysql-server mysql-client \
                      cron logrotate

### CentOS 6

    # yum install httpd mysql-server mysql php php-cli php-mysql php-pdo \
                  php-pear-Log php-pear-MDB2 php-pear-MDB2-Driver-mysql \
                  java-1.7.0-openjdk java-1.7.0-openjdk-devel cronie logrotate

**NOTE:** This list includes packages included with
[EPEL](http://fedoraproject.org/wiki/EPEL).  This repository can be
added with this command:

    # rpm -Uvh http://download.fedoraproject.org/pub/epel/6/i386/epel-release-6-8.noarch.rpm

**NOTE:** Neither the CentOS repositories nor EPEL include PhantomJS,
so that must be installed manually.  Packages are available for
[download](http://phantomjs.org/download.html) from the PhantomJS
website.

**NOTE:** Users of RHEL will need to enable the `optional` channel to
install some of these packages using `yum`.

**NOTE:** After installing Apache and MySQL you must make sure that they
are running.  CentOS may not start these services and they will not
start after a reboot unless you have configured them to do so.

Additional Notes
----------------

### PHP

Some linux distributions (including CentOS) do not set the timezone used
by PHP in their default configuration.  This will result in many warning
messages from PHP.  You should set the timezone in your `php.ini` file
by adding the following, but substituting your timezone:

    date.timezone = America/New_York

The PHP website contains the full list of supported [timezones][].

[timezones]: http://php.net/manual/en/timezones.php

### Apache

Open XDMoD requires that mod_rewrite be installed and enabled.  You will
also need to enable `.htaccess`
[support](http://httpd.apache.org/docs/current/howto/htaccess.html) (or
make sure it hasn't been disabled).  Since the Open XDMoD portal is a
web application you will also need to make sure you have configured your
firewall properly to allow appropriate network access.

### PhantomJS

If you are running PhantomJS 1.4 or earlier you will also need [Xvfb][]
running on port 99.

On Ubuntu 12.04 (and other operating systems using [Upstart][]), create
a file `/etc/init/xvfb.conf` containing the following:

    description "Xvfb X Server"
    start on (net-device-up
              and local-filesystems
              and runlevel [2345])
    stop on runlevel [016]
    exec su -s /bin/sh -c 'exec "$0" "$@"' daemon -- /usr/bin/Xvfb :99 -screen 0 1024x768x24

This will start Xvfb when your system boots.  To start Xvfb manually:

    # service xvfb start

[xvfb]:    http://www.x.org/archive/current/doc/man/man1/Xvfb.1.xhtml
[upstart]: http://upstart.ubuntu.com/

