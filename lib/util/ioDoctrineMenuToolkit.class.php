<?php

/**
* 
*/
class ioDoctrineMenuToolkit
{
  public static function nestify( $arrs, $root_level = 0, $depth_key = 'level' )
  {
    $nested = array();
    $depths = array();
  
    foreach( $arrs as $key => $arr ) {
      if( $arr[$depth_key] == $root_level ) {
        $nested[$key] = $arr;
        $depths[$arr[$depth_key] + 1] = $key;
      }
      else {
        $parent =& $nested;
        for( $i = $root_level+1; $i <= ( $arr[$depth_key] ); $i++ ) {
          $parent =& $parent[$depths[$i]]['children'];
        }
      
        $parent[$key] = $arr;
        $depths[$arr[$depth_key] + 1] = $key;
      }
    }

    return self::array_reindex($nested);
  }

  public static function array_reindex($array)
  {
    foreach ($array as $key => $value) 
    {
      if (isset($value['children'])) 
      {
        $array[$key]['children'] = array_values(self::array_reindex($value['children']));
      }
    }
    return $array;
  }
}
