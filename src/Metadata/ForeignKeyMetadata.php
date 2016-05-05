<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\DataSync\Metadata;

//----------------------------------------------------------------------------------------------------------------------
/**
 * Class for foreign key of table in metadata.
 */
class ForeignKeyMetadata
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The name of a foreign key.
   *
   * @var string
   */
  public $foreignKeyName;

  /**
   * The name of a table.
   *
   * @var string
   */
  public $table;

  /**
   * The name of a column.
   *
   * @var string
   */
  public $column;

  /**
   * The name of a referenced table.
   *
   * @var string
   */
  public $refTable;

  /**
   * The name of a referenced column in referenced table.
   *
   * @var string
   */
  public $refColumn;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * ForeignKeyMetadata constructor.
   *
   * @param string $fkName           The name of a foreign key.
   * @param string $table            The name of a table.
   * @param string $column           The name of a column.
   * @param string $referencedTable  The name of a referenced table.
   * @param string $referencedColumn The name of a referenced column in referenced table.
   */
  public function __construct($fkName, $table, $column, $referencedTable, $referencedColumn)
  {
    $this->foreignKeyName = $fkName;
    $this->table = $table;
    $this->column = $column;
    $this->refTable = $referencedTable;
    $this->refColumn = $referencedColumn;
  }

  // -------------------------------------------------------------------------------------------------------------------
}

// ---------------------------------------------------------------------------------------------------------------------