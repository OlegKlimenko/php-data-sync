<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\DataSync\Command;

use SetBased\DataSync\Config;
use SetBased\DataSync\DBObjects\Record;
use SetBased\DataSync\DBObjects\Table;
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
   * @var string
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

    foreach($this->config->data['metadata'] as $table_name => $table_data)
    {
      $this->remoteFileData[$table_name] = new Table($table_name, $remote_file_data[$table_name]);
      $this->localFileData[$table_name] = new Table($table_name, $local_file_data[$table_name]);
    }
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
    $data = json_decode(file_get_contents($filename), true);

    return $data;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Passes over records in table, checks passed secondary keys and changes if we have secondary key.
   *
   * @param array  $tableData The array with data of config file.
   * @param string $tableName The name of a table in which we want to find changes.
   *
   * @return array
   */
  private function getChangesWithSecondaryKey($tableData, $tableName)
  {
    $secondary_key = $tableData['secondary_key'][0];
    $passed_secondary_keys = [];

    // Compare secondary keys of two records. If they are equals store name of secondary key list and got to
    // check changes method.
    foreach($this->remoteFileData[$tableName]->records as $remote_record)
    {
      foreach($this->localFileData[$tableName]->records as $local_record)
      {
        if ($remote_record->fields[$secondary_key]->fieldValue == $local_record->fields[$secondary_key]->fieldValue)
        {
          $this->checkChanges($tableData['primary_key'], $remote_record, $local_record);
          $passed_secondary_keys[] = $remote_record->fields[$secondary_key]->fieldValue;
        }
      }
    }
    return $passed_secondary_keys;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Passes over records in table, checks passed primary keys and changes if we don't have a secondary key.
   *
   * @param array  $tableData The array with data of config file.
   * @param string $tableName The name of a table in which we want to find changes.
   *
   * @return array
   */
  private function getChangesWithoutSecondaryKey($tableData, $tableName)
  {
    $passed_primary_keys = [];

    foreach($this->remoteFileData[$tableName]->records as $remote_record)
    {
      foreach($this->localFileData[$tableName]->records as $local_record)
      {
        // Pass over each primary key, because primary key can be complex.
        $pk_equality = true;
        foreach($tableData['primary_key'] as $primary_key)
        {
          if ($remote_record->fields[$primary_key]->fieldValue != $local_record->fields[$primary_key]->fieldValue)
          {
            $pk_equality = false;
          }
        }

        if ($pk_equality)
        {
          $this->checkChanges($tableData['primary_key'], $remote_record, $local_record);
          $primary = [];

          // Generate and store primary key, because PK can be complex.
          foreach ($tableData['primary_key'] as $primary_key)
          {
            $primary[] = $remote_record->fields[$primary_key]->fieldValue;
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
   * @param Record $remoteRecord
   * @param Record $localRecord
   */
  private function checkChanges($primaryKey, $remoteRecord, $localRecord)
  {
    $is_changed = false;

    // Pass over each field of two records. And check if we have changes.
    foreach($remoteRecord->fields as $field_name => $remote_field)
    {
      if ($remoteRecord->fields[$field_name] != $localRecord->fields[$field_name]
          and !in_array($field_name, $primaryKey))
      {
        $is_changed = true;
      }
    }

    if ($is_changed)
    {
      $this->writeChanges($remoteRecord);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Passes over each field of a secondary key. If new secondary key not exists in list of existing secondary keys,
   * goes to write added record.
   *
   * @param array  $existingSecondaryKeys The list of existing secondary keys.
   * @param array  $tableData             Data about table, exist metadata.
   * @param string $tableName             The name of a table which we passing.
   */
  private function getAddingsWithSecondaryKey($existingSecondaryKeys, $tableData, $tableName)
  {
    $secondary_key = $tableData['secondary_key'][0];
    $existing_secondary_keys = $existingSecondaryKeys;

    foreach($this->remoteFileData[$tableName]->records as $remote_record)
    {
      if (!in_array($remote_record->fields[$secondary_key]->fieldValue, $existing_secondary_keys))
      {
        $this->writeAddings($remote_record);
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Passes over each field of a primary key. If new primary key not exists in list of existing primary keys,
   * goes to write added record.
   *
   * @param array  $existingPrimaryKeys The list of existing primary keys.
   * @param array  $tableData           Data about table, exist metadata.
   * @param string $tableName           The name of a table which we passing.
   */
  private function getAddingsWithoutSecondaryKey($existingPrimaryKeys, $tableData, $tableName)
  {
    // Pass over records.
    foreach($this->remoteFileData[$tableName]->records as $remote_record)
    {
      $primary_keys = [];

      // Pass over columns of a primary key. And generate a primary key of separate columns of PK.
      foreach($tableData['primary_key'] as $primary_key)
      {
        $primary_keys[] = $remote_record->fields[$primary_key]->fieldValue;
      }

      // Check if array with existing primary keys, have this primary key. If not, we write added record.
      if (!in_array($primary_keys, $existingPrimaryKeys))
      {
        $this->writeAddings($remote_record);
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Passes over each field in in local and remote record list, by secondary key. Checks if local record exists in
   * remote records changes 'is_exists' flag to true. If flag stays at false, we don't have this record in remote file,
   * so outputs info about deletions.
   *
   * @param array  $tableData Data about table, exist metadata.
   * @param string $tableName The name of a table which we passing.
   */
  private function getDeletionsWithSecondaryKey($tableData, $tableName)
  {
    $secondary_key = $tableData['secondary_key'][0];

    foreach($this->localFileData[$tableName]->records as $local_record)
    {
      $is_exists = false;
      foreach($this->remoteFileData[$tableName]->records as $remote_record)
      {
        // If secondary keys are equals, so record is exists, we continue passing
        if ($remote_record->fields[$secondary_key]->fieldValue == $local_record->fields[$secondary_key]->fieldValue)
        {
          $is_exists = true;
        }
      }

      // If we don't found equality, we know that record is deleted, we write info about it.
      if (!$is_exists)
      {
        $this->writeDeletions($local_record);
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Passes over each field in in local and remote record list, by primary key. Checks if local record exists in
   * remote records, changes 'is_exists' flag to true. If flag stays at false, we don't have this record in remote file,
   * so outputs info about deletions.
   *
   * @param array  $tableData Data about table, exist metadata.
   * @param string $tableName The name of a table which we passing.
   */
  private function getDeletionsWithoutSecondaryKey($tableData, $tableName)
  {
    foreach($this->localFileData[$tableName]->records as $local_record)
    {
      $is_exists = false;
      foreach($this->remoteFileData[$tableName]->records as $remote_record)
      {
        $local_pk = [];
        $remote_pk = [];

        // Generating list of columns from primary keys for future check on equality.
        foreach ($tableData['primary_key'] as $primary_key)
        {
          $local_pk[] = $local_record->fields[$primary_key]->fieldValue;
          $remote_pk[] = $remote_record->fields[$primary_key]->fieldValue;
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
        $this->writeDeletions($local_record);
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Outputs the info aboute changed columns in record.
   *
   * @param Record $remoteRecord
   */
  private function writeChanges($remoteRecord)
  {
    $this->io->text(sprintf('<note>%s</note>', OutputFormatter::escape('Updated:')));
    $output = "(";

    foreach($remoteRecord->fields as $field_name => $remote_field)
    {
      $output = $output." {$remote_field->fieldValue},";
    }

    $output = rtrim($output, ", ");
    $output = $output." )";
    $this->io->text(sprintf('%s', $output));
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Outputs information about added records.
   *
   * @param Record $remoteRecord
   */
  private function writeAddings($remoteRecord)
  {
    $this->io->text(sprintf('<fso>%s</fso>', OutputFormatter::escape('Inserted:')));
    $output = "(";

    foreach($remoteRecord->fields as $field_name => $remote_field)
    {
      $output = $output." {$remote_field->fieldValue},";
    }

    $output = rtrim($output, ", ");
    $output = $output." )";
    $this->io->text(sprintf('%s', $output));
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Outputs information about removed records.
   *
   * @param $record
   */
  private function writeDeletions($record)
  {
    $this->io->text(sprintf('<sql>%s</sql>', OutputFormatter::escape('Deleted:')));
    $output = "(";

    foreach($record->fields as $field_name => $field)
    {
      $output = $output." {$field->fieldValue},";
    }

    $output = rtrim($output, ", ");
    $output = $output." )";
    $this->io->text(sprintf('%s', $output));
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------