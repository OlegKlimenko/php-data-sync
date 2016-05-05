<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\DataSync\DBObjects;

//----------------------------------------------------------------------------------------------------------------------
/**
 * Class for records.
 */
class Record
{
  /**
   * The list of fields in record.
   *
   * @var array
   */
  public $fields;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param array $record
   */
  public function __construct($record)
  {
    foreach($record as $field_name => $field_value)
    {
      $this->fields[$field_name] = new Field($field_name, $field_value);
    }
  }

  // -------------------------------------------------------------------------------------------------------------------

}

// ---------------------------------------------------------------------------------------------------------------------