<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\DataSync\Exception;
//----------------------------------------------------------------------------------------------------------------------
/**
 * Class for runtime exceptions.
 */
class RuntimeException extends \RuntimeException
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param string $theFormat The format string of the error message, see
   *                          [sprintf](http://php.net/manual/function.sprintf.php).
   */
  public function __construct($theFormat)
  {
    $args = func_get_args();
    array_shift($args);
    if (isset($args[0]) && is_a($args[0], '\Exception'))
    {
      $previous = array_shift($args);
    }
    else
    {
      $previous = null;
    }
    parent::__construct(vsprintf($theFormat, $args), 0, $previous);
  }
  //--------------------------------------------------------------------------------------------------------------------
}
//----------------------------------------------------------------------------------------------------------------------