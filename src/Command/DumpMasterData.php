<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\DataSync\Command;

use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
use Ramsey\Uuid\Uuid;
use SetBased\DataSync\Config;
use SetBased\DataSync\DBObjects\Table;
use SetBased\DataSync\Metadata;
use SetBased\DataSync\MySql\DataLayer;
use SetBased\Exception\RuntimeException;
use SetBased\Stratum\Style\StratumStyle;

//----------------------------------------------------------------------------------------------------------------------
/**
 * Class for dumping the master data.
 */
class DumpMasterData
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The output decorator
   *
   * @var StratumStyle
   */
  private $io;

  /**
   * Config file object.
   *
   * @var Config
   */
  private $config;

  /**
   * The data layer for using methods of manipulating with database.
   *
   * @var DataLayer
   */
  private $dataLayer;

  /**
   * The list of tables with data.
   *
   * @var array
   */
  private $tableList;

  /**
   * The data which we need to dump.
   *
   * @var array[]
   */
  private $dumpedData;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param Config    $config    The config file object.
   * @param DataLayer $dataLayer The data layer.
   * @param StratumStyle $io             The output decorator.
   */
  public function __construct($config, $dataLayer, $io)
  {
    $this->io        = $io;
    $this->config    = $config;
    $this->dataLayer = $dataLayer;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Dumps the data in .json file.
   *
   * @param string $dumpFileName The name of file in which we dump data.
   */
  public function dumpData($dumpFileName)
  {
    $this->config->readConfigFile($this->config->fileName);
    $this->generateData();

    $this->dump();

    StaticCommand::writeTwoPhases($dumpFileName, json_encode($this->dumpedData, JSON_PRETTY_PRINT), $this->io);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Generates data in a structure for future dumping.
   */
  private function generateData()
  {
    // Pass over each table name in metadata.
    foreach ($this->config->metadata->tableList as $table_name => $table)
    {
      // Select each row of a table.
      $rows = $this->dataLayer->selectAllFields($this->config->data['database']['data_schema'], $table_name);

      $this->tableList[$table_name] = new Table($table_name, $rows);
    }

    // Set new id's for PK values
    $this->setNewIDs();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Dumps the data
   */
  private function dump()
  {
    foreach ($this->tableList as $table_name => $table)
    {
      foreach ($this->tableList[$table_name]->records as $record_name => $record)
      {
        foreach ($this->tableList[$table_name]->records[$record_name]->fields as $field_name => $field)
        {
          // Every field have Additional value for setting new ID. If it is set, we use it.
          // Otherwise use its own value.
//          if (!$field->additionalValue)
//          {
//            $this->dumpedData[$table_name][$record_name][$field_name] = $field->fieldValue;
//          }
//          else
//          {
//            $this->dumpedData[$table_name][$record_name][$field_name] = $field->additionalValue;
//          }
          $this->dumpedData[$table_name][$record_name][$field_name] = $field->fieldValue;
        }
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Sets the UUID's to PK values.
   */
  private function setNewIDs()
  {
    $unique_records = $this->getPrimaryKeyRows();

    // Set new ID's
    foreach ($unique_records as $item)
    {
      $uuid = (string)$this->generateUniqueID();

      $this->changePkValues($item, $uuid);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects primary keys with values and put them in array.
   *
   * @return array[]
   */
  private function getPrimaryKeyRows()
  {
    // Select records with PK's.
    $pk_rows = [];
    foreach ($this->config->metadata->tableList as $table_name => $table)
    {
      foreach ($table->primaryKey as $key => $value)
      {
        $pk_rows[] = $this->dataLayer->selectField($value, $this->config->data['database']['data_schema'], $table_name);
      }
    }

    // Returns an array only with name of PK field and value of field.
    return $this->getUniqueRecords($pk_rows);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Change many times nested array into 'flat' array with values.
   *
   * @param array[] $rows The rows which we need to change.
   *
   * @return array[]
   */
  private function getUniqueRecords($rows)
  {
    $unique = [];

    foreach ($rows as $table_name => $row)
    {
      foreach ($row as $data)
      {
        foreach ($data as $key => $record)
        {
          $unique[] = [$key, $record];
        }
      }
    }

    return $unique;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Generates the unique ID.
   *
   * @return integer
   *
   * @throws RuntimeException
   */
  private function generateUniqueID()
  {
    try
    {
      $uuid = (string)Uuid::uuid4();

      return $uuid;
    }
    catch (UnsatisfiedDependencyException $e)
    {
      throw new RuntimeException("Cannot generate unique ID...");
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Changes the values of primary keys.
   *
   * @param array[] $item The record in table with field name and field value.
   * @param string  $uuid The unique ID which must be set.
   */
  private function changePkValues($item, $uuid)
  {
    // Set new ID's to referenced keys.
    foreach ($this->config->metadata->tableList as $table_name => $metatable)
    {
      if (is_array($metatable->foreignKeys))
      {
        foreach ($metatable->foreignKeys as $fk_name => $fk_data)
        {
          if ($item[0] == $fk_data['refColumn'])
          {
            $this->changeRefValues('ref', $fk_data, $item, $uuid);
            $this->changeRefValues('', $fk_data, $item, $uuid);
          }
        }
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Change the value referenced on param.
   *
   * @param string $state   If set to 'ref_' we change values for referenced tables, Otherwise, for own tables.
   * @param array  $fk_data The info about foreign key in Metadata table.
   * @param array  $item    The selected primary key record for checking.
   * @param string $uuid    The new unique ID for primary key.
   */
  private function changeRefValues($state, $fk_data, $item, $uuid)
  {
    if ($state == 'ref')
    {
      $table  = $state.'Table';
      $column = $state.'Column';
    }
    else
    {
      $table  = $state.'table';
      $column = $state.'column';
    }


    foreach ($this->tableList[$fk_data[$table]]->records as $record_name => $record)
    {
      foreach ($record->fields as $field_name => $field)
      {
        if ($item[1] == $record->fields[$fk_data[$column]]->fieldValue)
        {
          if (!$record->fields[$fk_data[$column]]->additionalValue)
          {
            $tmp = $this->tableList[$fk_data[$table]]->records[$record_name]->fields[$fk_data[$column]];
            $tmp->additionalValue = $uuid;
          }
        }
      }
    }
  }

  // -------------------------------------------------------------------------------------------------------------------
}

// ---------------------------------------------------------------------------------------------------------------------