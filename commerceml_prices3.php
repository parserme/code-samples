<?
//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
//!!!manual: 1с<->bitrix http://v8.1c.ru/edi/edi_stnd/131/?printversion=1
//!!!file: http://v8.1c.ru/edi/edi_stnd/131/offers.xml
//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
error_reporting(7);
include "../includes/mysql.connect.o003inforu.inc";

$idpack="100-100-100";
$idcat="0-0-0";
$idclassif="1-1-1";
$idtypeofprice="2-2-2";

list($t_products)=mysql_fetch_row(mysql_query("select table_name_active from tables where constant_name='table_products'"));

//**********************************************************************
//********************** inbound paramteters ***************************
//**********************************************************************
$script_params=getopt("o:");
if(!($script_params[o])){
echo "Possible options:
	-o offers.xml\t- name of xml file to save to
";
	die("FOR CONTINUING, please set all of obligatory parameters\r\n");
	}
/*$script_params=array();
if(sizeof(argv)>0)foreach($argv as $arg){
	if($arg=='--changes-only')$script_params[changes_only]='changes';
	if($arg=='--whole-catalogue')$script_params[changes_only]='whole';
	}
if(!($script_params[changes_only])){
echo "Possible options:
	--changes-only - periodically updates of prices and remnants of goods, [generally] without pictures
	--whole-catalogue - whole catalogue import including pictures
";
	die("FOR CONTINUING, please set an obligatory parameter\r\n");
	}*/
//**********************************************************************
//**********************************************************************
//**********************************************************************


$doc = new domDocument('1.0', 'utf-8');
$doc->formatOutput = true;
$nroot = $doc->createElement("КоммерческаяИнформация");
	$oa = $doc->createAttribute('xmlns:xsi');
		$oa->value = 'http://www.w3.org/2001/XMLSchema-instance';
		$nroot->appendChild($oa);
	$oa = $doc->createAttribute('xsi:noNamespaceSchemaLocation');
		$oa->value = 'commerceml_2.04.xsd';
		$nroot->appendChild($oa);
	$oa = $doc->createAttribute('ВерсияСхемы');
		$oa->value ='2.04';
		$nroot->appendChild($oa);
	$oa = $doc->createAttribute('ДатаФормирования');
		$oa->value = '2008-01-09T18:13:34';
		$nroot->appendChild($oa);
	$doc->appendChild($nroot);
//------------------
$ncat=$doc->CreateElement("ПакетПредложений");
	$oa = $doc->createAttribute('СодержитТолькоИзменения');
		$oa->value = "false";//($script_params[changes_only]=='changes') ? "true" : "false";
		$ncat->appendChild($oa);
	$nroot->AppendChild($ncat);

	$nt=$doc->CreateElement("Ид");
		$nt->appendChild($doc->createTextNode($idpack));
		$ncat->appendChild($nt);
	$nt=$doc->CreateElement("Наименование");
		$nt->appendChild($doc->createTextNode("Пакет предложений"));
		$ncat->appendChild($nt);
	$nt=$doc->CreateElement("ИдКаталога");
		$nt->appendChild($doc->createTextNode($idcat));
		$ncat->appendChild($nt);
	$nt=$doc->CreateElement("ИдКлассификатора");
		$nt->appendChild($doc->createTextNode($idclassif));
		$ncat->appendChild($nt);

