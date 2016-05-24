<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\DataSync\Command;

use SetBased\DataSync\Config;
use SetBased\Stratum\Style\StratumStyle;
use Symfony\Component\Console\Formatter\OutputFormatter;

//----------------------------------------------------------------------------------------------------------------------
/**
 * Class for comparing the master data.
 */
class CompareMasterData 
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The output decorator
   *
   * @var StratumStyle
   */
  private $io;

  /**
   * The config.
   *
   * @var Config
   */
  private $config;

  /**
   * The list with info about dumped data in remote file.
   *
   * @var array
   */
  private $remoteFileData;

  /**
   * The list with info about dumped data in local file.
   *
   * @var array
   */
  private $localFileData;
  
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * CompareMasterData constructor.
   *
   * @param string       $configFileName The name of config file.
   * @param StratumStyle $io             The styled input/output interface.
   */
  public function __construct($configFileName, $io)
  {
    $this->io = $io;
    $this->config = new Config($configFileName, $this->io);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Compares two dumped files.
   *
   * @param string $remoteFilename
   * @param string $localFilename
   */
  public function compare($remoteFilename, $localFilename)
  {
    $this->generateTableObjects($remoteFilename, $localFilename);

    // Passing over each table listed in metadata.
    foreach($this->config->data['metadata'] as $table_name => $table_data)
    {
      $this->io->text(sprintf('<info>#### %s ####</info>', OutputFormatter::escape($table_name)));

      // If table have secondary key, use it.
      if ($table_data['secondary_key'])
      {
        $existing_secondary_keys = $this->getChangesWithSecondaryKey($table_data, $table_name);
        $this->getAddingsWithSecondaryKey($existing_secondary_keys, $table_data, $table_name);
        $this->getDeletionsWithSecondaryKey($table_data, $table_name);
      }
      // If we don't have secondary key use primary key.
      else
      {
        $existing_primary_keys = $this->getChangesWithoutSecondaryKey($table_data, $table_name);
        $this->getAddingsWithoutSecondaryKey($existing_primary_keys, $table_data, $table_name);
        $this->getDeletionsWithoutSecondaryKey($table_data, $table_name);
      }

      $this->io->text(sprintf('', OutputFormatter::escape($table_name)));
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Generating table objects with existing records and fields with data.
   *
   * @param string $remoteFilename
   * @param string $localFilename
   */
  private function generateTableObjects($remoteFilename, $localFilename)
  {
    $remote_file_data = $this->readFile($remoteFilename);
    $local_file_data = $this->readFile($localFilename);

    $this->remoteFileData = $remote_file_data;
    $this->localFileData = $local_file_data;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Reads the file. Parses JSON file and create a list of arrays with data. Returns generated data.
   *
   * @param $filename
   *
   * @return mixed
   */
  private function readFile($filename)
  {
    $data = (array)json_decode(file_get_contents($filename), true);

    return $data;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Passes over records in table, checks passed secondary keys and changes if we have secondary key.
   *
   * @param array  $tableMetadata The array with metadata.
   * @param string $tableName     The name of a table in which we want to find changes.
   *
   * @return array
   */
  private function getChangesWithSecondaryKey($tableMetadata, $tableName)
  {
    $secondary_key = $tableMetadata['secondary_key'][0];
    $passed_secondary_keys = [];

    // Compare secondary keys of two records. If they are equals store name of secondary key list and got to
    // check changes method.
    foreach($this->remoteFileData[$tableName] as $remote_record)
    {
      foreach($this->localFileData[$tableName] as $local_record)
      {
        if ($remote_record[$secondary_key] == $local_record[$secondary_key])
        {
          $this->checkChanges($tableMetadata['primary_key'], $remote_record, $local_record);
          $passed_secondary_keys[] = $remote_record[$secondary_key];
        }
      }
    }
    return $passed_secondary_keys;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Passes over records in table, checks passed primary keys and changes if we don't have a secondary key.
   *
   * @param array  $tableMetadata The array with metadata.
   * @param string $tableName     The name of a table in which we want to find changes.
   *
   * @return array
   */
  private function getChangesWithoutSecondaryKey($tableMetadata, $tableName)
  {
    $passed_primary_keys = [];

    foreach($this->remoteFileData[$tableName] as $remote_record)
    {
      foreach($this->localFileData[$tableName] as $local_record)
      {
        // Pass over each primary key, because primary key can be complex.
        $pk_equality = true;
        foreach($tableMetadata['primary_key'] as $primary_key)
        {
          if ($remote_record[$primary_key] != $local_record[$primary_key])
          {
            $pk_equality = false;
          }
        }

        if ($pk_equality)
        {
          $this->checkChanges($tableMetadata['primary_key'], $remote_record, $local_record);
          $primary = [];

          // Generate and store primary key, because PK can be complex.
          foreach ($tableMetadata['primary_key'] as $primary_key)
          {
            $primary[] = $remote_record[$primary_key];
          }
          $passed_primary_keys[] = $primary;
        }
      }
    }
    return $passed_primary_keys;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Passes over fields in record and checks changes.
   *
   * @param array  $primaryKey
   * @param array $remoteRecord
   * @param array $localRecord
   */
  private function checkChanges($primaryKey, $remoteRecord, $localRecord)
  {
    $is_changed = false;

    // Pass over each field of two records. And check if we have changes.
    foreach($remoteRecord as $field_name => $remote_field)
    {
      if ($remoteRecord[$field_name] != $localRecord[$field_name] && !in_array($field_name, $primaryKey))
      {
        $is_changed = true;
      }
    }

    if ($is_changed)
    {
      $this->outputInfo($remoteRecord, 'change');
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Passes over each field of a secondary key. If new secondary key not exists in list of existing secondary keys,
   * goes to write added record.
   *
   * @param array  $existingSecondaryKeys The list of existing secondary keys.
   * @param array  $tableMetadata         The metadata of a table.
   * @param string $tableName             The name of a table which we passing.
   */
  private function getAddingsWithSecondaryKey($existingSecondaryKeys, $tableMetadata, $tableName)
  {
    $secondary_key = $tableMetadata['secondary_key'][0];
    $existing_secondary_keys = $existingSecondaryKeys;

    foreach($this->remoteFileData[$tableName] as $remote_record)
    {
      if (!in_array($remote_record[$secondary_key], $existing_secondary_keys))
      {
        $this->outputInfo($remote_record, 'add');
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Passes over each field of a primary key. If new primary key not exists in list of existing primary keys,
   * goes to write added record.
   *
   * @param array  $existingPrimaryKeys The list of existing primary keys.
   * @param array  $tableMetadata       The metadata about table.
   * @param string $tableName           The name of a table which we passing.
   */
  private function getAddingsWithoutSecondaryKey($existingPrimaryKeys, $tableMetadata, $tableName)
  {
    // Pass over records.
    foreach($this->remoteFileData[$tableName] as $remote_record)
    {
      $primary_keys = [];

      // Pass over columns of a primary key. And generate a primary key of separate columns of PK.
      foreach($tableMetadata['primary_key'] as $primary_key)
      {
        $primary_keys[] = $remote_record[$primary_key];
      }

      // Check if array with existing primary keys, have this primary key. If not, we write added record.
      if (!in_array($primary_keys, $existingPrimaryKeys))
      {
        $this->outputInfo($remote_record, 'add');
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Passes over each field in in local and remote record list, by secondary key. Checks if local record exists in
   * remote records changes 'is_exists' flag to true. If flag stays at false, we don't have this record in remote file,
   * so outputs info about deletions.
   *
   * @param array  $tableMetadata The metadata of a table.
   * @param string $tableName     The name of a table which we passing.
   */
  private function getDeletionsWithSecondaryKey($tableMetadata, $tableName)
  {
    $secondary_key = $tableMetadata['secondary_key'][0];

    foreach($this->localFileData[$tableName] as $local_record)
    {
      $is_exists = false;
      foreach($this->remoteFileData[$tableName] as $remote_record)
      {
        // If secondary keys are equals, so record is exists, we continue passing
        if ($remote_record[$secondary_key] == $local_record[$secondary_key])
        {
          $is_exists = true;
        }
      }

      // If we don't found equality, we know that record is deleted, we write info about it.
      if (!$is_exists)
      {
        $this->outputInfo($local_record, 'delete');
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Passes over each field in in local and remote record list, by primary key. Checks if local record exists in
   * remote records, changes 'is_exists' flag to true. If flag stays at false, we don't have this record in remote file,
   * so outputs info about deletions.
   *
   * @param array  $tableMetadata The metadata of a table.
   * @param string $tableName     The name of a table which we passing.
   */
  private function getDeletionsWithoutSecondaryKey($tableMetadata, $tableName)
  {
    foreach($this->localFileData[$tableName] as $local_record)
    {
      $is_exists = false;
      foreach($this->remoteFileData[$tableName] as $remote_record)
      {
        $local_pk = [];
        $remote_pk = [];

        // Generating list of columns from primary keys for future check on equality.
        foreach ($tableMetadata['primary_key'] as $primary_key)
        {
          $local_pk[] = $local_record[$primary_key];
          $remote_pk[] = $remote_record[$primary_key];
        }

        // If primary keys are equals, so record is exists, we continue passing
        if ($local_pk == $remote_pk)
        {
          $is_exists = true;
        }
      }

      // If we don't found equality, we know that record is deleted, we write info about it.
      if (!$is_exists)
      {
        $this->outputInfo($local_record, 'delete');
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Outputs the info depending on action.
   *
   * @param string $action The action which we need to output.
   * @param array  $record The record which we need to output.
   */
  private function outputInfo($record, $action)
  {
    if ($action == 'change') { $this->io->text(sprintf('<note>%s</note>', OutputFormatter::escape('Updated:'))); }
    else if ($action == 'add') { $this->io->text(sprintf('<fso>%s</fso>', OutputFormatter::escape('Inserted:'))); }
    else if ($action == 'delete') { $this->io->text(sprintf('<sql>%s</sql>', OutputFormatter::escape('Deleted:'))); }

    $output = "(";

    foreach($record as $field_name => $remote_field)
    {
      $output = $output." {$remote_field},";
    }

    $output = rtrim($output, ", ");
    $output = $output." )";
    $this->io->text(sprintf('%s', $output));
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------