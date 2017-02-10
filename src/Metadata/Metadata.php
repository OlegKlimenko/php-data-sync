<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\DataSync\Metadata;

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
  private $tableList = [];

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Getter for list of metadata table objects.
   *
   * @return array
   */
  public function getTableList()
  {
    return $this->tableList;
  }

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
    foreach ($data['tables'] as $table_name => $sync_flag)
    {
      // Set metadata to metadata object if not set in config for selected table.
      if ($sync_flag and !isset($data['metadata'][$table_name]))
      {
        $this->setMetadataFromDatabase($table_name, $dataLayer, $data);
      }

      // Set metadata to metadata object if set in config for selected table.
      if ($sync_flag and isset($data['metadata'][$table_name]))
      {
        $this->setMetadataFromExistingFile($table_name, $data);
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Fetch metadata from database and set into object.
   *
   * @param string          $tableName The name of a table
   * @param MySql\DataLayer $dataLayer The layer to work with database
   * @param array           $data      The config data
   */
  private function setMetadataFromDatabase($tableName, $dataLayer, $data)
  {
    $table = new TableMetadata($tableName);
    $table->setPrimaryKeyFromDB($dataLayer, $data);
    $table->setAutoincrementFromDB($dataLayer);
    $table->setSecondaryKeysFromDB($dataLayer, $data);
    $table->setForeignKeysFromDB($dataLayer, $data);

    $this->tableList[$table->getTableName()] = $table;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Fetch metadata from config file and set into object.
   *
   * @param $tableName
   * @param $data
   */
  private function setMetadataFromExistingFile($tableName, $data)
  {
    $table = new TableMetadata($tableName);
    $table->setPrimaryKey($data['metadata'][$tableName]['primary_key']);
    $table->setAutoincrement($data['metadata'][$tableName]['primary_autoincrement']);

    $secondary_key = $data['metadata'][$tableName]['secondary_key'];
    if ($secondary_key) { $table->setSecondaryKey(new SecondaryKey($secondary_key)); }
    else { $table->setSecondaryKey(null); }

    $table->setForeignKeys($data['metadata'][$tableName]['foreign_keys']);

    $this->tableList[$table->getTableName()] = $table;
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
    foreach ($this->tableList as $table)
    {
      $table_name = $table->getTableName();

      $metadata[$table_name] = [];
      $metadata[$table_name]['primary_key'] = $table->getPrimaryKey();
      $metadata[$table_name]['primary_autoincrement'] = $table->getAutoincrement();
      $metadata[$table_name]['secondary_key'] = $table->getSecondaryKey();
      $metadata[$table_name]['foreign_keys'] = $this->insertForeignKeys($table);

    }
    return $metadata;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Inserts foreign keys in structure.
   *
   * @param TableMetadata $table The metadata of a table.
   *
   * @return array|null
   */
  private function insertForeignKeys($table)
  {
    $fk = $table->getForeignKeys();
    if ($fk)
    {
      $foreign_keys = [];
      foreach($table->getForeignKeys() as $fk_number => $fk_data)
      {
        $foreign_keys[$fk_number] = ['foreignKeyName' => $fk_data->getFkName(),
                                     'table' => $fk_data->getTable(),
                                     'column' => $fk_data->getColumn(),
                                     'refTable' => $fk_data->getRefTable(),
                                     'refColumn' => $fk_data->getRefColumn()];
      }
    }
    else
    {
      $foreign_keys = null;
    }
    return $foreign_keys;
  }

  // -------------------------------------------------------------------------------------------------------------------
}

// ---------------------------------------------------------------------------------------------------------------------
