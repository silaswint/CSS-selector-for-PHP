<?php
include __DIR__ . "/getBetween/getBetween.php";

class buildQueryComponents
{
    private int $i_loop = 0;

    function build($queryString): bool|array
    {
        // dont separate when comma is in some of these arrays
        $commas = (new getBetween())->explodeExceptIf($queryString, ",", ["[", "("], ["]", ")"]);
        $elements = [];

        foreach($commas as $comma_queryString) {

            $comma_queryString = trim($comma_queryString);
            $queryStringRebuilded = "";

            $element = [];

            $pseudoClasses = [];

            // -- set original query string
            $element["query_string"] = $comma_queryString;

            // -- get value (like: "2" or "2n+1") (don't find "odd" or "even" here, it will be added when tag name is searched)
            preg_match('/^(?:(?:-|[0-9n]))/', $comma_queryString, $preg_match_matches_pseudo_classes_values);

            if(isset($preg_match_matches_pseudo_classes_values[0])) {
                $queryStringRebuilded .= $comma_queryString;
            }

            // -- get element name
            preg_match('/^(?:\*|(?:[a-zA-ZÄÖÜäöüß][a-zA-Z0-9ÄÖÜäöüß]*))/', $comma_queryString, $preg_match_matches_tag_name);

            if(isset($preg_match_matches_tag_name[0])) {
                $element["tag_name"] =  $preg_match_matches_tag_name[0];
                $queryStringRebuilded .= $element["tag_name"];
            }

            // -- find class / id
            while(true) {
                preg_match("/^" . preg_quote($queryStringRebuilded, '/') . "((?:\.|#)[a-zA-ZÄÖÜäöüß][a-zA-Z0-9ÄÖÜäöüß\-_]*)/", $comma_queryString, $preg_match_matches_class_or_id);

                if(!isset($preg_match_matches_class_or_id[1])) {
                    break;
                }

                $class_or_id = $preg_match_matches_class_or_id[1];
                $queryStringRebuilded .= $class_or_id;

                $first_char_class_or_id = $this->firstChar($class_or_id);
                switch ($first_char_class_or_id) {
                    case ".":
                        $element["attr"]["class"][] = [
                            "operator" => "~=",
                            "value" => $this->offsetChars($class_or_id, 1)
                        ];
                        break;

                    case "#":
                        $element["attr"]["id"][] = [
                            "operator" => "=",
                            "value" => $this->offsetChars($class_or_id, 1)
                        ];
                        break;
                }

                $substr_class_or_id = substr($comma_queryString, strlen($queryStringRebuilded), 1);
                if($substr_class_or_id === ".") {
                    continue;
                }

                break;
            }

            // -- find more attributes
            $pattern = '/^' . preg_quote($queryStringRebuilded, "/") . '(\[.*\])(?: |:)/';
            preg_match($pattern, $comma_queryString . " ", $preg_match_matches_attributes);

            if (isset($preg_match_matches_attributes[1])) {
                $attributesFromQueryString = $preg_match_matches_attributes[1];

                $queryStringRebuilded .= $attributesFromQueryString;

                $getBetween = new getBetween();
                $getBetween->getArray($attributesFromQueryString, "[", "]");

                $attributeSelectors = $getBetween->filter(["flat" => true]);

                foreach ($attributeSelectors as $attributeSelector) {
                    preg_match('/[ ]*([a-zA-Z][a-zA-Z0-9\-_]*)[ ]*([^\w\d]?\=){0,2}[ ]*(.*)/', $attributeSelector, $array);

                    $attribute_name = trim($array[1]);
                    $operator = $array[2];
                    $attribute_value = $array[3];

                    $first_char_of_value = $this->firstChar($attribute_value);
                    $last_char_of_value = $this->lastChar($attribute_value);

                    // attribute name is empty
                    if ($attribute_name === "") {
                        return false;
                    }

                    // attribute_value is missing
                    if ($operator !== "" && $attribute_value === "") {
                        return false;
                    }

                    // attribute_value is invalid
                    if ($operator !== "" && $attribute_value !== "" && $first_char_of_value !== $last_char_of_value) {
                        return false;
                    }

                    // delete first and last char of value, what should be either ' or "
                    $attribute_value = substr(substr($attribute_value, 0, -1), 1);

                    $element["attr"][$attribute_name][] = [
                        "operator" => $operator,
                        "value" => $attribute_value
                    ];
                }
            }

            // -- check for pseudo classes
            $query_string_first = (new getBetween())->explodeExceptIf($comma_queryString, " ", "(", ")")[0];
            $__first_colon = $this->firstPositionFrom($query_string_first, ":");

            $preg_match_matches_pseudo_classes = (new getBetween())->explodeExceptIf($__first_colon, " ", "(", ")");

            if (isset($preg_match_matches_pseudo_classes[0])) {
                $preg_match_matches_pseudo_classes = $preg_match_matches_pseudo_classes[0];
                $preg_match_matches_pseudo_classesShortend = $preg_match_matches_pseudo_classes;

                // delete all chars till ":" because tag name doesnt matter here
                $position_till_colon = strpos(" " . $preg_match_matches_pseudo_classesShortend, ":");
                if($position_till_colon !== false) {
                    $preg_match_matches_pseudo_classesShortend = substr(
                        $preg_match_matches_pseudo_classesShortend,
                        strpos(" " . $preg_match_matches_pseudo_classesShortend, ":") -1
                    );

                    // extend the rebuilded query
                    $queryStringRebuilded .= $preg_match_matches_pseudo_classesShortend;
                }

                // delete brackets first, so we have a clear :not:last-child
                $getBetween = new getBetween();
                $getBetween->getArray($preg_match_matches_pseudo_classesShortend, "(", ")");
                $brackets = $getBetween->filter(["flat" => true]);

                foreach ($brackets as $bracket) {
                    $preg_match_matches_pseudo_classesShortend = str_replace("(" . $bracket . ")", "", $preg_match_matches_pseudo_classesShortend);
                }

                // delete first char, it is always a ":"
                $preg_match_matches_pseudo_classesShortend = $this->removeFirstChar($preg_match_matches_pseudo_classesShortend);

                // seperate it
                $pseudoClassNames = explode(":", $preg_match_matches_pseudo_classesShortend);

                $pseudoClassNames = array_unique($pseudoClassNames);

                // check for empty values
                $pseudoClassNames = array_filter($pseudoClassNames, fn($value) => $value !== '');

                // now we know what we want to search
                foreach ($pseudoClassNames as $pseudoClassName) {
                    $getBetween = new getBetween();
                    $getBetween->getArray($preg_match_matches_pseudo_classes, "(", ")");
                    $_values = $getBetween->filter(
                        [
                            "flat" => false,
                            "before_string" => ":" . $pseudoClassName
                        ]
                    );

                    $_new_array = [];
                    foreach ($_values as $_value) {
                        if (!isset($_value["node"])) {
                            continue;
                        }

                        $_new_array[] = $this->build($_value["node"]);
                    }

                    $pseudoClasses[$pseudoClassName] = $_new_array;
                }
            }

            if(is_array($pseudoClasses) && count($pseudoClasses) > 0) {
                $element["pseudo"] = $pseudoClasses;
            }

            // -- check if next position is a space
            $next_relevant = $this->next_relevant_function($comma_queryString, $queryStringRebuilded);

            $next_relevant_position = $next_relevant["position"];
            $next_relevant_string = $next_relevant["string"];

            switch($next_relevant_position) {
                case ">":
                case "+":
                case "~":
                case "":
                case " ":
                    $key_name = match ($next_relevant_position) {
                        ">" => "child",
                        "+" => "adjacent_sibling",
                        "~" => "general_sibling",
                        "", " " => "descendant"
                    };

                    $queryStringRebuilded .= $next_relevant_string;

                    $strlen = strlen($queryStringRebuilded);
                    if($next_relevant_position === "") {
                        $strlen++;
                    }

                    $substr = substr($comma_queryString, $strlen);

                    if($substr !== "" && $substr != false) {
                        $element = array_merge(
                            $element,
                            [
                                $key_name => $this->build(substr($comma_queryString, $strlen))
                            ]
                        );
                    }

                    break;

                default:
                    die("<code>NEXT POSITION '" . $next_relevant_position . "' UNKNOWN</code>");
            }

            $this->i_loop++;
            $elements[] = $element;
        }

        return $elements;
    }

