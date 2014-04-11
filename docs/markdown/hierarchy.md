Hierarchy Guide
===============

Open XDMoD supports a three level hierarchy that can be customized and
populated with site specific data.  Importing this data is a two step
process.  First, you must import the hierarchy items and then you must
import a mapping from groups (PIs) to hierarchy items.

The input format of the hierarchy data is a CSV file where the first
column contains the name of the hierarchy item, the second column
contains an abbreviation of the first column and the third column
contains the parent hierarchy item (leave this column blank for the
top level hierarchy items.

If you use Slurm, here is an example that will output account data in an
acceptable format.  This will only work if your account hierarchy
contains three or fewer levels.  Any more than that won't be displayed
in the portal.

    $ sacctmgr -n -P -s list account \
               format=Account,Description,ParentName \
               | grep -v '|$' \
               | sed -e 's/|/","/g' -e 's/$\|^/"/g' -e 's/"root"$/""/' \
               >/tmp/accounts.csv

    $ xdmod-import-csv -t hierarchy -i /tmp/accounts.csv

After importing the heirarchy it is necessary to provide a mapping from
your user groups to the hierarchy items.  The input format of this
mapping is a CSV file where the first column contains the name of groups
used by your resource manager and the second column contains names of
items in the bottom most hierarchy level you have imported.

    $ xdmod-import-csv -t group-to-hierarchy -i /tmp/groups.csv

After importing this data you must ingest it for the date range of any
job data you have already shredded.

    $ xdmod-ingest --start-date 2012-01-01 --end-date 2012-12-31

