<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\DataSync;

use SetBased\DataSync\MySql\DataLayer;
use SetBased\DataSync\Command\DumpMasterData;
//----------------------------------------------------------------------------------------------------------------------
/**
 * The DataSync program.
 */
class DataSync
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The config.
   *
   * @var string
   */
  private $config;

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
    $this->config = new Config($options['config']);

    // Open connection with database.
    $data_layer = new DataLayer();
    $data_layer::connect($this->config->data['database']['host_name'], $this->config->data['database']['user_name'],
                         $this->config->data['database']['password'], $this->config->data['database']['data_schema']);

    // Update config file.
    $this->config->updateConfigFile($data_layer);

    // Dumping master data.
    $dump_master_data = new DumpMasterData($this->config, $data_layer);
    $dump_master_data->dumpData($options['dump-master-data']);

    // Drop database connection.
    $data_layer::disconnect();

    return 0;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------