//-----------------
$nt=$doc->CreateElement("ТипыЦен");
	$ncat->appendChild($nt);
	/*		<ТипЦены>
				<Ид>DISCOUNT</Ид>
				<Наименование>DISCOUNT</Наименование>
				<Валюта>RUB</Валюта>
			</ТипЦены>*/
	$nt1=$doc->CreateElement("ТипЦены");
		$nt->appendChild($nt1);
			$nt2=$doc->CreateElement("Ид");
				$nt2->appendChild($doc->createTextNode("DISCOUNT"));
				$nt1->appendChild($nt2);
			$nt2=$doc->CreateElement("Наименование");
				$nt2->appendChild($doc->createTextNode("DISCOUNT"));
				$nt1->appendChild($nt2);
			$nt2=$doc->CreateElement("Валюта");
				$nt2->appendChild($doc->createTextNode("RUB"));
				$nt1->appendChild($nt2);
	$nt1=$doc->CreateElement("ТипЦены");
		$nt->appendChild($nt1);
			$nt2=$doc->CreateElement("Ид");
				$nt2->appendChild($doc->createTextNode($idtypeofprice));
				$nt1->appendChild($nt2);
			$nt2=$doc->CreateElement("Наименование");
				$nt2->appendChild($doc->createTextNode("Розничная"));
				$nt1->appendChild($nt2);
			$nt2=$doc->CreateElement("Валюта");
				$nt2->appendChild($doc->createTextNode("RUB"));
				$nt1->appendChild($nt2);
			$nt2=$doc->CreateElement("Налог");
				$nt1->appendChild($nt2);
					$nt3=$doc->CreateElement("Наименование");
						$nt3->appendChild($doc->createTextNode("НДС"));
						$nt2->appendChild($nt3);
					$nt3=$doc->CreateElement("УчтеноВСумме");
						$nt3->appendChild($doc->createTextNode("true"));
						$nt2->appendChild($nt3);


//-----------------
$ngd=$doc->CreateElement("Предложения");
	$ncat->appendChild($ngd);

$r=sql_get_goods();
while($d=mysql_fetch_assoc($r)){
	$nt=$doc->CreateElement("Предложение");
			$nt1=$doc->CreateElement("Ид");
				$nt1->appendChild($doc->createTextNode($d[prod_id]));
				$nt->appendChild($nt1);
			$nt1=$doc->CreateElement("Наименование");
				$nt1->appendChild($doc->createTextNode($d[prod_name]));
				$nt->appendChild($nt1);
			$nt1=$doc->CreateElement("БазоваяЕдиница");
				$nt1->appendChild($doc->createTextNode("шт"));
					$oa=$doc->createAttribute('Код');
						$oa->value ="796";
						$nt1->appendChild($oa);
					$oa=$doc->createAttribute('НаименованиеПолное');
						$oa->value ="Штука";
						$nt1->appendChild($oa);
					$oa=$doc->createAttribute('МеждународноеСокращение');
						$oa->value ="PCE";
						$nt1->appendChild($oa);
				$nt->appendChild($nt1);
			$nt1=$doc->CreateElement("Количество");
				$nt1->appendChild($doc->createTextNode($d[quantity]));
				$nt->appendChild($nt1);
			/*$nt1=$doc->CreateElement("ЗначенияСвойства");
				$nt->appendChild($nt1);
					$nt2=$doc->CreateElement("Ид");
						$nt2->appendChild($doc->createTextNode('30'));
						$nt1->appendChild($nt2);
					$nt2=$doc->CreateElement("Значение");
						$nt2->appendChild($doc->createTextNode($d[proizv_name]));
						$nt1->appendChild($nt2);*/
			$nt1=$doc->CreateElement("Цены");
				$nt->appendChild($nt1);
				/*		  		<Цена>
						<ИдТипаЦены>DISCOUNT</ИдТипаЦены>
						<ЦенаЗаЕдиницу>199.99</ЦенаЗаЕдиницу>
						<Валюта>RUB</Валюта>
						<Единица>шт</Единица>
				</Цена>*/
					$disc_price=($d[price_with_disc]>0) ? $d[price_with_disc] : $d[prod_price];
					$nt2=$doc->CreateElement("Цена");
						$nt1->appendChild($nt2);
							$nt3=$doc->CreateElement("ИдТипаЦены");
								$nt3->appendChild($doc->createTextNode("DISCOUNT"));
								$nt2->appendChild($nt3);
							$nt3=$doc->CreateElement("ЦенаЗаЕдиницу");
								$nt3->appendChild($doc->createTextNode($disc_price));
								$nt2->appendChild($nt3);
							$nt3=$doc->CreateElement("Валюта");
								$nt3->appendChild($doc->createTextNode("RUB"));
								$nt2->appendChild($nt3);
							$nt3=$doc->CreateElement("Единица");
								$nt3->appendChild($doc->createTextNode("шт"));
								$nt2->appendChild($nt3);
					$nt2=$doc->CreateElement("Цена");
						$nt1->appendChild($nt2);
							$nt3=$doc->CreateElement("Представление");
								$nt3->appendChild($doc->createTextNode($d[prod_price]));
								$nt2->appendChild($nt3);
							$nt3=$doc->CreateElement("ИдТипаЦены");
								$nt3->appendChild($doc->createTextNode($idtypeofprice));
								$nt2->appendChild($nt3);
							$nt3=$doc->CreateElement("ЦенаЗаЕдиницу");
								$nt3->appendChild($doc->createTextNode($d[prod_price]));
								$nt2->appendChild($nt3);
							$nt3=$doc->CreateElement("Валюта");
								$nt3->appendChild($doc->createTextNode("RUB"));
								$nt2->appendChild($nt3);
							$nt3=$doc->CreateElement("Единица");
								$nt3->appendChild($doc->createTextNode("шт"));
								$nt2->appendChild($nt3);
							$nt3=$doc->CreateElement("Коэффициент");
								$nt3->appendChild($doc->createTextNode("1"));
								$nt2->appendChild($nt3);
		$ngd->appendChild($nt);
	}

