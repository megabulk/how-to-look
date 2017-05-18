<?php
$q = $_GET['query'];
include_once('knowgraph_lib.php');

$conn = getDB();

$sql = <<<SQL
    SELECT *
    FROM `people`
    WHERE `q` LIKE "{$q}%"
    AND paths LIKE '%\"1651\"%' AND paths LIKE '%\"81891\"%'
    ORDER BY num_links_to DESC, name
    LIMIT 30
SQL;

$values = [];
if ($result = $conn->query($sql)) {
	header('Content-Type: application/json');
	while ($r = $result->fetch_assoc()) {
		$name = $r['name'] ? ucwords($r['name']) : ucwords($r['q']);
		$suggestion['value'] = $name;
		$suggestion['data'] = $r['q'];
		$values[] = $suggestion;
	}
}
$s['suggestions'] = $values;
print json_encode($s);
?>