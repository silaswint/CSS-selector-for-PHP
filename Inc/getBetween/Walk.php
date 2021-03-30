<?php
class Walk {
    private int $i = 0;
    private array $array = [];
    private array $route = [];
    private bool $debug = false;

    private function msg($string, $color = "black") {
        $space = str_repeat("&nbsp;&nbsp;&nbsp;&nbsp; ", $this->i);

        if(is_array($string) || is_object($string)) {
            ob_start();
            var_dump($string);
            $result = ob_get_clean();
            $string = trim($result);
        }

        $bold = ($this->i % 2 === 0) ? "; font-weight: bold" : "";
        echo $space . "<span style='color: " . $color . $bold . "'>" . $string . "</span><br />\n";
    }

    function doProcess(array $array, $callback, $parent = NULL, $route = []) {
        $this->i++;

        if($this->debug) {
            $this->msg("in new process now (" . $this->i . ")", "red");
        }

        if($this->i === 1) {
            if($this->debug) {
                $this->msg("first call of function");
            }

            $this->array = $array;

            $callback(NULL, $array, $parent);
        }

        if(count($route) > 0) {
            if($this->debug) {
                $this->msg("setting new route:");
                $this->msg($route);
            }

            $this->route = $route;
        }

        $i_foreach = 0;

        if($this->debug) {
            $this->msg(":START FOREACH");
        }

        foreach ($array as $key => $value) {
            $i_foreach++;

            if($this->debug) {
                $this->msg(":FOREACH-ROW: " . $i_foreach);
            }


            $this->route[] = $key;

            if($this->debug) {
                $this->msg("adding new key to route: " . $key . "", "green");
                $this->msg($array, "green");
            }

            if(is_array($value)) {
                $new_parent = clone new ($this->parent());
                $new_parent->setKey($key);
                $new_parent->setArray($this->array);
                $new_parent->setRoute($this->route);

                if($this->debug) {
                    $this->msg("go to new Process with this route + value:", "purple");
                    $this->msg($this->route, "purple");
                    $this->msg($value, "purple");
                }

                $callback($key, $value, $parent);
                $this->doProcess($value, $callback, $new_parent, $this->route);
                continue;
            }

            if($parent === NULL) {
                if($this->debug) {
                    $this->msg("parent === NULL");
                }

                $parent = clone new ($this->parent());
            }

            $parent->setKey(NULL);
            $parent->setArray($this->array);
            $parent->setRoute($this->route);

            $callback($key, $value, $parent);

            if($this->debug) {
                $this->msg("remove last element from route", "pink");
            }

            array_pop($this->route);

            if($this->debug) {
                $this->msg($this->route);
            }
        }

        array_pop($this->route);

        if($this->debug) {
            $this->msg(":ENDE FOREACH");
        }
    }

    private function parent(): object
    {
        return new class {
            private string|NULL $key = "";
            private array $array = [];
            private array $route = [];

            public function setKey($name) {
                $this->key = $name;
            }

            public function getKey(): string
            {
                return $this->key;
            }

            public function setArray($array) {
                $this->array = $array;
            }

            public function getArray(): array
            {
                return $this->array;
            }

            public function setRoute($array) {
                $this->route = $array;
            }

            public function getRoute($int = NULL) {
                $route = array_reverse($this->route);

                if($int === NULL) {
                    return $route;
                } else {
                    return (isset($route[$int])) ? $route[$int] : false;
                }
            }
        };
    }

    /** @note watch out for callback, first comes value and then key
     * @param array $array
     * @param null|callable $callback
     * @return array
     */
    function array_filter_recursive(array $array, $callback = null): array
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if (!$callback($value, $key)) {
                    unset($array[$key]);
                    $array[$key] = $this->array_filter_recursive($value, $callback);
                } else {
                    $array[$key] = $value;
                }

            } else {
                if (!$callback($value, $key)) {
                    unset($array[$key]);
                }
            }
        }

        return $array;
    }
}