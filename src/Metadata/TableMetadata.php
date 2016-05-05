<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\DataSync\Metadata;

use SetBased\DataSync\MySql\DataLayer;

//----------------------------------------------------------------------------------------------------------------------
/**
 * Class for table in metadata.
 */
class TableMetadata
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The name of a table.
   *
   * @var string
   */
  public $tableName;

  /**
   * The primary key.
   *
   * @var array[]
   */
  public $primaryKey = null;

  /**
   * Is primary key is autoincrement.
   *
   * @var bool
   */
  public $is_autoincrement = null;

  /**
   * The secondary key.
   *
   * @var array[]
   */
  public $secondaryKey = null;

  /**
   * The foreign keys.
   *
   * @var array[]
   */
  public $foreignKeys = null;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param string $tableName The table name.
   */
  public function __construct($tableName)
  {
    $this->tableName = $tableName;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Sets the primary key to a table.
   *
   * @param DataLayer $dataLayer The datalayer.
   * @param array[]   $data      The config data.
   */
  public function setPrimaryKey($dataLayer, $data)
  {
    $primary_key = $dataLayer::getTablePrimaryKey($data['database']['data_schema'], $this->tableName);
    if (!empty($primary_key))
    {
      $this->primaryKey = $this->getColumnNames($primary_key);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Sets the autoincrement state of a primary key of a table.
   *
   * @param DataLayer $dataLayer
   */
  public function setAutoincrement($dataLayer)
  {
    $is_autoincrement = $dataLayer::getAutoIncrementInfo($this->tableName);
    if (!empty($is_autoincrement))
    {
      $this->is_autoincrement = true;
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Sets secondary keys for a table.
   *
   * @param DataLayer $dataLayer The datalayer.
   * @param array[]   $data      The config data.
   */
  public function setSecondaryKeys($dataLayer, $data)
  {
    $secondary_keys = $dataLayer::getTableSecondaryKey($data['database']['data_schema'], $this->tableName);
    if (!empty($secondary_keys))
    {
      $secondary_keys     = self::getColumnNames($secondary_keys[0]);
      $this->secondaryKey = $secondary_keys[0];
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Sets the foreign keys of a table.
   *
   * @param DataLayer $dataLayer The datalayer.
   * @param array[]   $data      The config data.
   */
  public function setForeignKeys($dataLayer, $data)
  {
    $foreign_keys = $dataLayer::getForeignKeys($data['database']['data_schema'], $this->tableName);
    if (!empty($foreign_keys))
    {
      $this->setFkNames($foreign_keys);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Creates pretty output data after sql execution.
   *
   * @param array[] $list
   *
   * @return array[]
   */
  private function getColumnNames($list)
  {
    $list_names = [];
    foreach ($list as $name)
    {
      $list_names[] = $name['column_name'];
    }

    return $list_names;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Sets foreign keys.
   *
   * @param array[] $foreignKeys
   */
  private function setFkNames($foreignKeys)
  {
    $this->foreignKeys = [];
    foreach ($foreignKeys as $foreign_key)
    {
      foreach ($foreign_key as $col_names)
      {
        $this->foreignKeys[] = new ForeignKeyMetadata($col_names['constraint_name'],
                                                      $col_names['table_name'],
                                                      $col_names['column_name'],
                                                      $col_names['ref_table_name'],
                                                      $col_names['ref_column_name']);
      }
    }
  }

  // -------------------------------------------------------------------------------------------------------------------

}

// ---------------------------------------------------------------------------------------------------------------------