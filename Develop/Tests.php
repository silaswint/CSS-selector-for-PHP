<?php
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

include __DIR__ . "/../jQuery.php";
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <style>
        html, body {
            font-family: Arial, serif;
        }
    </style>
</head>
<body>
<?php
function Dom2Array($root) {
    $array = array();

    //list attributes
    if($root->hasAttributes()) {
        foreach($root->attributes as $attribute) {
            $array['_attributes'][$attribute->name] = $attribute->value;
        }
    }

    //handle classic node
    if($root->nodeType == XML_ELEMENT_NODE) {
        $array['_type'] = $root->nodeName;
        if($root->hasChildNodes()) {
            $children = $root->childNodes;
            for($i = 0; $i < $children->length; $i++) {
                $child = Dom2Array( $children->item($i) );

                //don't keep textnode with only spaces and newline
                if(!empty($child)) {
                    $array['_children'][] = $child;
                }
            }
        }

        //handle text node
    } elseif($root->nodeType == XML_TEXT_NODE || $root->nodeType == XML_CDATA_SECTION_NODE) {
        $value = $root->nodeValue;
        if(!empty($value)) {
            $array['_type'] = '_text';
            $array['_content'] = $value;
        }
    }

    return $array;
}

function var_dump_to_string($object) {
    ob_start();
    var_dump($object);
    $result = ob_get_clean();
    return $result;
}

function toArray($object) {
    $array = array();
    foreach ($object as $key => $value) {
        $array[]["nodeValue"] = $value->nodeValue;
        $array[]["textContent"] = $value->textContent;
        $array[]["tagName"] = $value->tagName;

        if ($value->hasAttributes()) {
            foreach ($value->attributes as $attr) {
                $attribute_name = $attr->nodeName;
                $attribute_value = $attr->nodeValue;

                $array[]["attributes"][] = [
                    "name" => $attribute_name,
                    "value" => $attribute_value
                ];
            }
        }
    }

    return $array;
}

function tests_section($title, $html, $query_string, $expected_result, $output = false) {
    echo "<h2>" . $title . "</h2>";

    $doc = (new Doc);

    if($output === true) {
        $doc = $doc->prove();
    }

    $doc = $doc->setDoc($html);

    $query = $doc->_($query_string);

    if($output === true) {
    ?>

    <h3>Selected nodes</h3>
    This is the xPath string:<br />
    <textarea style="width: 500px; height: 30px" readonly><?php echo $doc->getXPathAsString(); ?></textarea><br /><br />

    <?php
    }

    if($query === false) {
        die("xPath has wrong syntax");
    }

    if($output === true) {
    ?>

    <ul>
        <?php
        if(count($query) == 0) {
            echo "<li>(no results)</li>";
        }

        foreach ($query as $element) {
            echo "<li><pre>" . var_dump_to_string(Dom2Array($element)) . "</pre></li>";
        }
        ?>
    </ul>
    <?php
    }

    $result = json_encode(toArray($query));


    if($result === $expected_result) {
        echo "<b style='color: green'>Fine</b>";
    } else {
        echo "<b style='color: red'>ERROR</b>";
        echo "<br />Result should be:<br /><textarea style='width: 500px; height: 100px' readonly>" . $result . "</textarea>";
        die();
    }
}

tests_section(
    "Invalid HTML",
    "<ul><ol>Test</ol></ul>",
    "ul ol",
    '[{"nodeValue":"Test"},{"textContent":"Test"},{"tagName":"ol"}]',
    true
);

tests_section(
    "Single element",
    "<div>Test</div>",
    "div",
    '[{"nodeValue":"Test"},{"textContent":"Test"},{"tagName":"div"}]'
);

tests_section(
    "Class after single element",
    "<div class='example-class'>Test</div>",
    "div.example-class",
    '[{"nodeValue":"Test"},{"textContent":"Test"},{"tagName":"div"},{"attributes":[{"name":"class","value":"example-class"}]}]'
);

