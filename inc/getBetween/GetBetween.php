<?php
require_once __DIR__ . "/autoload.php";

class getBetween
{
    private array|NULL $result = NULL;
    private string|NULL $original_string = NULL;
    private string|NULL $start_string = NULL;

    private array|NULL $options = NULL;

    /**
     * @param string $string
     * @param string $start_string
     * @param string $end_string
     * @return array
     */
    public function getArray(string $string, string $start_string = "{", string $end_string = "}"): array
    {
        // it doesnt find it, when it is on first position
        $string = " " . $string;

        if($this->original_string === NULL) {
            $this->original_string = $string;
        }

        if($this->start_string === NULL) {
            $this->start_string = $start_string;
        }

        $siblings = [];

        $j = 0;

        // -- find all siblings
        $new_string = $string;
        $pos_end_total = 0;

        while(true) {
            if($pos_end_total >= strlen($string)) {
                break;
            }

            $main = "";

            $pos_start = stripos($new_string, $start_string);
            $pos_end = stripos($new_string, $end_string);

            if($pos_start == false || $pos_end == false) {
                break;
            }

            $start_string_length = strlen($start_string);
            $next_start_result = $pos_start;

            // -- find next position
            while(true) {
                $next_start_result = stripos($new_string, $start_string, ($start_string_length + $next_start_result));
                if($next_start_result !== false && $next_start_result < $pos_end) {

                    $next_end_result = stripos($new_string, $end_string, ($pos_end + 1));
                    if($next_end_result !== false) {
                        // überschreibe end mit neuem Wert
                        $pos_end = $next_end_result;
                    }
                } else {
                    break;
                }
            }

            // -- concat all letters
            $main_start = $pos_start + strlen($start_string);
            $string_splitted = str_split($new_string);
            for ($i = $main_start; $i < $pos_end; $i++) {
                $main .= $string_splitted[$i];
            }

            $pos_end_total += $pos_end + strlen($end_string);

            $new_pos_start = $pos_end_total - strlen($main) - strlen($start_string) + 1;
            $new_pos_end = $new_pos_start + strlen($main);

            $siblings[] = [
                "main" => $main,
                "pos_start" => $new_pos_start,
                "pos_end" => $new_pos_end,
                "parent" => $string,
                "original_string" => $this->original_string,
            ];

            // for the next loop (the space because it doesn't find it on first position
            $new_string = " " . substr($string, $pos_end + strlen($end_string));

            $j++;

            if($j == 50) {
                var_dump("SAFETY FOR TOO MANY RAM");
                break;
            }
        }

        $result = [];
        foreach ($siblings as $sibling) {
            $sibling_main = $sibling["main"];
            $sibling_pos_start = $sibling["pos_start"];
            $sibling_pos_end = $sibling["pos_end"];

            if($sibling_main !== "") {
                $result[] = [
                    "node" => $sibling_main,
                    "pos_start" => $sibling_pos_start,
                    "pos_end" => $sibling_pos_end,
                    "parent" => $sibling["parent"],
                    "original_string" => $sibling["original_string"],
                    "children" => $this->getArray($sibling_main, $start_string, $end_string),
                ];
            }
        }

        $this->result = $result;
        return $result;
    }

    public function filter($options): array|string
    {
        $this->options = $options;
        $result = $this->result;

        if(!is_array($result)) {
            die("Fehler. Kein Array übergeben.");
        }

        // -- before_string (searches for something like div(...), relating to what you types as before_string
        if(isset($this->options["before_string"]) && is_string($this->options["before_string"])) {

            $result = (new Walk)->array_filter_recursive($result, function($value, $key) {

                if(isset($value["node"])) {
                    $before_string = substr(
                        $value["original_string"],
                        ($value["pos_start"] - strlen($this->start_string) - strlen($this->options["before_string"]) - 1),
                        strlen($this->options["before_string"])
                    );

                    if($before_string === $this->options["before_string"]) {
                        return true;
                    }
                }

                return false;
            });

        }

        // -- flat
        if(isset($this->options["flat"]) && $this->options["flat"] == true) {
            global $flat_array;
            $flat_array = [];

            (new Walk)->doProcess($result, function($key, $value, $parent) {
                if($parent != NULL &&
                    (($parent->getRoute(2) === "children" && $key === "node")
                    || ($parent->getRoute(2) == false && $key === "node"))
                ) {
                    global $flat_array;
                    $flat_array[] = $value;
                }

            });

            $result = $flat_array;
        }

        return $result;
    }

    function explodeExceptIf($str, $delimiter, $start_string = "(", $end_string = ")"): array
    {
        $ret = array();
        $in_parenths = 0;
        $pos = 0;

        for($i=0; $i < strlen($str); $i++)
        {
            $c = $str[$i];

            if($c === $delimiter && !$in_parenths) {
                $ret[] = substr($str, $pos, $i-$pos);
                $pos = $i+1;
            }
            elseif((!is_array($start_string) && $c === $start_string) || (is_array($start_string) && in_array($c, $start_string))) {
                $in_parenths++;
            }
            elseif((!is_array($end_string) && $c === $end_string) || (is_array($end_string) && in_array($c, $end_string))) {
                $in_parenths--;
            }
        }

        if($pos > 0) {
            $ret[] = substr($str, $pos);
        } else {
            $ret[] = $str;
        }

        return $ret;
    }
}