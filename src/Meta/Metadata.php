<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\DataSync\Meta;

use SetBased\DataSync\MySql;

//----------------------------------------------------------------------------------------------------------------------
/**
 * Class for metadata of config file.
 */
class Metadata
{
  // -------------------------------------------------------------------------------------------------------------------
  /**
   * The list of tables
   *
   * @var array
   */
  public $tableList = [];

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Generates metadata.
   *
   * @param MySql\DataLayer $dataLayer The datalayer.
   * @param array[]         $data      The config data.
   *
   * @return array[]
   */
  public function generateMetadata($dataLayer, $data)
  {
    // Pass over all table names.
    foreach($data['tables'] as $table_name => $sync_flag)
    {
      // Create the metadata if not set for selected table.
      if ($sync_flag and !isset($data['metadata'][$table_name]))
      {
        $table = new MetaTable($table_name);
        $table->setPrimaryKey($dataLayer, $data);
        $table->setAutoincrement($dataLayer);
        $table->setSecondaryKeys($dataLayer, $data);
        $table->setForeignKeys($dataLayer, $data);

        $this->tableList[$table->getTableName()] = $table;
      }

      // Get the metadata if set for selected table.
      if ($sync_flag and isset($data['metadata'][$table_name]))
      {
        $table = new MetaTable($table_name);
        $table->primaryKey = $data['metadata'][$table_name]['primary_key'];
        $table->is_autoincrement = $data['metadata'][$table_name]['primary_autoincrement'];
        $table->secondaryKey = $data['metadata'][$table_name]['secondary_key'];
        $table->foreignKeys = $data['metadata'][$table_name]['foreign_keys'];

        $this->tableList[$table->getTableName()] = $table;
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Inserts metadata into config file.
   *
   * @return array
   */
  public function insertMetadata()
  {
    $metadata = [];
    foreach($this->tableList as $table)
    {
      $table_name = $table->getTableName();
      $table_pk = $table->getPrimaryKey();
      $table_autoincrement = $table->getAutoincrement();
      $table_sk = $table->getSecondaryKey();
      $table_fk = $table->getForeignKeys();

      $metadata[$table_name] = [];
      $metadata[$table_name]['primary_key'] = $table_pk;
      $metadata[$table_name]['primary_autoincrement'] = $table_autoincrement;
      $metadata[$table_name]['secondary_key'] = $table_sk;
      $metadata[$table_name]['foreign_keys'] = $table_fk;
    }

    return $metadata;
  }
  
  // -------------------------------------------------------------------------------------------------------------------
}

// ---------------------------------------------------------------------------------------------------------------------
