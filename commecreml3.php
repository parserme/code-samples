<?
//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
//!!!manual: 1с<->bitrix http://v8.1c.ru/edi/edi_stnd/131/?printversion=1
//!!!file: http://v8.1c.ru/edi/edi_stnd/131/import.xml
//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

///inbound parameters: commerceml3.php -pno -oimport.xml 

error_reporting(7);
include "../includes/mysql.connect.o003inforu.inc";
$img_dir_wsabs="import_files/";
//$thumbs_dir_wsabs="import_files/thumb5/";
$img_source="/var/www/images/products/p__PRODID__.png";
$groupid_nogroup="9999";
$groupname_nogroup="Все товары";
$groupid_discounts="1";//ид раздела "акции.подарки."

$aDescrItems = array(
'composition'=>'Состав и форма выпуска',
'drugformdescr'=>'Описание лекарственной формы',
'characters'=>'Характеристика',
'pharmaactions'=>'Фармакологическое действие',
'actonorg'=>'Действие на организм',
'componentsproperties'=>'Свойства компонентов',
'pharmakinetic'=>'Фармакокинетика',
'pharmadynamic'=>'Фармакодинамика',
'clinicalpharmacology'=>'Клиническая фармакология',
'direction'=>'Инструкция',
'indications'=>'Показания',
'recommendations'=>'Рекомендуется',
'contraindications'=>'Противопоказания',
'pregnancyuse'=>'Применение при беременности и кормлении грудью',
'sideactions'=>'Побочные действия',
'interactions'=>'Взаимодействие',
'usemethodanddoses'=>'Способ применения и дозы',
//'instrforpac'=>'Инструкция для пациента',
'overdose'=>'Передозировка',
'precautions'=>'Меры предосторожности',
'specialguidelines'=>'Особые указания',
//'manufacturer'=>'Производитель',
'literature'=>'Литература',
'comment'=>'Комментарий',
);

list($t_products)=mysql_fetch_row(mysql_query("select table_name_active from tables where constant_name='table_products'"));

//**********************************************************************
//********************** inbound paramteters ***************************
//**********************************************************************

$script_params=getopt("p:o:");
if(!($script_params[p]) || !($script_params[o])){
echo "Possible options:
	-p yes\t\t- include pictures
	-p no\t\t- do not include pictures
	-o import.xml\t- name of xml file to save to
FOR CONTINUING, please set all of obligatory parameters\r\n";
	die();
	}
//**********************************************************************
//**********************************************************************
//**********************************************************************

$idclassif="1-1-1";
$idcat="0-0-0";

$doc = new domDocument('1.0', 'utf-8');
$doc->formatOutput = true;
//$n<something> >>means>> $node<something>
//$<smth>t or $t<smth> >>means>> temporary
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
		$oa->value = date("Y-m-d")."T".date("h:i:s");//'2008-01-09T18:13:34';
		$nroot->appendChild($oa);
	$doc->appendChild($nroot);
//------------------
$nclassf=$doc->CreateElement("Классификатор");
	$nroot->appendChild($nclassf);
$ncat=$doc->CreateElement("Каталог");
	$oa = $doc->createAttribute('СодержитТолькоИзменения');
		$oa->value = "false";
		$ncat->appendChild($oa);
	$nroot->AppendChild($ncat);
//------------------
$nt=$doc->CreateElement("Ид");
	$nt->appendChild($doc->createTextNode($idclassif));
	$nclassf->appendChild($nt);
$nt=$doc->CreateElement("Наименование");
	$nt->appendChild($doc->createTextNode("Классификатор (Каталог товаров)"));
	$nclassf->appendChild($nt);

$nown=$doc->CreateElement("Владелец");
	$nclassf->appendChild($nown);


//******************************* groups **********************
$r=sql1of3_getting_of_groups();
$ngrs=array();
$grids_long=array();
$sort=0;
while($d=mysql_fetch_assoc($r)){
	$ii=$d[id];
	$grid_long=str_replace("/","-",$d[path]);
	$grids_long[$ii]=$grid_long;
		$ngrs[$ii]=$doc->CreateElement("Группа");
			$ott=$doc->CreateElement("Ид");
				$ott->appendChild($doc->createTextNode($grid_long));
				$ngrs[$ii]->appendChild($ott);
			$ott=$doc->CreateElement("Наименование");
				$ott->appendChild($doc->createTextNode($d[name]));
				$ngrs[$ii]->appendChild($ott);
			$ott=$doc->CreateElement("БитриксСортировка");
				$ott->appendChild($doc->createTextNode($sort));
				$ngrs[$ii]->appendChild($ott);
	$o_parent_group=(!$d[parent_id]) ? $nclassf : $ngrs[($d[parent_id])]; 
	if (($oparent_node_grps=a_childGROUPSNode($o_parent_group))!==false) {
		$oparent_node_grps->appendChild($ngrs[$ii]);
		}
	else{
		$ottgrs=$doc->CreateElement("Группы");
			$ottgrs->appendChild($ngrs[$ii]);
		$o_parent_group->appendChild($ottgrs);
		}
	$sort+=100;
	}