tests_section(
    "Multiple classes after single element #1",
    "<div class='example-class'>Test</div>",
    "div.example-class.second-class",
    '[]'
);

tests_section(
    "Multiple classes after single element #2",
    "<div class='example-class second-class'>Test</div>",
    "div.example-class.second-class",
    '[{"nodeValue":"Test"},{"textContent":"Test"},{"tagName":"div"},{"attributes":[{"name":"class","value":"example-class second-class"}]}]'
);

tests_section(
    "Multiple classes after single element #3",
    "<div class='second-class example-class'>Test</div>",
    "div.example-class.second-class",
    '[{"nodeValue":"Test"},{"textContent":"Test"},{"tagName":"div"},{"attributes":[{"name":"class","value":"second-class example-class"}]}]'
);

tests_section(
    "Multiple classes after single element #4",
    "<div class=' second-class  example-class '>Test</div>",
    "div.example-class.second-class",
    '[{"nodeValue":"Test"},{"textContent":"Test"},{"tagName":"div"},{"attributes":[{"name":"class","value":" second-class  example-class "}]}]'
);

tests_section(
    "ID after single element #1",
    "<div id='example-id'>Test</div>",
    "div#example-id",
    '[{"nodeValue":"Test"},{"textContent":"Test"},{"tagName":"div"},{"attributes":[{"name":"id","value":"example-id"}]}]'
);

tests_section(
    "ID after single element #2",
    "<div class='example-id'>Test</div>",
    "div#example-id",
    '[]'
);

tests_section(
    "Single class",
    "<div class='example-class'>Test</div>",
    ".example-class",
    '[{"nodeValue":"Test"},{"textContent":"Test"},{"tagName":"div"},{"attributes":[{"name":"class","value":"example-class"}]}]'
);

tests_section(
    "Single id",
    "<div id='example-id'>Test</div>",
    "#example-id",
    '[{"nodeValue":"Test"},{"textContent":"Test"},{"tagName":"div"},{"attributes":[{"name":"id","value":"example-id"}]}]'
);

tests_section(
    "Single class after *",
    "<div class='example-class'>Test</div>",
    "*.example-class",
    '[{"nodeValue":"Test"},{"textContent":"Test"},{"tagName":"div"},{"attributes":[{"name":"class","value":"example-class"}]}]'
);

tests_section(
    "Single id after *",
    "<div id='example-id'>Test</div>",
    "*#example-id",
    '[{"nodeValue":"Test"},{"textContent":"Test"},{"tagName":"div"},{"attributes":[{"name":"id","value":"example-id"}]}]'
);

tests_section(
    "Single *",
    "<div>Test</div>",
    "*",
    '[{"nodeValue":"Test"},{"textContent":"Test"},{"tagName":"div"}]',
    true
);

tests_section(
    "Combinator: Descendant selector #1",
    "<div><p>Test</p></div>",
    "div p",
    '[{"nodeValue":"Test"},{"textContent":"Test"},{"tagName":"p"}]'
);

tests_section(
    "Combinator: Descendant selector #2",
    "<div class='first'><div class='second'><p>Test</p></div></div>",
    "div p",
    '[{"nodeValue":"Test"},{"textContent":"Test"},{"tagName":"p"}]'
);

tests_section(
    "Combinator: Descendant selector #3",
    "<div><span class='first'>A<span class='second'>B</span></p></div>",
    "div span",
    '[{"nodeValue":"AB"},{"textContent":"AB"},{"tagName":"span"},{"attributes":[{"name":"class","value":"first"}]},{"nodeValue":"B"},{"textContent":"B"},{"tagName":"span"},{"attributes":[{"name":"class","value":"second"}]}]'
);

tests_section(
    "Combinator: Child selector #1",
    "<ul><li>Test</li></ul>",
    "ul > li",
    '[{"nodeValue":"Test"},{"textContent":"Test"},{"tagName":"li"}]'
);

