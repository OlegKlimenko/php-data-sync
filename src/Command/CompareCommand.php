<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\DataSync\Command;

use SetBased\DataSync\Command\Compare\CompareMasterData;
use SetBased\Stratum\Style\StratumStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

//----------------------------------------------------------------------------------------------------------------------
/**
 * Class for compare command.
 */
class CompareCommand extends Command
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The output decorator
   *
   * @var StratumStyle
   */
  private $io;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  protected function configure()
  {
    $this->setName('compare')
         ->setDescription('Compares two dumped files for checking the differences')
         ->addArgument('config filename', InputArgument::REQUIRED)
         ->addArgument('remote filename', InputArgument::REQUIRED)
         ->addArgument('local filename', InputArgument::REQUIRED);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Executes the compare command.
   *
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @return int
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->io = new StratumStyle($input, $output);
    $this->io->title('Compare dumped files');

    $config_filename = $input->getArgument('config filename');
    $remote_filename = $input->getArgument('remote filename');
    $local_filename = $input->getArgument('local filename');
    
    $compare_master_data = new CompareMasterData($config_filename, $this->io);
    $compare_master_data->compare($remote_filename, $local_filename);

    return 0;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------