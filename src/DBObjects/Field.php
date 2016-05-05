<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\DataSync\DBObjects;

//----------------------------------------------------------------------------------------------------------------------
/**
 * Class for fields.
 */
class Field
{
  /**
   * The name of a field.
   * 
   * @var string
   */
  public $fieldName;

  /**
   * The value of a field.
   *
   * @var string
   */
  public $fieldValue;

  /**
   * Additional data.
   *
   * @var string
   */
  public $additionalValue = null;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param string $fieldName  The name of a field.
   * @param string $fieldValue The value of a field.
   */
  public function __construct($fieldName, $fieldValue)
  {
    $this->fieldName = $fieldName;
    $this->fieldValue = $fieldValue;
  }

  // -------------------------------------------------------------------------------------------------------------------

}

// ---------------------------------------------------------------------------------------------------------------------