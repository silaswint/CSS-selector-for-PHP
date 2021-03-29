<?php
class Removed_functions {
    /* @source /jQuery.php */
    private function remove_last_xquery(string $xqueryString) {
        $explode = (new getBetween())->explodeExceptIf($xqueryString, "/", "[", "]");
        $last_part = $this->array_last($explode);

        return preg_replace("/" . preg_quote($last_part, "/") . "$/", "", $xqueryString);
    }

    private function array_last(array $array) {
        return $array[count($array) - 1];
    }

    /* @source /inc/BuildQueryComponents.php */
    private function flatToAssociative($array, $name = "children")
    {
        $array_reverse = array_reverse($array);
        array_walk($array_reverse, function ($value, $key) use (&$array, $name) {
            $array = $key ? array_merge(
                $value,
                [
                    $name => $array,
                ]
            ) : $value;
        });

        return $array;
    }

    /* @source /inc/getBetween/Walk.php */
    function array_walk_recursive($input, $callback = null) {
        $it = new RecursiveIteratorIterator(new RecursiveArrayIterator($input), RecursiveIteratorIterator::SELF_FIRST);

        while($it->valid())
        {
            $callback($it->current(), $it->key());
            $it->next();
        }
    }
}