<?php
/**
 * generar_sorteo.php - VERSION CORREGIDA
 */

if (ob_get_level() === 0) ob_start();
ob_clean();

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');

function jsonResponse($data){
    while (ob_get_level()>0) ob_end_clean();
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

register_shutdown_function(function(){
    $e = error_get_last();
    if($e){
        jsonResponse([
            "success"=>false,
            "message"=>"Fatal: ".$e["message"],
            "steps"=>[]
        ]);
    }
});

require_once "config.php";

if(!function_exists("getConnection")){
    jsonResponse(["success"=>false,"message"=>"Sin config.php","steps"=>[]]);
}

define("DB_SEPE","sepe");
define("DB_USET","uset");
define("DB_SORTEO","sorteo");

/* =====================================================
   CONEXIONES
===================================================== */
function conectar(){
    try{
        $sepe = getConnection("sepe");
        $uset = getConnection("uset");
        $sort = getConnection("sorteo");

        if(!$sepe || !$uset || !$sort){
            throw new Exception("Error de conexión");
        }

        return [$sepe,$uset,$sort];
    }catch(Exception $e){
        jsonResponse(["success"=>false,"message"=>$e->getMessage(),"steps"=>[]]);
    }
}

/* =====================================================
   EJECUCIÓN SEGURA QUERY
===================================================== */
function q($conn,$sql){
    $r = $conn->query($sql);
    if(!$r){
        throw new Exception($conn->error);
    }
    return $r;
}

/* =====================================================
   TABLAS
===================================================== */
function crearTablas($db){

    $sqls = [

"CREATE TABLE IF NOT EXISTS profes_ini_31 (
id INT AUTO_INCREMENT PRIMARY KEY,
rfc VARCHAR(13),
nombre VARCHAR(200),
cod_pago INT,
unidad INT,
subunidad INT,
cat_puesto VARCHAR(20),
horas DECIMAL(10,2),
cons_plaza VARCHAR(20),
ent_fed INT,
ct_clasif VARCHAR(2),
ct_id INT,
ct_secuencial INT,
ct_digito_ver VARCHAR(2),
fec_ing INT,
municipio VARCHAR(100),
region INT,
origen VARCHAR(20),
sindicato INT
)",

"CREATE TABLE IF NOT EXISTS profes_ini_55 (
id INT AUTO_INCREMENT PRIMARY KEY,
rfc VARCHAR(13),
nombre VARCHAR(200),
num_emp INT,
ramo INT,
sector INT,
programa INT,
subprograma INT,
dependencia INT,
unidad_res INT,
proyecto VARCHAR(10),
nombramiento VARCHAR(10),
categoria INT,
digito_cat VARCHAR(2),
cat_pago INT,
horas INT,
tipo_nom VARCHAR(10),
cons_plaza VARCHAR(20),
ct VARCHAR(20),
qna_ing_sep INT,
municipio VARCHAR(100),
region INT,
origen VARCHAR(20),
sindicato INT
)",

"CREATE TABLE IF NOT EXISTS profes_ini_31_limpia LIKE profes_ini_31",
"CREATE TABLE IF NOT EXISTS profes_ini_55_limpia LIKE profes_ini_55",

"CREATE TABLE IF NOT EXISTS profes_31 (
id INT AUTO_INCREMENT PRIMARY KEY,
rfc VARCHAR(13),
nombre VARCHAR(200),
plaza VARCHAR(100),
ctr VARCHAR(100),
fec_ing INT,
municipio VARCHAR(100),
region INT,
origen VARCHAR(20),
sindicato INT
)",

"CREATE TABLE IF NOT EXISTS profes_55 LIKE profes_31",
"CREATE TABLE IF NOT EXISTS profes_unificada LIKE profes_31",
"CREATE TABLE IF NOT EXISTS profes_sorteo LIKE profes_31",

"CREATE TABLE IF NOT EXISTS ganadores_atoaños (
id INT AUTO_INCREMENT PRIMARY KEY,
rfc VARCHAR(13),
nombre VARCHAR(200),
descripcion VARCHAR(200),
anio INT
)",

"CREATE TABLE IF NOT EXISTS comisionados (
id INT AUTO_INCREMENT PRIMARY KEY,
rfc VARCHAR(13),
nombre VARCHAR(200),
plaza VARCHAR(100),
ct VARCHAR(50)
)"

    ];

    foreach($sqls as $s){
        $db->query($s);
    }

    return ["success"=>true,"message"=>"Tablas OK"];
}

