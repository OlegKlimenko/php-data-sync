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
  private $foreignKeyName;

  /**
   * The name of a table.
   *
   * @var string
   */
  private $table;

  /**
   * The name of a column.
   *
   * @var string
   */
  private $column;

  /**
   * The name of a referenced table.
   *
   * @var string
   */
  private $refTable;

  /**
   * The name of a referenced column in referenced table.
   *
   * @var string
   */
  private $refColumn;

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

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Getter for foreign key name.
   *
   * @return string
   */
  public function getFkName()
  {
    return $this->foreignKeyName;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Getter for table info of foreign key.
   *
   * @return string
   */
  public function getTable()
  {
    return $this->table;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Getter for column info of foreign key.
   *
   * @return string
   */
  public function getColumn()
  {
    return $this->column;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Getter for referenced table of foreign key.
   *
   * @return string
   */
  public function getRefTable()
  {
    return $this->refTable;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Getter for referenced column of foreign key.
   *
   * @return string
   */
  public function getRefColumn()
  {
    return $this->refColumn;
  }

  // -------------------------------------------------------------------------------------------------------------------
}

// ---------------------------------------------------------------------------------------------------------------------