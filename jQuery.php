<?php
require_once __DIR__ . "/Inc/BuildQueryComponents.php";

class Doc {
    private string $loadedModuleName = "";
    public string $string_recreated = "";

    /**
     * @var false|string
     */
    public false|string $html;
    private bool $prove = false;
    private array $query_string_array;
    private string $xpath_string;
    /**
     * @var mixed
     */
    private $data;

    public function __construct()
    {

    }

    /**
     * @note the result will have the HTML with CSS to prove it is correct
     * @return $this
     */
    public function prove() {
        $this->prove = true;
        return $this;
    }

    /**
     * @param DOMDocument|DOMXPath|string|DOMNode|DOMElement|DOMNodeList $object
     * @return Doc
     * @note
     * ALLOWED
     * - html
     * - DOMDocument
     * - DOMXPath
     * - filename
     * - DOMNode
     * - DOMElement
     * - DOMNodeList
     */
    public function setDoc(DOMDocument | DOMXPath | string | DOMNode | DOMElement | DOMNodeList $object): static
    {
        $html = false;

        try {
            if(is_string($object) && str_contains(" " . $object, "<")) {
                $html = $object;
            }
            elseif(is_string($object) && is_file($object)) {
                $html = file_get_contents($object);
            }
            elseif($object instanceof DOMDocument) {
                $html = $object->saveHTML($object);
            }
            elseif($object instanceof DOMXPath) {
                $html = $object->document->saveHTML();
            }
            elseif($object instanceof DOMNodeList) {
                if(version_compare(phpversion(), '5.3.6', '<')) {
                    throw new Exception("You PHP-Version must be higher than 5.3.6");
                }

                if(count($object) > 0) {
                    $html = $object->item(0)->ownerDocument->saveHTML($object->item(0));
                } else {
                    $html = "";
                }
            }
            elseif ($object instanceof DOMNode) {
                if(version_compare(phpversion(), '5.3.6', '<')) {
                    throw new Exception("You PHP-Version must be higher than 5.3.6");
                }

                $html = $object->ownerDocument->saveHTML($object);
            }
            elseif($object instanceof DOMElement) {
                $innerHTML= '';
                $children = $object->childNodes;
                foreach ($children as $child) {
                    $innerHTML .= $child->ownerDocument->saveXML( $child );
                }

                $html = $innerHTML;
            }
            elseif($html == false) {
                $this->pre($object);
                throw new Exception("You passed a wrong object to setDoc()");
            }
        } catch (Exception $e) {
            die($e);
        }

        $this->html = $html;

        return $this;
    }

    public function _(string $queryString): DOMNodeList|bool
    {
        try {
            if($this->html === NULL) {
                throw new Exception("you need to call setDoc() first");
            }
        } catch (Exception $e) {
            die($e);
        }

        // -- success
        $queryStringArray = (new buildQueryComponents)->build($queryString);
        $html = $this->html;

        // -- initialize DOMDocument
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML($html, LIBXML_HTML_NOIMPLIED);
        libxml_use_internal_errors();

        // -- initialize DOMXPath
        $xPath = new DOMXpath($doc);

        // -- generate xPath expression
        $xPathExpression = $this->getXPathExpression($queryStringArray);

        // -- prove
        if($this->prove == true) {
            $this->proveHTML($html, $queryString);
        }

        return $xPath->query($xPathExpression);
    }

