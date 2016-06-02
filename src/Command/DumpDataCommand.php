<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\DataSync\Command;

use SetBased\DataSync\Config;
use SetBased\DataSync\MySql\DataLayer;
use SetBased\DataSync\Command\Dump\DumpMasterData;
use SetBased\DataSync\Command\Dump\WrongReferencedData;
use SetBased\Stratum\Style\StratumStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

//----------------------------------------------------------------------------------------------------------------------
/**
 * Class for dumping data command.
 */
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
   * @var Config
   */
  private $config;

  /**
   * The data layer for operations with database.
   *
   * @var DataLayer
   */
  private $dataLayer;

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
    // Get names of files which we will use.
    $config_filename    = $input->getArgument('config filename');
    $dump_data_filename = $input->getArgument('dump-data filename');

    $this->setStyle($input, $output);

    $this->createConfig($config_filename);
    $this->connectToDatabase();
    $this->updateConfig();

    $this->dumpMasterData($dump_data_filename);

    $this->dropConnectionToDatabase();
    
    return 0;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Sets style for future outputting data.
   *
   * @param InputInterface  $input
   * @param OutputInterface $output
   */
  private function setStyle($input, $output)
  {
    $this->io = new StratumStyle($input, $output);
    $this->io->title('Dump master data');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Creates config file name.
   *
   * @param string $configFilename
   */
  private function createConfig($configFilename)
  {
    $this->config = new Config($configFilename, $this->io);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Connects to database, sets data layer.
   */
  private function connectToDatabase()
  {
    $data = $this->config->getData();

    $this->dataLayer = new DataLayer();
    $this->dataLayer->connect($data['database']['host_name'],
                         $data['database']['user_name'],
                         $data['database']['password'],
                         $data['database']['data_schema']);

    $this->dataLayer->setIo($this->io);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /*
   * Updates config file.
   */
  private function updateConfig()
  {
    $this->config->updateConfigFile($this->dataLayer);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Executes dumping data.
   *
   * @param string $dumpDataFilename The name of file name in which we want to dump data.
   */
  private function dumpMasterData($dumpDataFilename)
  {
    $dump_master_data = new DumpMasterData($this->config, $this->dataLayer, $this->io);
    $dump_master_data->dumpData($dumpDataFilename);

    $wrong_ref_data = new WrongReferencedData($this->io, $this->config);
    $wrong_ref_data->checkProblems();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Closes connection with database.
   */
  private function dropConnectionToDatabase()
  {
    $this->dataLayer->disconnect();
  }

  // -------------------------------------------------------------------------------------------------------------------
}

// ---------------------------------------------------------------------------------------------------------------------