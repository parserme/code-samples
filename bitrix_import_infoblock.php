<?
//login,password="import","YCBqMpdB"
error_reporting(7);
chdir("/var/www/a/");
ini_set("include_path",ini_get('include_path').':/usr/share/php:');
require_once "HTTP/Request.php";

include "a_pip.class.php";
$possibly_inbound_parameters=array(
	param=>array(0=>"site_domain_http",1=>"xmlfile",2=>"login",3=>"password"),
	regexp=>array(0=>"^http://[\w\.\-_]+$"));
$aPip=new aPip($possibly_inbound_parameters);
extract($aPip->checkparams($argv));
if(!$site_domain_http)
	die("Set an oibligatory parameter(1 of 4), sample: site_domain_http=http://exp.003apteka.local\r\n");
if(!$xmlfile)
	die("Set an oibligatory parameter(2 of 4), sample: xmlfile=path/to/file_import.xml\r\n");
if(!$login)
	die("Set an oibligatory parameter(3 of 4), sample: login=admin\r\n");
if(!$password)
	die("Set an oibligatory parameter(4 of 4), sample: password=admpas\r\n");
$catfile=$xmlfile;
$tmpzipfile=$catfile.".zip";
//**********************************************************************
//***************************** req1 ***********************************
//**********************************************************************

$url=$site_domain_http."/bitrix/admin/1c_exchange.php?type=catalog&mode=checkauth";

$option = array( 
    "timeout" => "10",
    "allowRedirects" => true,
    "maxRedirects" => 3,
); 
$req =& new HTTP_Request($url, $option); 
 
$req->addHeader("User-Agent", "1C+Enterprise/8.2");
$req->addHeader('Accept-Encoding', "deflate");
$req->addHeader('Connection', "keep-alive");

$req->setBasicAuth($login,$password);


$response = $req->sendRequest(); 
if (!PEAR::isError($response)) { 
    $res_code = $req->getResponseCode();
    $res_body = $req->getResponseBody();
	}
if($res_code != 200){
	errdie(1);
	}

list($sux,$cn,$cok)=preg_split("|\s+|msi",$res_body);
var_dump($sux,$cn,$cok);
if($sux!="success"){
	errdie(2);
	}

$req->addCookie($cn, $cok);


//**********************************************************************
//***************************** req2 ***********************************
//**********************************************************************
	$url=$site_domain_http."/bitrix/admin/1c_exchange.php?type=catalog&mode=init";
	$req->setURL($url);
	if (!PEAR::isError($req->sendRequest()))
		$res_body = $req->getResponseBody();
	else
		errdie(3,$res_body);
	var_dump($res_body);
//echo "Sleep for 10 seconds....";sleep(10);echo "Ok\r\n";

//**********************************************************************
//***************************** req3 ***********************************
//**********************************************************************

$zip = new ZipArchive();
if(file_exists($tmpzipfile))unlink($tmpzipfile);
if($zip->open($tmpzipfile, ZIPARCHIVE::CREATE)!==TRUE)
    errdie(4);
$zip->addFile($catfile);
$zip->close();
$url=$site_domain_http."/bitrix/admin/1c_exchange.php?type=catalog&mode=file&filename=".basename($catfile).".zip";

$req->setURL($url);
$req->setMethod(HTTP_REQUEST_METHOD_POST);
$req->setBody(file_get_contents($tmpzipfile));
if (!PEAR::isError($req->sendRequest())){
    $res_code = $req->getResponseCode();
    $res_header = $req->getResponseHeader();
    $res_body = $req->getResponseBody();
	var_dump($res_code,$res_body);
	}
else{
	errdie(5,$res_body);
	}

//**********************************************************************
//***************************** req4 ***********************************
//**********************************************************************
$url=$site_domain_http."/bitrix/admin/1c_exchange.php?type=catalog&mode=import&filename=".basename($catfile);
$req->setURL($url);
$req->clearPostData();
$req->setMethod(HTTP_REQUEST_METHOD_SEND);
$cond="progress";
$i=0;
while($cond=="progress" && $i<100){
	if (!PEAR::isError($req->sendRequest())){
		$res_body = $req->getResponseBody();
		$cond=substr($res_body,0,strpos($res_body,"\n"));
		//file_put_contents("./req_resp.txt.tmp",$res_body,FILE_APPEND);
		}
	else
		errdie(3,$res_body);
	var_dump($res_body);
	$i++;
	}



function errdie($ern,$ert=""){
	$ers=array(
		1=>"Auth error",2=>"auth with no success message",3=>"req2 error",4=>"Can't create zip file for req3",5=>"req3 error"
		);
	echo "Error(".$ern."):".$ers[$ern].(($ert)?":".$ert:"")."\r\n";
	die();
	}
?>