    public function buildXPathString($currentNodes): string
    {
        $commas = [];

        foreach($currentNodes as $currentNode) {

            $string = "";

            $all_attributes = [];

            $saveString = function($content) use(&$string) {
                $string .= $content;
                $this->string_recreated .= $content;
            };

            // -- pseudo classes
            // "position()" needs to be the first one
            $string_pseudo_classes = [];
            if(isset($currentNode["pseudo"])) {
                $pseudo_classes = $currentNode["pseudo"];
                foreach($pseudo_classes as $key => $values) {

                    try {
                        $module_pseudo_classes = $this->getModule("Pseudo_classes");
                        if($module_pseudo_classes == false) {
                            throw new Exception("module does not exist");
                        }

                        $this->setData([
                            "values" => $values
                        ]);

                        $module_pseudo_classes_entry = $module_pseudo_classes->getEntry($key, "first");
                        if($module_pseudo_classes_entry === false) {
                            throw new Exception("module entry for '" . $key . "' does not exist");
                        }

                    } catch(Exception $e) {
                        die($e);
                    }

                    if($module_pseudo_classes_entry !== NULL) {
                        $string_pseudo_classes[] = $module_pseudo_classes_entry;
                    }
                }
            }

            if(count($string_pseudo_classes) > 0) {
                $saveString("[" . implode(" and ", $string_pseudo_classes) . "]");
            }

            // -- tag name
            if(isset($currentNode["tag_name"]) && $currentNode["tag_name"] !== "*") {
                $all_attributes[] = 'name()="' . $currentNode["tag_name"] . '"';
            }

            // -- attributes
            if(isset($currentNode["attr"])) {
                $attributes = $currentNode["attr"];
                foreach ($attributes as $attributeName => $attribute) {
                    foreach ($attribute as $operation) {
                        $attr_operator = $operation["operator"];
                        $attr_value = $operation["value"];

                        $__result = match ($attr_operator) {
                            "=" => '@' . $attributeName . '="' . $attr_value . '"',
                            "" => ($attr_value === "") ? '@' . $attributeName . '' : NULL,
                            "~=" => 'contains(concat(" ", normalize-space(@' . $attributeName . '), " "), " ' . $attr_value . ' ")',
                            "|=" => '@' . $attributeName . '="' . $attr_value . '" or starts-with(@' . $attributeName . ', concat("' . $attr_value . '", "-"))',
                            "^=" => 'starts-with(@' . $attributeName . ', "' . $attr_value . '")',
                            "\$=" => 'ends-with(@' . $attributeName . ', "' . $attr_value . '")',
                            "*=" => 'contains(@' . $attributeName . ',"' . $attr_value . '")',
                        };

                        if($__result !== NULL) {
                            $all_attributes[] = $__result;
                        }
                    }
                }
            }

            if(count($all_attributes) > 0) {
                $saveString("[" . implode(" and ", $all_attributes) . "]");
            }

            // -- pseudo classes
            $string_pseudo_classes = [];
            if(isset($currentNode["pseudo"])) {
                $pseudo_classes = $currentNode["pseudo"];
                foreach($pseudo_classes as $key => $values) {
                    try {
                        $module_pseudo_classes = $this->getModule("Pseudo_classes");
                        if($module_pseudo_classes == false) {
                            throw new Exception("module does not exist");
                        }

                        $this->setData([
                            "values" => $values
                        ]);

                        $module_pseudo_classes_entry = $module_pseudo_classes->getEntry($key, "last");
                        if($module_pseudo_classes_entry === false) {
                            throw new Exception("module entry for '" . $key . "' does not exist");
                        }

                        if($module_pseudo_classes_entry !== NULL) {
                            $string_pseudo_classes[] = $module_pseudo_classes_entry;
                        }


                    } catch(Exception $e) {
                        die($e);
                    }
                }
            }

            if(count($string_pseudo_classes) > 0) {
                $saveString("[" . implode(" and ", $string_pseudo_classes) . "]");
            }

            // -- descendant
            if(isset($currentNode["descendant"])) {
                $saveString("//*");
                $string .= $this->buildXPathString($currentNode["descendant"]);
            }

            // -- child
            elseif(isset($currentNode["child"])) {
                $saveString("/*");
                $string .= $this->buildXPathString($currentNode["child"]);
            }

            // -- adjacent sibling
            elseif(isset($currentNode["adjacent_sibling"])) {
                $saveString("/following-sibling::*[1]");
                $string .= $this->buildXPathString($currentNode["adjacent_sibling"]);
            }

            // -- general sibling
            elseif(isset($currentNode["general_sibling"])) {
                $saveString("/following-sibling::*");
                $string .= $this->buildXPathString($currentNode["general_sibling"]);
            }

            // -- result string for current node
            $commas[] = $string;
        }

        return implode(" | //*", $commas);
    }

    public function buildNthXQuery(string $previousXQueryString, string $nthString): string
    {
        if(!isset($this->html)) {
            die("html is not initialized");
        }

        // -- initialize DOMDocument
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML($this->html, LIBXML_HTML_NOIMPLIED);
        libxml_use_internal_errors();

        // -- initialize DOMXPath
        $xPath = new DOMXpath($doc);

        $query = $xPath->query($previousXQueryString);
        if($query === false) {
            echo "<pre>" . $previousXQueryString . " is not valid</pre>";
            return "";
        }

        $i = 0;
        $array = [];

        foreach ($query as $item) {
            $i++;

            if(!$this->isNthPosition($nthString, $i)) {
                continue;
            }

            $array[] = "position()=" . $i;
        }

        return (count($array) > 0) ? implode(" or ", $array) : "";
    }

