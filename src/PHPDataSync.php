<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\DataSync;

use SetBased\DataSync\MySql\DataLayer;
use SetBased\DataSync\Exception\RuntimeException;
//----------------------------------------------------------------------------------------------------------------------
/**
 * The PHPDataSync program.
 */
class PHPDataSync 
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

    // Drop database connection
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
    $this->configData = json_decode(file_get_contents($configFileName),
                                    true);
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
   * Changes JSON object, Writes new data to .json file.
   *
   * @param DataLayer $dataLayer
   *
   * @throws RuntimeException
   */
  public function updateConfigFile($dataLayer)
  {
    // Get all table names.
    $table_names = $dataLayer::getTableNames($this->configData['database']['data_schema']);

    // Insert table names into json object.
    foreach ($table_names as $name)
    {
      $this->configData['tables'][$name['table_name']] = false;
    }

    // Write into .json file.
    $file = fopen($this->configFileName, 'w');
    if (!$file)
    {
      throw new RuntimeException("Unable to open file '%s'", $this->configFileName);
    }

    $bytes = fwrite($file, json_encode($this->configData, JSON_PRETTY_PRINT));
    if (!$bytes)
    {
      throw new RuntimeException("Unable to write into file '%s'", $this->configFileName);
    }

    $is_closed = fclose($file);
    if (!$is_closed) {
      throw new RuntimeException("Unable to close file '%s'", $this->configFileName);
    }
  }
}

