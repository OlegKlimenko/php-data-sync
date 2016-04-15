<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\DataSync;

use SetBased\DataSync\MySql\DataLayer;
use SetBased\DataSync\Exception\RuntimeException;

//----------------------------------------------------------------------------------------------------------------------
/**
 * The DataSync program.
 */
class DataSync
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Config file name.
   *
   * @var string
   */
  private $configFileName;

  /**
   * The data of config file.
   *
   * @var array[array[string]]
   */
  private $configData;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Does main operations of the program.
   *
   * @param string[] $options Option from console on running script
   *
   * @return int
   */
  public function main($options)
  {
    // Open JSON file for getting data.
    $this->readConfigFile($options['config']);

    // Open connection with database.
    $data_layer = new DataLayer();
    $data_layer::connect($this->configData['database']['host_name'], $this->configData['database']['user_name'],
                         $this->configData['database']['password'], $this->configData['database']['data_schema']);

    // Update config file.
    $this->updateConfigFile($data_layer);

    // Drop database connection.
    $data_layer::disconnect();

    return 0;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Reads configuration file. Stores data of config file in class attribute.
   *
   * @param string $configFileName
   *
   * @throws RuntimeException
   */
  public function readConfigFile($configFileName)
  {
    $this->configData = json_decode(file_get_contents($configFileName), true);
    if (!$this->configData)
    {
      throw new RuntimeException('Unable to read file "%s"', $configFileName);
    }
    else
    {
      $this->configFileName = $configFileName;
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Writes changes in config file.
   *
   * @param DataLayer $dataLayer
   *
   * @throws RuntimeException
   */
  public function updateConfigFile($dataLayer)
  {
    // Fill table list if we haven't got list of tables in config file.
    if (!count($this->configData['tables']))
    {
      $this->getTablesList($dataLayer);
    }

    $this->generateMetadata($dataLayer);

    // Write into config file.
    Util::writeTwoPhases($this->configFileName, json_encode($this->configData, JSON_PRETTY_PRINT));
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Gets list of tables.
   *
   * @param DataLayer $dataLayer
   */
  public function getTablesList($dataLayer)
  {

    // Get all table names.
    $table_names = $dataLayer::getTableNames($this->configData['database']['data_schema']);

    // Insert table names into json object.
    foreach ($table_names as $name)
    {
      $this->configData['tables'][$name['table_name']] = false;
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Generates metadata for tables where value set to 'true'.
   *
   * @param DataLayer $dataLayer
   */
  public function generateMetadata($dataLayer)
  {
    if (!array_key_exists('metadata', $this->configData))
    {
      $this->configData['metadata'] = [];
    }

    // Pass over all table names.
    foreach($this->configData['tables'] as $table_name => $sync_flag)
    {
      // If we must sync data in the table and no metadata has been defined add metadata to the config.
      if ($sync_flag and !isset($this->configData['metadata'][$table_name]))
      {
        // Set primary key.
        $primary_key = $dataLayer::getTablePrimaryKey($this->configData['database']['data_schema'], $table_name);
        if (!empty($primary_key))
        {
          $primary_key = $this->getColumnNames($primary_key);
          $this->configData['metadata'][$table_name]['primary_key'] = $primary_key[0];
        }
        else
        {
          $this->configData['metadata'][$table_name]['primary_key'] = null;
        }

        // Set secondary keys.
        $secondary_keys = $dataLayer::getTableSecondaryKey($this->configData['database']['data_schema'], $table_name);
        if (!empty($secondary_keys))
        {
          $secondary_keys = $this->getColumnNames($secondary_keys[0]);
          $this->configData['metadata'][$table_name]['secondary_key'] = $secondary_keys[0];
        }
        else
        {
          $this->configData['metadata'][$table_name]['secondary_key'] = null;
        }

        // Set foreign keys.
        $foreign_keys = $dataLayer::getForeignKeys($this->configData['database']['data_schema'], $table_name);
        if (!empty($foreign_keys)) 
        {
          $this->setFkNames($foreign_keys, $table_name);
        }
        else
        {
          $this->configData['metadata'][$table_name]['foreign_keys'] = null;
        }
      }
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
  public function getColumnNames($list)
  {
    $list_names = [];
    foreach($list as $name)
    {
        $list_names[] = $name['column_name'];
    }
    return $list_names;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Sets foreign keys into config.
   *
   * @param array[] $foreignKeys
   *
   * @param string $tableName
   */
  public function setFkNames($foreignKeys, $tableName)
  {
    $fk_number = 0;
    foreach($foreignKeys as $foreign_key)
    {
      $metadata = [];
      foreach($foreign_key as $col_names)
      {
        $metadata['column'] = $col_names['column_name'];
        $metadata['ref_table'] = $col_names['ref_table_name'];
        $metadata['ref_column'] = $col_names['ref_column_name'];
        $fk_number += 1;
      }
      $this->configData['metadata'][$tableName]['foreign_keys'][$col_names['constraint_name']] = $metadata;
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------


