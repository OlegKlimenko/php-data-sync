<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\DataSync;

use Exception;
use SetBased\Stratum\Style\StratumStyle;
use Symfony\Component\Console\Formatter\OutputFormatter;

//----------------------------------------------------------------------------------------------------------------------
/**
 * Class for manipulating with dependencies of tables.
 */
class DependencyGraph
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The output decorator
   *
   * @var StratumStyle
   */
  private $io;
  
  /**
   * The list of trees.
   *
   * @var array
   */
  private $nodes;

  //-------------------------------------------------------------------------------------------------------------------
  /**
   * DependencyGraph constructor.
   *
   * @param StratumStyle $io The output decorator.
   */
  public function __construct($io)
  {
    $this->nodes = [];
    $this->io = $io;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Adds a new node to list of nodes.
   *
   * @param Node $newNode The new node which we want to add.
   */
  public function addNode($newNode)
  {
    $this->nodes[] = $newNode;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Creates dependencies between nodes.
   */
  public function boundNodes()
  {
    foreach($this->nodes as $node_num => $node)
    {
      foreach($this->nodes as $found_node_num => $found_node)
      {
        if ($found_node->metadata->getForeignKeys())
        {
          foreach($found_node->metadata->getForeignKeys() as $fk_name => $fk_data)
          {
            if ($fk_data->getRefTable() == $node->name && $fk_data->getRefTable() != $fk_data->getTable())
            {
              $this->nodes[$node_num]->children[] = $found_node->name;
              $this->nodes[$found_node_num]->parents[] = $node->name;
            }
          }
        }
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Starts detecting cycles. Passes over each vertex and calls DFS alghoritm to detect cycles.
   */
  public function startSearchCycles()
  {
    foreach($this->nodes as $node)
    {
      $passed_nodes = [];
      $passed_nodes[] = $node->name;
      $this->DFS($node, $passed_nodes);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * DFS algorithm: https://en.wikipedia.org/wiki/Depth-first_search
   * Passes deep into each vertex and add each vertex into passed nodes, for checking
   * if the first vertex and the last are equals, we have cycle.
   *
   * @param Node  $parent      The parent node in which we get data.
   * @param array $passedNodes The list of nodes which we already passed.
   *
   * @throws Exception Throws the exception to terminate program when we find cycle dependencies.
   */
  private function DFS($parent, $passedNodes)
  {
    foreach($parent->children as $child)
    {
      foreach($this->nodes as $node)
      {
        if ($node->name == $child)
        {
          $passedNodes[] = $node->name;

          if ($passedNodes[0] == end($passedNodes))
          {
            $this->outputCycleInfo($passedNodes);
            throw new Exception("Cycle detected! See above!");
          }

          $this->DFS($node, $passedNodes);
        }
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Outputs info between which tables cycle was detected.
   *
   * @param array $nodeList The list of nodes (i.e. their names).
   */
  private function outputCycleInfo($nodeList)
  {
    $this->io->text(sprintf(''));
    $this->io->text("There is a cycle dependency between tables:");

    foreach($nodeList as $node)
    {
      $this->io->text(sprintf('<sql>%s</sql> =>', OutputFormatter::escape($node)));
    }
  }

  // -------------------------------------------------------------------------------------------------------------------

}

// ---------------------------------------------------------------------------------------------------------------------

