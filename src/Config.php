<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\DataSync;

use SetBased\DataSync\Meta\Metadata;
use SetBased\Exception\RuntimeException;
//----------------------------------------------------------------------------------------------------------------------
/**
 * Class for manipulating and storing config info.
 */
class Config
{
  // -------------------------------------------------------------------------------------------------------------------
  /**
   * Config file name.
   *
   * @var string
   */
  public $fileName;

  /**
   * The data of config file.
   *
   * @var array[]
   */
  public $data;

  /**
   * The metadata of config file.
   * 
   * @var Metadata
   */
  public $metadata;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param string $configFileName The config filename.
   */
  public function __construct($configFileName)
  {
    $this->readConfigFile($configFileName);
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
    $this->data = json_decode(file_get_contents($configFileName), true);
    if (!$this->data)
    {
      throw new RuntimeException('Unable to read file "%s"', $configFileName);
    }
    else
    {
      $this->fileName = $configFileName;
    }
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
    Util::writeTwoPhases($this->fileName, json_encode($this->data, JSON_PRETTY_PRINT));
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