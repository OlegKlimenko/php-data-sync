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
     * @param string $theSchemaName The name of the schema.
     *
     * @return array[]
     */
    public static function getTableNames($theSchemaName)
    {
        $sql = sprintf("
        SELECT TABLE_NAME AS table_name
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = %s
        AND TABLE_TYPE = 'BASE TABLE'
        ORDER BY TABLE_NAME", self::quoteString($theSchemaName));

        return self::executeRows($sql);
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