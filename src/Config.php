<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\DataSync;

use SetBased\DataSync\Command\StaticCommand;
use SetBased\DataSync\Metadata\Metadata;
use SetBased\Exception\RuntimeException;
use SetBased\Stratum\Style\StratumStyle;

//----------------------------------------------------------------------------------------------------------------------
/**
 * Class for manipulating and storing config info.
 */
class Config
{
  // -------------------------------------------------------------------------------------------------------------------
  /**
   * The output decorator
   *
   * @var StratumStyle
   */
  private $io;

  /**
   * Config file name.
   *
   * @var string
   */
  private $fileName;

  /**
   * The data of config file.
   *
   * @var array[]
   */
  private $data;

  /**
   * The metadata of config file.
   *
   * @var Metadata
   */
  private $metadata;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param string       $configFileName The config filename.
   * @param StratumStyle $io             The output decorator.
   */
  public function __construct($configFileName, $io)
  {
    $this->io = $io;
    $this->readConfigFile($configFileName);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Getter for 'data' attribute.
   *
   * @return array
   */
  public function getData()
  {
    return $this->data;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Getter for 'fileName' attribute.
   *
   * @return string
   */
  public function getFileName()
  {
    return $this->fileName;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Getter for metadata object.
   *
   * @return Metadata
   */
  public function getMetadata()
  {
    return $this->metadata;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Reads configuration file. Stores data of config file in class attribute.
   *
   * @param string $configFileName
   */
  public function readConfigFile($configFileName)
  {
    $this->data = (array)json_decode(file_get_contents($configFileName), true);
    $this->fileName = $configFileName;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Writes changes in config file.
   *
   * @param MySql\DataLayer $dataLayer
   *
   * @throws RuntimeException
   */
  public function updateConfigFile($dataLayer)
  {
    // Fill table list if we haven't got list of tables in config file.
    if (!count($this->data['tables']))
    {
      $this->getTablesList($dataLayer);
    }

    // Set metadata.
    $this->metadata = new Metadata();
    $this->metadata->generateMetadata($dataLayer, $this->data);
    $this->data['metadata'] = $this->metadata->insertMetadata();

    // Write into config file.
    StaticCommand::writeTwoPhases($this->fileName, json_encode($this->data, JSON_PRETTY_PRINT), $this->io);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Gets list of tables.
   *
   * @param MySql\DataLayer $dataLayer
   */
  private function getTablesList($dataLayer)
  {
    // Get all table names.
    $table_names = $dataLayer::getTableNames($this->data['database']['data_schema']);

    // Insert table names into json object.
    foreach ($table_names as $name)
    {
      $this->data['tables'][$name['table_name']] = false;
    }
  }

  // -------------------------------------------------------------------------------------------------------------------

}

// ---------------------------------------------------------------------------------------------------------------------