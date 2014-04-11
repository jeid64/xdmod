<?php

namespace Xdmod\Ingestor\Staging;

use PDODBSynchronizingIngestor;

class People extends PDODBSynchronizingIngestor
{
   public function __construct($dest_db, $src_db)
   {
      parent::__construct(
         $dest_db,
         $src_db,
         "
            SELECT
               union_user_group_id   AS person_id,
               1                     AS organization_id,
               union_user_group_name AS last_name
            FROM staging_union_user_group
         ",
         'hpcdb_people',
         'person_id',
         array(
            'person_id',
            'organization_id',
            'last_name',
         )
      );
   }
}

