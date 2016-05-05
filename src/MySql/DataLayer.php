<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\DataSync\MySql;

use SetBased\Stratum\MySql\StaticDataLayer;
//----------------------------------------------------------------------------------------------------------------------
/**
 * Supper class for a static stored routine wrapper class.
 */
class DataLayer extends StaticDataLayer {
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects all table names in a schema.
   *
   * @param string $schemaName The name of the schema.
   *
   * @return array[]
   */
  public static function getTableNames($schemaName)
  {
    $sql = sprintf("
    SELECT TABLE_NAME AS table_name
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = %s
    AND TABLE_TYPE = 'BASE TABLE'
    ORDER BY TABLE_NAME", self::quoteString($schemaName));

    return self::executeRows($sql);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Checks if PK of table is autoincrement.
   *
   * @param string $tableName The name of table.
   *
   * @return array[]
   */
  public static function getAutoIncrementInfo($tableName)
  {
    $sql = sprintf("
    SELECT COLUMN_NAME as column_name
    FROM information_schema.COLUMNS
    WHERE TABLE_NAME = %s
    AND EXTRA like '%%auto_increment%%'", self::quoteString($tableName));

    return self::executeRows($sql);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects primary key of a table in selected schema.
   *
   * @param string $schemaName The name of the schema.
   * @param string $tableName  The name of a table.
   *
   * @return array[]
   */
  public static function getTablePrimaryKey($schemaName, $tableName)
  {
    $sql = sprintf("
    SELECT TABLE_NAME AS table_name, column_name
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = %s
    AND TABLE_NAME = %s
    AND COLUMN_KEY = 'PRI'", self::quoteString($schemaName), self::quoteString($tableName));

    return self::executeRows($sql);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects secondary key of a table in selected schema.
   *
   * @param string $schemaName The name of the schema.
   * @param string $tableName  The name of a table.
   *
   * @return array[]
   */
  public static function getTableSecondaryKey($schemaName, $tableName)
  {
    // Getting a name of constraint which type is UNIQUE.
    $sql = sprintf("
    SELECT CONSTRAINT_NAME AS 'constraint_name'
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE constraint_schema = %s
    AND TABLE_NAME = %s
    AND CONSTRAINT_TYPE = 'UNIQUE'
    ORDER BY CONSTRAINT_NAME", self::quoteString($schemaName), self::quoteString($tableName));

    $constraints = self::executeRows($sql);

    if (!empty($constraints))
    {
      $field_names = [];

      foreach($constraints as $constraint)
      {
        // Selecting a name of column by name of constraint.
        $sql = sprintf("
        SELECT COLUMN_NAME AS 'column_name'
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE constraint_schema = %s
        AND TABLE_NAME = %s
        AND CONSTRAINT_NAME = %s", self::quoteString($schemaName), self::quoteString($tableName),
                                   self::quoteString($constraint['constraint_name']));

        $field_names[] = self::executeRows($sql);
      }
      return $field_names;
    }
    
    else 
    { 
      return []; 
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects info about primary keys of a table in selected schema.
   *
   * @param string $schemaName The name of the schema.
   * @param string $tableName  The name of a table.
   *
   * @return array[]
   */
  public static function getForeignKeys($schemaName, $tableName)
  {
    // Getting a constraint name for foreign key.
    $sql = sprintf("
    SELECT CONSTRAINT_NAME AS 'constraint_name'
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE information_schema.TABLE_CONSTRAINTS.CONSTRAINT_TYPE = 'FOREIGN KEY'
    AND information_schema.TABLE_CONSTRAINTS.TABLE_SCHEMA = %s
    AND information_schema.TABLE_CONSTRAINTS.TABLE_NAME = %s", self::quoteString($schemaName),
                                                               self::quoteString($tableName));
    $constraint_names = self::executeRows($sql);
    $field_names = [];

    // Getting names of columns and tables of foreign keys.
    foreach($constraint_names as $constraint_name)
    {
      $sql = sprintf("
      SELECT COLUMN_NAME AS 'column_name',
      TABLE_NAME AS 'table_name',
      REFERENCED_TABLE_NAME AS 'ref_table_name',
      REFERENCED_COLUMN_NAME AS 'ref_column_name'
      FROM information_schema.KEY_COLUMN_USAGE
      WHERE CONSTRAINT_NAME = %s", self::quoteString($constraint_name['constraint_name']));

      $table_items = self::executeRows($sql);
      $table_items[0]['constraint_name'] = $constraint_name['constraint_name'];

      $field_names[] = $table_items;
    }
    return $field_names;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects all rows of data.
   *
   * @param string $schemaName The name of the schema.
   * @param string $tableName  The name of a table.
   *
   * @return array[]
   */
  public static function selectAllFields($schemaName, $tableName)
  {
    $sql = sprintf("SELECT * FROM %s.%s", $schemaName, $tableName);
    
    return self::executeRows($sql);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects rows of data of certain column name.
   *
   * @param string $column     The name of column which we want to select.
   * @param string $schemaName The name of the schema.
   * @param string $tableName  The name of a table.
   *
   * @return array[]
   */
  public static function selectField($column, $schemaName, $tableName)
  {
    $sql = sprintf("SELECT %s FROM %s.%s", $column, $schemaName, $tableName);

    return self::executeRows($sql);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Executes a query that returns 0 or more rows.
   *
   * @param string $query The SQL statement.
   *
   * @return array[] The selected rows.
   */
  public static function executeRows($query)
  {
    return parent::executeRows($query);
  }
  // -------------------------------------------------------------------------------------------------------------------
}
// ---------------------------------------------------------------------------------------------------------------------