Frequently Asked Questions
==========================

### What are the software requirements for Open XDMoD?

See [Software Requirements](software-requirements.md) for details.

### What are the hardware requirements for Open XDMoD?

The hardware requirements for Open XDMoD depend on how many concurrent
users you have and how much data you have.  You'll need roughly 300MB of
disk space per 1 Million jobs on your MySQL server.

### Why does the ingestion process take a long time?

If you are experiencing long ingestion times make sure that you have
tuned MySQL properly.  See
[Optimizing the MySQL Server][optimizing-mysql] for more details.

Here is an example of some server parameters that you can change.  Be
sure to understand and changes you make to your MySQL server
configuration.

    key_buffer              = 1600M
    max_allowed_packet      = 16M
    thread_stack            = 192K
    thread_cache_size       = 8
    sort_buffer_size        = 8M
    read_buffer_size        = 4M
    tmp_table_size          = 1G
    innodb_buffer_pool_size = 64M
    join_buffer_size        = 16M
    max_heap_table_size     = 128M
    table_cache             = 512
    query_cache_limit       = 16M
    query_cache_size        = 1G

[optimizing-mysql]: http://dev.mysql.com/doc/refman/5.1/en/optimizing-the-server.html


### How do I install Open XDMoD in a non-root URL?

Non-root URLs are not supported at this time.

### Why do I see the error message "It is not safe to rely on the system's timezone settings..."

You need to set your timezone in your `php.ini` file.

### Why do I see "Unknown resource attribute" in my xdmod-shredder output?

This indicates that you are using a resource attribute that Open XDMoD
does not recognize. This isn't a problem and can be safely ignored.

### Will Open XDMoD run on RHEL 5?

It is possible, but you must have PHP 5.3 installed.

### Can I use Open XDMoD with MySQL 5.0?

Open XDMoD should work with MySQL 5.0, but it hasn't been tested
extensively, so we recommend MySQL 5.1.