$ngs1st=a_childGROUPSNode($nclassf);
	$ngr=$doc->CreateElement("Группа");
		$ott=$doc->CreateElement("Ид");
			$ott->appendChild($doc->createTextNode($groupid_nogroup));
			$ngr->appendChild($ott);
		$ott=$doc->CreateElement("Наименование");
			$ott->appendChild($doc->createTextNode($groupname_nogroup));
			$ngr->appendChild($ott);
		$ott=$doc->CreateElement("БитриксАктивность");
			$ott->appendChild($doc->createTextNode("false"));
			$ngr->appendChild($ott);
		$ott=$doc->CreateElement("БитриксСортировка");
			$ott->appendChild($doc->createTextNode($sort));
			$ngr->appendChild($ott);
		$ngs1st->appendChild($ngr);
//******************************* goods ***********************
$nt=$doc->CreateElement("Ид");
	$nt->appendChild($doc->createTextNode($idcat));
	$ncat->appendChild($nt);
$nt=$doc->CreateElement("ИдКлассификатора");
	$nt->appendChild($doc->createTextNode($idclassif));
	$ncat->appendChild($nt);
$nt=$doc->CreateElement("Наименование");
	$nt->appendChild($doc->createTextNode("Каталог товаров"));
	$ncat->appendChild($nt);
$nt=$doc->CreateElement("Свойства");
	$ncat->appendChild($nt);
$nt=$doc->CreateElement("СвойстваЭлементов");
	$ncat->appendChild($nt);
$ncat->appendChild($nown);
$ngd=$doc->CreateElement("Товары");
	$ncat->appendChild($ngd);

