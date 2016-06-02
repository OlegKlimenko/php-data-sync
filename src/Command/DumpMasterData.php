<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\DataSync\Command;

use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
use Ramsey\Uuid\Uuid;
use SetBased\DataSync\Config;
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
   * The data which we need to dump.
   *
   * @var array[]
   */
  private $dumpedData;

  /**
   * The data with unique ID info.
   *
   * @var array[]
   */
  private $uuidData;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param Config       $config    The config file object.
   * @param DataLayer    $dataLayer The data layer.
   * @param StratumStyle $io        The output decorator.
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
    $this->config->readConfigFile($this->config->getFileName());

    $this->generateData();
    $this->generateLookupTable();
    $this->writeData($dumpFileName);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Generates data in a structure for future dumping.
   */
  private function generateData()
  {
    $table_list = $this->config->getMetadata()->getTableList();
    $config_data = $this->config->getData();

    // Pass over each table name in metadata.
    foreach ($table_list as $table_name => $table)
    {
      // Select each row of a table.
      $rows = $this->dataLayer->selectAllFields($config_data['database']['data_schema'], $table_name);

      foreach($rows as $record_name => $record)
      {
        foreach($record as $field_name => $field_value)
        {
          $this->dumpedData[$table_name][$record_name][$field_name] = $field_value;
        }
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Generates lookup table with Unique ID's.
   */
  private function generateLookupTable()
  {
    foreach($this->dumpedData as $table_name => $table)
    {
      foreach($this->dumpedData[$table_name] as $record_name => $record)
      {
        foreach($this->dumpedData[$table_name][$record_name] as $field_name => $field)
        {
          $pk_is_autoincrement = $this->config->getMetadata()->getTableList()[$table_name]->getAutoincrement();
          $pk_field_name = $this->config->getMetadata()->getTableList()[$table_name]->getPrimaryKey();

          if ($pk_is_autoincrement && $pk_field_name[0] == $field_name)
          {
            $this->uuidData[$table_name][$record_name] = [$field, $this->generateUniqueID()];
          }
        }
      }
    }
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
   * Writes data into .json files.
   *
   * @param string $dumpFileName
   */
  private function writeData($dumpFileName)
  {
    StaticCommand::writeTwoPhases($dumpFileName, json_encode($this->dumpedData, JSON_PRETTY_PRINT), $this->io);

    $f_name = explode('.', $dumpFileName);
    $f_name[0] = $f_name[0]."-id";
    $uuid_filename = implode('.', $f_name);

    StaticCommand::writeTwoPhases($uuid_filename, json_encode($this->uuidData, JSON_PRETTY_PRINT), $this->io);
  }

  // -------------------------------------------------------------------------------------------------------------------
}

// ---------------------------------------------------------------------------------------------------------------------