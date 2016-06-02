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
  private $tableName;

  /**
   * The primary key.
   *
   * @var array|null
   */
  private $primaryKey;

  /**
   * Is primary key is autoincrement.
   *
   * @var bool
   */
  private $is_autoincrement;

  /**
   * The secondary key.
   *
   * @var SecondaryKey
   */
  private $secondaryKey;

  /**
   * The foreign keys.
   *
   * @var array|null
   */
  private $foreignKeys;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param string $tableName The table name.
   */
  public function __construct($tableName)
  {
    $this->tableName = $tableName;
    $this->primaryKey = null;
    $this->is_autoincrement = null;
    $this->secondaryKey = null;
    $this->foreignKeys = null;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Getter for table name.
   *
   * @return string
   */
  public function getTableName()
  {
    return $this->tableName;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Getter for primary key.
   *
   * @return array|null
   */
  public function getPrimaryKey()
  {
    return $this->primaryKey;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Getter for autoincrement state.
   *
   * @return bool|null
   */
  public function getAutoincrement()
  {
    return $this->is_autoincrement;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Getter for secondary key.
   *
   * @return SecondaryKey
   */
  public function getSecondaryKey()
  {
    if ($this->secondaryKey) { return $this->secondaryKey->getKey(); }
    else { return $this->secondaryKey; }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Getter for foreign keys.
   *
   * @return array|null
   */
  public function getForeignKeys()
  {
    return $this->foreignKeys;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Setter for primary key.
   *
   * @param array $primaryKey
   */
  public function setPrimaryKey($primaryKey)
  {
    $this->primaryKey = $primaryKey;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Setter for autoincrement state attribute.
   *
   * @param bool $state
   */
  public function setAutoincrement($state)
  {
    $this->is_autoincrement = $state;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Setter for secondary key.
   *
   * @param string $key
   */
  public function setSecondaryKey($key)
  {
    $this->secondaryKey = $key;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Sets foreign keys fetched from config file.
   *
   * @param array[] $foreignKeys
   */
  public function setForeignKeys($foreignKeys)
  {
    if ($foreignKeys)
    {
      $this->foreignKeys = [];

      foreach ($foreignKeys as $foreign_key)
      {
        $this->foreignKeys[] = new ForeignKeyMetadata($foreign_key['foreignKeyName'],
                                                      $foreign_key['table'],
                                                      $foreign_key['column'],
                                                      $foreign_key['refTable'],
                                                      $foreign_key['refColumn']);
      }
    }
    else
    {
      $this->foreignKeys = null;
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Fetches the primary key from a database and set it to an attribute.
   *
   * @param DataLayer $dataLayer The datalayer.
   * @param array[]   $data      The config data.
   */
  public function setPrimaryKeyFromDB($dataLayer, $data)
  {
    $primary_key = $dataLayer::getTablePrimaryKey($data['database']['data_schema'], $this->tableName);
    if (!empty($primary_key))
    {
      $this->primaryKey = $this->getColumnNames($primary_key);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Fetches the autoincrement state of a primary key from a database and set it to an attribute.
   *
   * @param DataLayer $dataLayer
   */
  public function setAutoincrementFromDB($dataLayer)
  {
    $is_autoincrement = $dataLayer::getAutoIncrementInfo($this->tableName);
    if (!empty($is_autoincrement))
    {
      $this->is_autoincrement = true;
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Fetches the secondary keys from a database and set them to an attribute.
   *
   * @param DataLayer $dataLayer The datalayer.
   * @param array[]   $data      The config data.
   */
  public function setSecondaryKeysFromDB($dataLayer, $data)
  {
    $secondary_keys = $dataLayer::getTableSecondaryKey($data['database']['data_schema'], $this->tableName);
    if (!empty($secondary_keys))
    {
      $secondary_keys     = self::getColumnNames($secondary_keys[0]);
      $this->secondaryKey = new SecondaryKey($secondary_keys[0]);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Fetches the foreign keys from a database and set them to an attribute.
   *
   * @param DataLayer $dataLayer The datalayer.
   * @param array[]   $data      The config data.
   */
  public function setForeignKeysFromDB($dataLayer, $data)
  {
    $foreign_keys = $dataLayer::getForeignKeys($data['database']['data_schema'], $this->tableName);
    if (!empty($foreign_keys))
    {
      $this->setForeignKeyNamesFromDB($foreign_keys);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Creates pretty output data after sql execution.
   *
   * @param array $list
   *
   * @return array
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
   * Sets foreign keys fetched from database.
   *
   * @param array[] $foreignKeys
   */
  public function setForeignKeyNamesFromDB($foreignKeys)
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