<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\DataSync\Command\Dump;

use SetBased\DataSync\Config;
use SetBased\DataSync\Command\Dump\Components\Node;
use SetBased\DataSync\Command\Dump\Components\DependencyGraph;
use SetBased\Stratum\Style\StratumStyle;
use Symfony\Component\Console\Formatter\OutputFormatter;

//----------------------------------------------------------------------------------------------------------------------
/**
 * Class for checking wrong dependencies between tables.
 */
class WrongReferencedData
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

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * WrongReferencedData constructor.
   *
   * @param StratumStyle $io
   * @param Config       $config
   */
  public function __construct($io, $config)
  {
    $this->io = $io;
    $this->config = $config;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Starts checking problems with references.
   */
  public function checkProblems()
  {
    $this->detectSelfReferences();
    $this->detectCycles();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Detects self references and writes about it.
   */
  private function detectSelfReferences()
  {
    $metadata = $this->config->getMetadata();

    foreach ($metadata->getTableList() as $table_name => $table)
    {
      if ($table->getForeignKeys())
      {
        foreach($table->getForeignKeys() as $fk_number => $fk_data)
        {
          if ($fk_data->getTable() == $fk_data->getRefTable())
          {
            $this->io->text(sprintf(''));
            $this->io->text(sprintf('table <sql>%s</sql> has self references:', OutputFormatter::escape($table_name)));
            $this->io->text(sprintf('<info>%s</info> => <info>%s</info>',
                                    OutputFormatter::escape($fk_data->getColumn()),
                                    OutputFormatter::escape($fk_data->getRefColumn())));
            $this->io->text(sprintf(''));

          }
        }
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Detects cycle dependencies.
   */
  private function detectCycles()
  {
    $metadata = $this->config->getMetadata();

    $graph = new DependencyGraph($this->io);

    foreach($metadata->getTableList() as $table_name => $table)
    {
      $node = new Node($table_name, $table);
      $graph->addNode($node);
    }

    $graph->boundNodes();
    $graph->startSearchCycles();
  }

  // -------------------------------------------------------------------------------------------------------------------
}

// ---------------------------------------------------------------------------------------------------------------------