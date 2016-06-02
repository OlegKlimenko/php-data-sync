<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\DataSync\Command\Dump\Components;

//----------------------------------------------------------------------------------------------------------------------
/**
 * Class for nodes which describes tables and it's dependencies.
 */
class Node
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The name of node (i.e. Table name).
   *
   * @var string
   */
  public $name;

  /**
   * The metadata of a node (i.e. table).
   *
   * @var array
   */
  public $metadata;

  /**
   * The name of a parent node.
   *
   * @var array
   */
  public $parents;

  /**
   * The list with child nodes.
   *
   * @var array
   */
  public $children;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Node constructor.
   *
   * @param string $name         The name of a node (i.e name of a table).
   * @param array  $nodeMetadata The metadata of a node (i.e. of a table).
   */
  public function __construct($name, $nodeMetadata)
  {
    $this->name = $name;
    $this->metadata = $nodeMetadata;
    $this->parents = [];
    $this->children = [];
  }

  // -------------------------------------------------------------------------------------------------------------------
}

// ---------------------------------------------------------------------------------------------------------------------