    private function isNthPosition(string $formula, int $current_position): bool
    {
        switch(strtolower($formula)) {
            case "even":
                $output_array = [];
                $output_array["a"] = 2;
                $output_array["n"] = "n";
                $output_array["sign"] = "+";
                $output_array["b"] = 0;
                break;

            case "odd":
                $output_array = [];
                $output_array["a"] = 2;
                $output_array["n"] = "n";
                $output_array["sign"] = "+";
                $output_array["b"] = 1;
        }

        if(!isset($output_array)) {
            $preg_match = preg_match('/(?<a>[0-9]+|)(?<n>n|-n)(?<sign>\+|\-|)(?<b>[0-9]+|)/', $formula, $output_array);
            if(!$preg_match) {
                die("Formula doesn't have a correct syntax (#1): " . $formula);
            }
        }

        $a = ($output_array["a"] !== "") ? $output_array["a"] : 1;
        $b = ($output_array["b"] !== "") ? $output_array["b"] : 0;
        $sign = ($output_array["sign"] !== "") ? $output_array["sign"] : "+";
        $n = ($output_array["n"] !== "") ? strtolower($output_array["n"]) : "";

        try {
            // "0n+1"
            if((string) $a === (string) "0") {
                if($sign === "-") {
                    die("I don't know how to handle '-' #1");
                }

                return ((string) $current_position === (string) $b);
            }
            // same as previous
            // "1"
            elseif($n === "") {
                if($sign === "-") {
                    die("I don't know how to handle '-' #2");
                }

                return ((string) $current_position === (string) $a);
            }
            // "2n+1"
            elseif($n === "n") {
                $eval_formula = "(" . $current_position . ' - ' . $sign . $b . ') / ' . $a;
                $result = eval('return ' . $eval_formula . ';');

                return ctype_digit((string) $result);
            }
            // "-n+3"
            elseif($n === "-n") {
                if($sign === "-") {
                    die("I don't know how to handle '-' #3");
                }

                return ((string) $current_position <= (string) $b);
            }
            else {
                die("Formula doesn't have a correct syntax (#2): " . $formula);
            }
        } catch (ParseError $e) {
            if(isset($eval_formula)) {
                die("formula has wrong syntax: " . $eval_formula);
            } else {
                $array = [
                    "file" => $e->getFile(),
                    "line" => $e->getLine(),
                    "a" => $e->getMessage(),
                    "trace" => $e->getTraceAsString()
                ];

                $this->pre($array);
                exit;
            }
        }

    }

    private function getXPathExpression(array $queryStringArray): string
    {
        $this->query_string_array = $queryStringArray;

        $this->string_recreated = "//*";
        $result = "//*" . $this->buildXPathString($queryStringArray);

        $this->xpath_string = $result;

        return $result;
    }

    private function proveHTML(bool|string $html, string $queryString)
    {
        $rand = "a" . rand();
        echo /** @lang HTML */"<style>#" . $rand . " * {border: 1px solid black; margin-left: 10px} #" . $rand . " " . $queryString . " { color: white; background: red; display: block; }</style><div><div><b>" . $queryString . "</b></div><div id='" . $rand . "' style='max-height: 400px; height: auto; width: 100%; overflow: auto; border: 1px solid black;'>" . $html . "</div></div>";
    }

    public function pre($result)
    {
        echo "<pre>";
        var_dump($result);
        echo "</pre>";
    }

    public function getQueryStringArray(): array
    {
        return $this->query_string_array;
    }

    public function getXPathAsString(): string
    {
        return $this->xpath_string;
    }

    private function setData(mixed $data)
    {
        $this->data = $data;
    }

    public function getData($key = NULL) {
        if($key != NULL) {
            return $this->data[$key];
        }

        return $this->data;
    }

    public function getModule(string $name) {
        $name = $this->upperFirst($name);
        $path = __DIR__ . "/Modules/" . $name;
        if(!is_dir($path)) {
            return false;
        }

        $this->loadedModuleName = $name;
        return $this;
    }

    public function getEntry(string $name, string $key) {
        try {
            if($this->loadedModuleName === "") {
                throw new Exception("call getModule() first");
            }

            $name = $this->upperFirst($name);
            $path = __DIR__ . "/Modules/" . $this->loadedModuleName . "/" . $name . ".php";
            if(!is_file($path)) {
                return false;
            }

            require_once $path;
            $functionName = $this->classComforn($name) . "_" . $this->upperFirst($key);

            if(!function_exists($functionName)) {
                return NULL;
            }

            return $functionName($this);
        } catch (Exception $e) {
            die($e);
        }
    }

    private function upperFirst(string $string) {
        $string = strtolower($string);
        return ucfirst($string);
    }

    private function classComforn(string $string) {
        $string = str_replace("-", "_", $string);
        return $string;
    }
}