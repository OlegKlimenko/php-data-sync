<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\DataSync\Application;

use SetBased\DataSync\Command\CompareCommand;
use SetBased\DataSync\Command\DumpDataCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

//----------------------------------------------------------------------------------------------------------------------
/**
 * The DataSync program.
 */
class DataSync extends Application
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Gets the default commands that should always be available.
   *
   * @return Command[] An array of default Command instances
   */
  protected function getDefaultCommands()
  {
    // Keep the core default commands to have the HelpCommand which is used when using the --help option
    $defaultCommands = parent::getDefaultCommands();

    $this->add(new DumpDataCommand());
    $this->add(new CompareCommand());

    return $defaultCommands;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------