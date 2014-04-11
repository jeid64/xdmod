<?php

namespace Xdmod\Ingestor\Staging;

use ArrayIngestor;
use Xdmod\Config;

class Resources extends ArrayIngestor
{
   public function __construct($dest_db, $src_db)
   {
      $config = Config::factory();
      $resourceConfig = $config['resources'];

      $configForResource = array();

      foreach ($resourceConfig as $id => $resource) {
         if (!is_numeric($id)) { continue; }

         $configForResource[$resource['resource']] = $resource;

         // Check for sub-resources.
         if (isset($resource['sub_resources'])) {
            foreach ($resource['sub_resources'] as $subResource) {

               // Inherit resource attributes.
               $attrs = array('resource_type_id');
               foreach ($attrs as $attr) {
                  if (!isset($subResource[$attr])) {
                     $subResource[$attr] = $resource[$attr];
                  }
               }

               $configForResource[$subResource['resource']] = $subResource;
            }
         }
      }

      $sql = 'SELECT cluster_id, cluster_name FROM staging_cluster';
      $rows = $src_db->query($sql);

      $resources = array();

      foreach ($rows as $row) {
         $config = $configForResource[$row['cluster_name']];

         $resources[] = array(
            $row['cluster_id'],
            $config['resource_type_id'] ?: 0,
            1,
            $config['name'],
            $config['name'],
         );
      }

      parent::__construct(
         $dest_db,
         $resources,
         'hpcdb_resources',
         array(
            'resource_id',
            'resource_type_id',
            'organization_id',
            'resource_name',
            'resource_code',
         )
      );
   }
}

