<?php
include __DIR__ . "/Backend/db.php";
$result = $conexion->query("SHOW TABLES");
$tables = [];
while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
}
$schema = "";
foreach ($tables as $table) {
    $res = $conexion->query("SHOW CREATE TABLE $table");
    $row = $res->fetch_row();
    $schema .= $row[1] . ";\n\n";
}
echo $schema;
?>