$r=sql2of3_getting_of_goods();
while($d=mysql_fetch_assoc($r)){
//var_dump($d);break;
	$ii=$d[prod_id];
	$ogd1=$doc->CreateElement("Товар");
		$ott=$doc->CreateElement("Ид");
			$ott->appendChild($doc->createTextNode($d[prod_id]));
			$ogd1->appendChild($ott);
		$isact_value= ($d[commodity_is_active]==1) ? 'true' : 'false';
		$ott=$doc->CreateElement("БитриксАктивность");
			$ott->appendChild($doc->createTextNode($isact_value));
			$ogd1->appendChild($ott);
		$ott=$doc->CreateElement("Наименование");
			$ott->appendChild($doc->createTextNode($d[prod_name]));
			$ogd1->appendChild($ott);
		$ott=$doc->CreateElement("Группы");
			$ogd1->appendChild($ott);
				if($d[groupids]!="" && $d[groupids]!="0"){
					$c_gidsar=explode(",",$d[groupids]);
					foreach($c_gidsar as $c_gid){
						$ott1=$doc->CreateElement("Ид");
						$ott1->appendChild($doc->createTextNode($grids_long[$c_gid]));
						$ott->appendChild($ott1);
						}
					}
				else{
					$ott1=$doc->CreateElement("Ид");
					$ott1->appendChild($doc->createTextNode($groupid_nogroup));
					$ott->appendChild($ott1);
					}
				if($d[discount]>0){
					$ott1=$doc->CreateElement("Ид");
					$ott1->appendChild($doc->createTextNode($groupid_discounts));
					$ott->appendChild($ott1);
					}
		//if($script_params[with_description]){
			$desc="";
			foreach($aDescrItems as $k=>$v){
				if(!empty($d[$k]))
				$desc.="<h3>".$v."</h3>".$d[$k]."\r\n\r\n";//htmlspecialchars();
				}
			$ott=$doc->CreateElement("Описание");
				$ott->appendChild($doc->createTextNode("\r\n".$desc."\r\n"));
				$ogd1->appendChild($ott);
			//}
		$prod_id_short=(!($iiit=strpos($d[prod_id],"_")))?$d[prod_id]:substr($d[prod_id],0,$iiit);
		$imgabssource=str_replace("__PRODID__",$prod_id_short,$img_source);
		if($script_params[p]=='yes' && file_exists($imgabssource)){
			$ott=$doc->CreateElement("Картинка");
				$imgdir_rel=substr(floor($prod_id_short/100),-1)."00";
				$ttpath=$img_dir_wsabs.$imgdir_rel."/".$prod_id_short.".png";
				$ott->appendChild($doc->createTextNode($ttpath));
				$ogd1->appendChild($ott);
			/*$ott=$doc->CreateElement("ЗначенияСвойств");
				$ogd1->appendChild($ott);
				$ott1=$doc->CreateElement("ЗначенияСвойства");
				$ott->appendChild($ott1);
					$ott2=$doc->CreateElement("Ид");
						$ott2->appendChild($doc->createTextNode("CML2_PREVIEW_PICTURE"));
						$ott1->appendChild($ott2);
					$ott2=$doc->CreateElement("Ид");
						$ttpath=$thumbs_dir_wsabs.$imgdir_rel."/".$prod_id_short.".png";
						$ott2->appendChild($doc->createTextNode($ttpath));
						$ott1->appendChild($ott2);*/
			}
		$ott=$doc->CreateElement("ЗначенияСвойств");
			//activity: setting it to OFF when product has been deleted
			/*					<ЗначенияСвойства>
						<Ид>CML2_ACTIVE</Ид>
						<Значение>true</Значение>
					</ЗначенияСвойства>
					*/
				$isact_value= ($d[commodity_is_active]==1) ? 'true' : 'false';
				$nt1=$doc->CreateElement("ЗначенияСвойства");
					$nt2=$doc->CreateElement("Ид");
						$nt2->appendChild($doc->createTextNode('CML2_ACTIVE'));
						$nt1->appendChild($nt2);
					$nt2=$doc->CreateElement("Значение");
						$nt2->appendChild($doc->createTextNode($isact_value));
						$nt1->appendChild($nt2);
					$nt2=$doc->CreateElement("ЗначениеСвойства");
						$nt3=$doc->CreateElement("Значение");
							$nt3->appendChild($doc->createTextNode($isact_value));
							$nt2->appendChild($nt3);
						$nt3=$doc->CreateElement("Описание");
							$nt3->appendChild($doc->createTextNode(""));
							$nt2->appendChild($nt3);
						$nt1->appendChild($nt2);
					$ott->appendChild($nt1);
			///activity
				//producer
				$manufacturer=trim(preg_replace("|\s+|"," ",$d[proizv_name]));//(preg_replace("|[^\w\- ']|","",$d[proizv_name]))));
				$nt1=$doc->CreateElement("ЗначенияСвойства");
					$nt2=$doc->CreateElement("Ид");
						$nt2->appendChild($doc->createTextNode('57'));
						$nt1->appendChild($nt2);
					$nt2=$doc->CreateElement("Значение");
						$nt2->appendChild($doc->createTextNode($manufacturer));
						$nt1->appendChild($nt2);
					$nt2=$doc->CreateElement("ЗначениеСвойства");
						$nt3=$doc->CreateElement("Значение");
							$nt3->appendChild($doc->createTextNode($manufacturer));
							$nt2->appendChild($nt3);
						$nt3=$doc->CreateElement("Описание");
							$nt3->appendChild($doc->createTextNode(""));
							$nt2->appendChild($nt3);
						$nt1->appendChild($nt2);
					$ott->appendChild($nt1);
					/*
					<ЗначенияСвойства>
						<Ид>57</Ид>
						<Значение>DDDD11</Значение>
						<ЗначениеСвойства>
							<Значение>DDDD11</Значение>
							<Описание></Описание>
						</ЗначениеСвойства>
					</ЗначенияСвойства>
					*/
				/*$nt1=$doc->CreateElement("ЗначенияСвойства");
					$nt2=$doc->CreateElement("Ид");
						$nt2->appendChild($doc->createTextNode('30'));
						$nt1->appendChild($nt2);
					$nt2=$doc->CreateElement("Значение");
						$nt2->appendChild($doc->createTextNode($manufacturer));
						$nt1->appendChild($nt2);
					$ott->appendChild($nt1);*/
			///producer
			//discount flag
						/*					<ЗначенияСвойства>
						<Ид>55</Ид>
						<Значение>8af46cee5edccf5848517d8515d47c66</Значение>
						<ЗначениеСвойства>
							<Значение>8af46cee5edccf5848517d8515d47c66</Значение>
							<Описание></Описание>
						</ЗначениеСвойства>
					</ЗначенияСвойства>*/
				$disc_value= ($d[discount]>0) ? "8af46cee5edccf5848517d8515d47c66" : "";
				$nt1=$doc->CreateElement("ЗначенияСвойства");
					$nt2=$doc->CreateElement("Ид");
						$nt2->appendChild($doc->createTextNode('55'));
						$nt1->appendChild($nt2);
					$nt2=$doc->CreateElement("Значение");
						$nt2->appendChild($doc->createTextNode($disc_value));
						$nt1->appendChild($nt2);
					$nt2=$doc->CreateElement("ЗначениеСвойства");
						$nt3=$doc->CreateElement("Значение");
							$nt3->appendChild($doc->createTextNode($disc_value));
							$nt2->appendChild($nt3);
						$nt3=$doc->CreateElement("Описание");
							$nt3->appendChild($doc->createTextNode(""));
							$nt2->appendChild($nt3);
						$nt1->appendChild($nt2);
					$ott->appendChild($nt1);
			///discount flag
			//recent income of goods
				$disc_value= ($d[is_recent]!='') ? "d2d03082e475bbd588092492d6c7a32d" : "";
				$nt1=$doc->CreateElement("ЗначенияСвойства");
					$nt2=$doc->CreateElement("Ид");
						$nt2->appendChild($doc->createTextNode('59'));
						$nt1->appendChild($nt2);
					$nt2=$doc->CreateElement("Значение");
						$nt2->appendChild($doc->createTextNode($disc_value));
						$nt1->appendChild($nt2);
					$nt2=$doc->CreateElement("ЗначениеСвойства");
						$nt3=$doc->CreateElement("Значение");
							$nt3->appendChild($doc->createTextNode($disc_value));
							$nt2->appendChild($nt3);
						$nt3=$doc->CreateElement("Описание");
							$nt3->appendChild($doc->createTextNode(""));
							$nt2->appendChild($nt3);
						$nt1->appendChild($nt2);
					$ott->appendChild($nt1);
			///recent income of goods
			//toplist
				$disc_value= ($d[is_intop]!='') ? "c21067241c9c1cb3236cf7b61f7b04f9" : "";
				$nt1=$doc->CreateElement("ЗначенияСвойства");
					$nt2=$doc->CreateElement("Ид");
						$nt2->appendChild($doc->createTextNode('60'));
						$nt1->appendChild($nt2);
					$nt2=$doc->CreateElement("Значение");
						$nt2->appendChild($doc->createTextNode($disc_value));
						$nt1->appendChild($nt2);
					$nt2=$doc->CreateElement("ЗначениеСвойства");
						$nt3=$doc->CreateElement("Значение");
							$nt3->appendChild($doc->createTextNode($disc_value));
							$nt2->appendChild($nt3);
						$nt3=$doc->CreateElement("Описание");
							$nt3->appendChild($doc->createTextNode(""));
							$nt2->appendChild($nt3);
						$nt1->appendChild($nt2);
					$ott->appendChild($nt1);
			///toplist
			$ogd1->appendChild($ott);
	$ngd->appendChild($ogd1);
	}
