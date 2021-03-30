<?php
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

include __DIR__ . "/jQuery.php";

$doc =
    (new Doc)
        ->prove() // remove this line to avoid HTML prove
        ->setDoc("
            <ul>
                <li>this</li>
                <li>lorem</li>
                <li>is</li>
                <li>ipsum</li>
                <li>awesome</li>
                <li>dolor</li>
            </ul>
        ");

$query = $doc->_("ul :nth-of-type(odd)");
?>

<h2>Selected nodes</h2>
This is the xPath string: <?php echo $doc->getXPathAsString(); ?><br /><br />

<?php
if($query === false) {
    die("xPath has wrong syntax");
}
?>

<ul>
<?php
if(count($query) == 0) {
    echo "<li>(no results)</li>";
}

/*
    - this
    - is
    - awesome
 */
foreach ($query as $element) {
    echo "<li>" . $element->tagName . ": '" . $element->nodeValue . "'</li>";
}
?>
</ul>