tests_section(
    "Combinator: Child selector #2",
    "<ul><li>Test</li></ul>",
    "ul  >  li",
    '[{"nodeValue":"Test"},{"textContent":"Test"},{"tagName":"li"}]'
);

tests_section(
    "Combinator: Child selector #3",
    "<ul><li><span>Test</span></li></ul>",
    "ul > span",
    '[]'
);

tests_section(
    "Combinator: Adjacent sibling selector",
    "<p>The + selector is used to select an element that is directly after another specific element.</p>
<p>The following example selects the first p element that are placed immediately after div elements:</p>

<div>
  <p>Paragraph 1 in the div.</p>
  <p>Paragraph 2 in the div.</p>
</div>

<p>Paragraph 3. After a div.</p>
<p>Paragraph 4. After a div.</p>

<div>
  <p>Paragraph 5 in the div.</p>
  <p>Paragraph 6 in the div.</p>
</div>

<p>Paragraph 7. After a div.</p>
<p>Paragraph 8. After a div.</p>",
    "div + p",
    '[{"nodeValue":"Paragraph 3. After a div."},{"textContent":"Paragraph 3. After a div."},{"tagName":"p"},{"nodeValue":"Paragraph 7. After a div."},{"textContent":"Paragraph 7. After a div."},{"tagName":"p"}]'
);

tests_section(
    "Combinator: General sibling selector",
    "<p>The general sibling selector (~) selects all elements that are siblings of a specified element.</p>

<p>Paragraph 1.</p>

<div>
  <p>Paragraph 2.</p>
</div>

<p>Paragraph 3.</p>
<code>Some code.</code>
<p>Paragraph 4.</p>",
    "div ~ p",
    '[{"nodeValue":"Paragraph 3."},{"textContent":"Paragraph 3."},{"tagName":"p"},{"nodeValue":"Paragraph 4."},{"textContent":"Paragraph 4."},{"tagName":"p"}]',
);

tests_section(
    "Comma selector",
    "<h1>Welcome to My Homepage</h1>

<div>
  <p>My name is Donald.</p>
  <p>I live in Duckburg.</p>
</div>

<p>My best friend is Mickey.</p>",
    "h1, p",
    '[{"nodeValue":"Welcome to My Homepage\r\n  My name is Donald.\r\n  I live in Duckburg.\r\nMy best friend is Mickey."},{"textContent":"Welcome to My Homepage\r\n  My name is Donald.\r\n  I live in Duckburg.\r\nMy best friend is Mickey."},{"tagName":"h1"},{"nodeValue":"My name is Donald."},{"textContent":"My name is Donald."},{"tagName":"p"},{"nodeValue":"I live in Duckburg."},{"textContent":"I live in Duckburg."},{"tagName":"p"},{"nodeValue":"My best friend is Mickey."},{"textContent":"My best friend is Mickey."},{"tagName":"p"}]',
    true
);

tests_section(
    "[attribute] selector",
    '<p>The links with a target attribute gets a yellow background:</p>

<a href="https://www.w3schools.com">w3schools.com</a>
<a href="http://www.disney.com" target="_blank">disney.com</a>
<a href="http://www.wikipedia.org" target="_top">wikipedia.org</a>',
    "a[target]",
    '[{"nodeValue":"disney.com"},{"textContent":"disney.com"},{"tagName":"a"},{"attributes":[{"name":"href","value":"http:\/\/www.disney.com"}]},{"attributes":[{"name":"target","value":"_blank"}]},{"nodeValue":"wikipedia.org"},{"textContent":"wikipedia.org"},{"tagName":"a"},{"attributes":[{"name":"href","value":"http:\/\/www.wikipedia.org"}]},{"attributes":[{"name":"target","value":"_top"}]}]',
);