/* =====================================================
   INSERT 31
===================================================== */
function insertar31($uset,$db){

$db->query("TRUNCATE profes_ini_31");

$sql = "
INSERT INTO profes_ini_31(
rfc,nombre,cod_pago,unidad,subunidad,cat_puesto,horas,cons_plaza,
ent_fed,ct_clasif,ct_id,ct_secuencial,ct_digito_ver,fec_ing,municipio,region,origen,sindicato
)
SELECT
e.rfc,e.nom_emp,ep.cod_pago,ep.unidad,ep.subunidad,ep.cat_puesto,
ep.horas,ep.cons_plaza,cc.ent_fed,cc.ct_clasif,cc.ct_id,cc.ct_secuencial,cc.ct_digito_ver,
e.qna_ing_sep,ct.municipio,0,'USET',31
FROM ".DB_USET.".empleado_plaza ep
JOIN ".DB_USET.".empleado e ON e.rfc=ep.rfc
JOIN ".DB_USET.".cheque_cpto cc ON cc.rfc=ep.rfc
JOIN ".DB_USET.".centro_trabajo ct ON ct.ct_clasif=cc.ct_clasif
";

q($db,$sql);

$c = q($db,"SELECT COUNT(*) c FROM profes_ini_31")->fetch_assoc()['c'];

return ["success"=>true,"message"=>"31: $c"];
}

/* =====================================================
   INSERT 55
===================================================== */
function insertar55($sepe,$db){

$db->query("TRUNCATE profes_ini_55");

$sql="
INSERT INTO profes_ini_55(
rfc,nombre,num_emp,ramo,sector,programa,subprograma,
dependencia,unidad_res,proyecto,nombramiento,categoria,
digito_cat,cat_pago,horas,tipo_nom,cons_plaza,ct,qna_ing_sep,municipio,region,origen,sindicato
)
SELECT
nd.rfc, CONCAT(nd.paterno,' ',nd.materno,' ',nd.nombre),
nd.num_emp,nd.ramo,nd.sector,nd.programa,nd.subprograma,
nd.dependencia,nd.unidad_res,nd.proyecto,nd.nombramiento,nd.categoria,
nd.digito_cat,nd.cat_pago,nd.horas,nd.tipo_nom,nd.cons_plaza,nd.ct,
nd.qna_ing_sep,ct.municipio,0,'SEPE',55
FROM ".DB_SEPE.".sepe_niv_desconcentrados nd
JOIN ".DB_SEPE.".sepe_cg_ct ct ON ct.ct=nd.ct
";

q($db,$sql);

$c = q($db,"SELECT COUNT(*) c FROM profes_ini_55")->fetch_assoc()['c'];

return ["success"=>true,"message"=>"55: $c"];
}

/* =====================================================
   LIMPIEZA
===================================================== */
function limpiar($db){

$db->query("TRUNCATE profes_ini_31_limpia");
$db->query("TRUNCATE profes_ini_55_limpia");

$db->query("
INSERT INTO profes_ini_31_limpia
SELECT * FROM (
SELECT *, ROW_NUMBER() OVER (PARTITION BY rfc ORDER BY horas DESC) rn
FROM profes_ini_31
)t WHERE rn=1
");

$db->query("
INSERT INTO profes_ini_55_limpia
SELECT * FROM (
SELECT *, ROW_NUMBER() OVER (PARTITION BY rfc ORDER BY horas DESC) rn
FROM profes_ini_55
)t WHERE rn=1
");

$c31 = q($db,"SELECT COUNT(*) c FROM profes_ini_31_limpia")->fetch_assoc()['c'];
$c55 = q($db,"SELECT COUNT(*) c FROM profes_ini_55_limpia")->fetch_assoc()['c'];

return ["success"=>true,"message"=>"Limpieza OK 31=$c31 55=$c55"];
}

/* =====================================================
   MAIN
===================================================== */
function run(){

list($sepe,$uset,$db) = conectar();

$results = [];

$results[] = array_merge(crearTablas($db),["step"=>"tablas"]);
$results[] = array_merge(insertar31($uset,$db),["step"=>"31"]);
$results[] = array_merge(insertar55($sepe,$db),["step"=>"55"]);
$results[] = array_merge(limpiar($db),["step"=>"limpieza"]);

return ["success"=>true,"message"=>"OK","steps"=>$results];
}

try{
    jsonResponse(run());
}catch(Throwable $e){
    jsonResponse(["success"=>false,"message"=>$e->getMessage(),"steps"=>[]]);
}
?>