$res=$doc->save($script_params[o]);
if($res>0)
	echo "OK ".number_format($res/1048576,3,'.','')."Mb";
else
	echo "Error with saving of the file";


function sql_get_goods(){
global $t_products;
	$q="SELECT ".
	//n.nidnomtov as prod_id,".
	"concat(n.`nidnomtov`,'_','0000') AS prod_id, ". //p.id) AS prod_id !!! omit this p.id it is no needp.id) AS prod_id,
	"p.kolvo as quantity
     , n.`prod_line1` AS prod_name
     , p.`prod_price`,
	 replace(format(ceil(p.prod_price*(100-dis.discount))/100,2),',','') as price_with_disc
     , CASE WHEN m2.name IS NOT NULL AND m2.name <> '' THEN m2.name WHEN n.`nidproizv`<>0 AND m.cname <> 'Неопределено' THEN m.cname ELSE '' END AS proizv_name
     , CASE WHEN d2.cname IS NOT NULL AND d2.cname <> 'Неопределено' THEN d2.cname WHEN n.`nidproizv`<>0 AND d.cname <> 'Неопределено' THEN d.cname ELSE '' END AS country_name     
      
  FROM `nomtov` AS n
INNER
  JOIN ".$t_products." AS p
    ON n.`nidnomtov`=p.`nidnomtov`
   AND n.storetype=1
   AND p.storetype=1
LEFT
 OUTER
  JOIN proizv AS m
    ON n.nidproizv=m.nidproizv
left join goods_discountprices dis on dis.prodid=p.nidnomtov and dis.dscode=p.shopcode
LEFT
 OUTER
  JOIN land AS d
    ON m.nidlnd=d.nidlnd
LEFT
 OUTER
  JOIN ref_to_base AS r2b
    ON r2b.nidnomtov=n.nidnomtov
   AND n.storetype=1
   AND r2b.storetype=1
   AND r2b.bases_id>=10
LEFT
 OUTER
  JOIN manufacturers AS m2
    ON n.manufacturer_id=m2.id
LEFT
 OUTER
  JOIN land AS d2
    ON m2.nidlnd=d2.nidlnd

  WHERE p.shopcode='D500' AND p.avail=1 AND r2b.active IS NULL ORDER 
    BY n.prod_line1
     , CASE WHEN p.shopcode='D500' THEN 0 ELSE 1 END
     , n.`order_by`";
	//echo $q;die();
	return mysql_query($q);
	}
?>