tests_section(
    "[attribute=value] selector #1",
    '<p>The link with target="_blank" gets a yellow background:</p>

<a href="https://www.w3schools.com">w3schools.com</a>
<a href="http://www.disney.com" target="_blank">disney.com</a>
<a href="http://www.wikipedia.org" target="_top">wikipedia.org</a>',
    "a[target=_blank]",
    '[{"nodeValue":"disney.com"},{"textContent":"disney.com"},{"tagName":"a"},{"attributes":[{"name":"href","value":"http:\/\/www.disney.com"}]},{"attributes":[{"name":"target","value":"_blank"}]}]'
);

tests_section(
    '[attribute="value"] selector #2',
    '<p>The link with target="_blank" gets a yellow background:</p>

<a href="https://www.w3schools.com">w3schools.com</a>
<a href="http://www.disney.com" target="_blank">disney.com</a>
<a href="http://www.wikipedia.org" target="_top">wikipedia.org</a>',
    'a[target="_blank"]',
    '[{"nodeValue":"disney.com"},{"textContent":"disney.com"},{"tagName":"a"},{"attributes":[{"name":"href","value":"http:\/\/www.disney.com"}]},{"attributes":[{"name":"target","value":"_blank"}]}]'
);

tests_section(
    "[attribute='value'] selector #3",
    '<p>The link with target="_blank" gets a yellow background:</p>

<a href="https://www.w3schools.com">w3schools.com</a>
<a href="http://www.disney.com" target="_blank">disney.com</a>
<a href="http://www.wikipedia.org" target="_top">wikipedia.org</a>',
    "a[target='_blank']",
    '[{"nodeValue":"disney.com"},{"textContent":"disney.com"},{"tagName":"a"},{"attributes":[{"name":"href","value":"http:\/\/www.disney.com"}]},{"attributes":[{"name":"target","value":"_blank"}]}]'
);

tests_section(
    "[attribute='va\"lue'] selector #4",
    '<p>The link with target="_blank" gets a yellow background:</p>

<a href="https://www.w3schools.com">w3schools.com</a>
<a href="http://www.disney.com" target=\'_"blank\'>disney.com</a>
<a href="http://www.wikipedia.org" target="_top">wikipedia.org</a>',
    "a[target='_\"blank']",
    '[{"nodeValue":"disney.com"},{"textContent":"disney.com"},{"tagName":"a"},{"attributes":[{"name":"href","value":"http:\/\/www.disney.com"}]},{"attributes":[{"name":"target","value":"_\"blank"}]}]'
);

tests_section(
    "[attribute~=value] selector",
    '<p>The image with the title attribute containing the word "flower" gets a yellow border.</p>

<img src="klematis.jpg" title="klematis flower" width="150" height="113">
<img src="img_flwr.gif" title="flowers" width="224" height="162">
<img src="landscape.jpg" title="landscape" width="160" height="120">',
    "[title~=flower]",
    '[{"nodeValue":""},{"textContent":""},{"tagName":"img"},{"attributes":[{"name":"src","value":"klematis.jpg"}]},{"attributes":[{"name":"title","value":"klematis flower"}]},{"attributes":[{"name":"width","value":"150"}]},{"attributes":[{"name":"height","value":"113"}]}]'
);

tests_section(
    "[attribute|=value] selector",
    '<p lang="en">Hello!</p>
<p lang="en-us">Hi!</p>
<p lang="en-gb">Ello!</p>
<p lang="us">Hi!</p>
<p lang="no">Hei!</p>',
    "[lang|=en]",
    '[{"nodeValue":""},{"textContent":""},{"tagName":"img"},{"attributes":[{"name":"src","value":"klematis.jpg"}]},{"attributes":[{"name":"title","value":"klematis flower"}]},{"attributes":[{"name":"width","value":"150"}]},{"attributes":[{"name":"height","value":"113"}]}]',
    true
);
?>
</body>
</html>