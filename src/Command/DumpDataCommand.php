<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\DataSync\Command;

use SetBased\DataSync\Config;
use SetBased\DataSync\MySql\DataLayer;
use SetBased\Stratum\Style\StratumStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

//----------------------------------------------------------------------------------------------------------------------
class DumpDataCommand extends Command
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The output decorator
   *
   * @var StratumStyle
   */
  private $io;

  /**
   * The config.
   *
   * @var string
   */
  private $config;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  protected function configure()
  {
    $this->setName('dump')
         ->setDescription('Reads the config file, generates metadata, dumps the data.')
         ->addArgument('config filename', InputArgument::REQUIRED)
         ->addArgument('dump-data filename', InputArgument::REQUIRED);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Executes the Dump data command.
   *
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @return integer
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->io = new StratumStyle($input, $output);
    $this->io->title('Dump master data');
    
    $config_filename    = $input->getArgument('config filename');
    $dump_data_filename = $input->getArgument('dump-data filename');

    // Open JSON file for getting data.
    $this->config = new Config($config_filename, $this->io);

    // Open connection with database.
    $data_layer = new DataLayer();
    $data_layer::connect($this->config->data['database']['host_name'],
                         $this->config->data['database']['user_name'],
                         $this->config->data['database']['password'],
                         $this->config->data['database']['data_schema']);
    $data_layer::setIo($this->io);

    // Update config file.
    $this->config->updateConfigFile($data_layer);

    // Dumping master data.
    $dump_master_data = new DumpMasterData($this->config, $data_layer, $this->io);
    $dump_master_data->dumpData($dump_data_filename);

    // Drop database connection.
    $data_layer::disconnect();

    return 0;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------