    function removeFirstChar($string) {
        return substr($string, 1);
    }

    function firstChar($string) {
        return substr($string, 0, 1);
    }

    function lastChar($string) {
        return substr($string, -1, 1);
    }

    function offsetChars($string, $offset) {
        return substr($string, $offset);
    }

    private function firstPositionFrom($string, $haystack) {
        $strpos = strpos(" " . $string, $haystack);

        if($strpos != false) {
            return substr($string, $strpos - 1);
        }

        return false;
    }

    private function next_relevant_function($original, $rebuilded) {
        $replaced = str_replace($rebuilded, "", $original);

        // -- check first found position
        $relevant_position_preg_replace = preg_replace('/[\s]+/', ' ', $replaced); // remove unnecessary spaces
        $relevant_position_1 = substr($relevant_position_preg_replace, 0, 1);
        $relevant_position_2 = substr($relevant_position_preg_replace, 1, 1);

        if($relevant_position_1 === " ") {
            $relevant_position = match ($relevant_position_2) {
                ">", ",", "~", "+" => $relevant_position_2,
                default => $relevant_position_1,
            };
        } else {
            $relevant_position = $relevant_position_1;
        }

        $relevant_string_strpos = strpos(" " . $replaced, $relevant_position);
        $relevant_string = substr($replaced, 0, $relevant_string_strpos);

        // -- check second position (it may be " >")

        return [
            "position" => $relevant_position,
            "string" => $relevant_string
        ];
    }
}