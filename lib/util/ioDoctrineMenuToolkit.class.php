<?php
/**
 * General utility class for thisp lugin
 *
 * @package    ioDoctrineMenuItemPlugin
 * @subpackage util
 * @author     Brent Shaffer <bshafs@gmail.com>
 * @author     Ryan Weaver <ryan.weaver@iostudio.com>
 */

class ioDoctrineMenuToolkit
{
  /**
   * Takes a flat array an organizes it into a nested set array
   *
   * @param  array  $arrs       The flat array to convert to nested set array
   * @param  int    $rootLevel The level that should be considered root
   * @param  string $depthKey  The array key where level/depth is specified
   * @return array
   */
  public static function nestify($setArr, $rootLevel = 0, $depthKey = 'level')
  {
    $nested = array();
    $depths = array();
  
    foreach ($setArr as $key => $arr)
    {
      if (!isset($arr[$depthKey]))
      {
        throw new InvalidArgumentException(sprintf('Passed array does not have key "%s"', $depthKey));
      }

      if ($arr[$depthKey] == $rootLevel)
      {
        $nested[$key] = $arr;
        $depths[$arr[$depthKey] + 1] = $key;
      }
      else
      {
        $parent =& $nested;
        for ($i = $rootLevel + 1; $i <= $arr[$depthKey]; $i++)
        {
          $parent =& $parent[$depths[$i]]['children'];
        }
      
        $parent[$key] = $arr;
        $depths[$arr[$depthKey] + 1] = $key;
      }
    }

    return self::arrayReindex($nested);
  }

  /**
   * Recursively reindexes the "children" key in an array of arrays
   *
   * @example
   * $arr =
   * array(
   *   array(
   *    'children' => array(
   *       2 => 'foo',
   *       0 => 'bar',
   *       1 => 'baz'
   *     )
   *   )
   * );
   * $arr = ioDoctrineMenuToolkit::arrayReindex($arr);
   *
   * Result:
   *   array(
   *     array(
   *       'children' =>
   *         0 => 'foo',
   *         1 => 'bar',
   *         2 => 'baz',
   *       )
   *     )
   *   )
   *
   * @param  array $array The array to reindex
   * @return array 
   */
  public static function arrayReindex($array)
  {
    foreach ($array as $key => $value) 
    {
      if (is_array($value) && isset($value['children'])) 
      {
        $array[$key]['children'] = array_values(self::arrayReindex($value['children']));
      }
    }

    return $array;
  }
}
