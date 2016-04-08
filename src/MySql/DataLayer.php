<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\DataSync\MySql;

use SetBased\Stratum\MySql\StaticDataLayer;
//----------------------------------------------------------------------------------------------------------------------
/**
 * Supper class for a static stored routine wrapper class.
 */
class DataLayer extends StaticDataLayer {
  //------------------------------------------------------------------------------------------------------------------
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

  //------------------------------------------------------------------------------------------------------------------
  /**
   * Selects primary key of a table in selected schema.
   *
   * @param string $schemaName The name of the schema.
   *
   * @param string $tableName The name of a table.
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

  //------------------------------------------------------------------------------------------------------------------
  /**
   * Selects secondary key of a table in selected schema.
   *
   * @param string $schemaName The name of the schema.
   *
   * @param string $tableName The name of a table.
   *
   * @return array[]
   */
  public static function getTableSecondaryKey($schemaName, $tableName)
  {
    // Getting a name of constraint which type is UNIQUE.
    $sql = sprintf("
    SELECT CONSTRAINT_NAME 'constraint_name'
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
        SELECT COLUMN_NAME  'column_name'
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

  //------------------------------------------------------------------------------------------------------------------
  /**
   * Executes a query that returns 0 or more rows.
   *
   * @param string $theQuery The SQL statement.
   *
   * @return array[] The selected rows.
   */
  public static function executeRows($theQuery)
  {
    return parent::executeRows($theQuery);
  }
}