//*************************************************************
sql3of3_cleaning();
$res=$doc->save($script_params[o]);
if($res>0)
	echo "OK ".number_format($res/1048576,3,'.','')."Mb";
else
	echo "Error with saving of the file";

function sql1of3_getting_of_groups(){
$qar=array(

"drop table if exists _delme_cat_c2c;",

"create table _delme_cat_c2c (id int not null,parent_id int not null,name tinytext not null,ordernum int not null) DEFAULT CHARSET=utf8;",

"insert into _delme_cat_c2c select * from ((select c2c.*,c.name,c.id_inc from c2c left join categories c using(id)) union (SELECT id,0,name,id_inc FROM categories where id<=19)) as ttt;",

"select t5.name,
concat(if(isnull(t0.id),'',concat(t0.id,'/')),if(isnull(t1.id),'',concat(t1.id,'/')),if(isnull(t2.id),'',concat(t2.id,'/')),if(isnull(t3.id),'',concat(t3.id,'/')),if(isnull(t4.id),'',concat(t4.id,'/')),t5.id) as path,
t5.parent_id,
t5.id,
concat(
	if(isnull(t0.ordernum),'',concat(lpad(t0.ordernum,6,'0'),'/')),
	if(isnull(t1.ordernum),'',concat(lpad(t1.ordernum,6,'0'),'/')),
	if(isnull(t2.ordernum),'',concat(lpad(t2.ordernum,6,'0'),'/')),
	if(isnull(t3.ordernum),'',concat(lpad(t3.ordernum,6,'0'),'/')),
	if(isnull(t4.ordernum),'',concat(lpad(t4.ordernum,6,'0'),'/')),
	if(isnull(t5.ordernum),'',concat(lpad(t5.ordernum,6,'0'),'/'))) as orderpath
from _delme_cat_c2c t0
right join _delme_cat_c2c t1 on t1.parent_id=t0.id 
right join _delme_cat_c2c t2 on t2.parent_id=t1.id 
right join _delme_cat_c2c t3 on t3.parent_id=t2.id 
right join _delme_cat_c2c t4 on t4.parent_id=t3.id 
right join _delme_cat_c2c t5 on t5.parent_id=t4.id
where t0.parent_id=0 or t1.parent_id=0 or t2.parent_id=0 or t3.parent_id=0 or t4.parent_id=0 or t5.parent_id=0
order by orderpath;",

);
mysql_query($qar[0]);
mysql_query($qar[1]);
mysql_query($qar[2]);
$r=mysql_query($qar[3]);//echo $qar[3]; die();
return $r;
}
function sql2of3_getting_of_goods(){
global $script_params,$t_products;
//if($script_params[with_description]){
$qf="
	left join 
 desc_to_nomtov3 AS d2n
    ON d2n.nidnomtov=n.nidnomtov
   AND n.storetype=1
   AND d2n.storetype=1
left
  JOIN descriptions3 AS rd
    ON d2n.description_id=rd.id
	";
$qs=" rd.`composition`
     , rd.`drugformdescr`
     , rd.`characters`
     , rd.`pharmaactions`
     , rd.`actonorg`
     , rd.`componentsproperties`
     , rd.`pharmakinetic`
     , rd.`pharmadynamic`
     , rd.`clinicalpharmacology`
     , rd.`direction`
     , rd.`indications`
     , rd.`recommendations`
     , rd.`contraindications`
     , rd.`pregnancyuse`
     , rd.`sideactions`
     , rd.`interactions`
     , rd.`usemethodanddoses`
     , rd.`instrforpac`
     , rd.`overdose`
     , rd.`precautions`
     , rd.`specialguidelines`
     , rd.`manufacturer`
     , rd.`literature`
     , rd.`comment`,";
	/*}
else
	{
	$qf="";
	$qs="";
	}*/
$q="SELECT 
	".$qs.
	" concat(n.`nidnomtov`,'_','0000') AS prod_id". //"n.nidnomtov AS prod_id ".
    ", n.`prod_line1` AS prod_name
     , p.`prod_price`,
	 dis.discount,
	 if(isnull(recent.prodid),'','y') as is_recent,
	 if(isnull(toplist.prodid),'','y') as is_intop,
	 if(p.avail=1,1,0) as commodity_is_active
     , CASE WHEN m2.name IS NOT NULL AND m2.name <> '' THEN m2.name WHEN n.`nidproizv`<>0 AND m.cname <> 'Неопределено' THEN m.cname ELSE '' END AS proizv_name
     , CASE WHEN d2.cname IS NOT NULL AND d2.cname <> 'Неопределено' THEN d2.cname WHEN n.`nidproizv`<>0 AND d.cname <> 'Неопределено' THEN d.cname ELSE '' END AS country_name,
group_concat(prod2c.category_id) as groupids
      
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
left 
	join goods_discountprices dis 
		on dis.prodid=p.nidnomtov and dis.dscode=p.shopcode
left 
	join goods_recent recent 
		on recent.prodid=p.nidnomtov
left 
	join goods_toplist toplist
		on toplist.prodid=p.nidnomtov
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
left join nomtov_to_category prod2c on 
	prod2c.nidnomtov=n.nidnomtov
".$qf."
WHERE 
	p.shopcode='D500' AND r2b.active IS NULL 
	and p.avail=1
group by 
	p.nidnomtov
#having not isnull(groupid)
;";
//echo $q;die();
return mysql_query($q);
	}
function sql3of3_cleaning(){
	mysql_query("drop table _delme_cat_c2c;");
	}
function a_childGROUPSNode($p) {
 if ($p->hasChildNodes()) {
  foreach ($p->childNodes as $c) {
   if ($c->nodeType == XML_ELEMENT_NODE && $c->nodeName == "Группы")
    return $c;
  }
 }
 return false;
}
?>
