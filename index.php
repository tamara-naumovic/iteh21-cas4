<?php
require 'flight/Flight.php';
require 'jsonindent.php';
//registrujemo promenljivu db, koja ja objekat klase Database, čiji je parametar za konstruktor naziv baze a to je rest
//kada se bude pokrenula fja $db = Flight::db(), instanciraće se konekcija sa bazom
Flight::register('db', 'Database', array('rest'));
$json_podaci = file_get_contents("php://input");
Flight::set('json_podaci', $json_podaci );
Flight::route('/', function(){
    echo "hello world";
});

Flight::route('GET /novosti', function(){
	header ("Content-Type: application/json; charset=utf-8");
	$db = Flight::db();
	$db->select();
	$niz=array();
	while ($red=$db->getResult()->fetch_object()){
		$niz[] = $red;
	}
	//JSON_UNESCAPED_UNICODE parametar je uveden u PHP verziji 5.4
	//Omogućava Unicode enkodiranje JSON fajla
	//Bez ovog parametra, vrši se escape Unicode karaktera
	//Na primer, slovo č će biti \u010
	$json_niz = json_encode ($niz,JSON_UNESCAPED_UNICODE);
	echo indent($json_niz);
	return false;
});
Flight::route('GET /novosti/@id', function($id){
	header ("Content-Type: application/json; charset=utf-8");
	$db = Flight::db();
	$db->select("novosti", "*", "kategorije", "kategorija_id", "id", "novosti.id = ".$id, null);
	$red=$db->getResult()->fetch_object();
	//JSON_UNESCAPED_UNICODE parametar je uveden u PHP verziji 5.4
	//Omogućava Unicode enkodiranje JSON fajla
	//Bez ovog parametra, vrši se escape Unicode karaktera
	//Na primer, slovo č će biti \u010
	$json_niz = json_encode ($red,JSON_UNESCAPED_UNICODE);
	echo indent($json_niz);
	return false;
});
Flight::route('GET /kategorije', function(){
	header ("Content-Type: application/json; charset=utf-8");
	$db = Flight::db();
	$db->select("kategorije", "*", null, null, null, null, null);
	$niz=array();
	$i=0;
	while ($red=$db->getResult()->fetch_object()){
		
		$niz[$i]["id"] = $red->id;
		$niz[$i]["kategorija"] = $red->kategorija;
		$db_pomocna=new Database("rest");
		$db_pomocna->select("novosti", "*", null, null, null, "novosti.kategorija_id = ".$red->id, null);
		while ($red_pomocna=$db_pomocna->getResult()->fetch_object()){
			$niz[$i]["novosti"][]=$red_pomocna;
		}
		$i++;
	}
	//JSON_UNESCAPED_UNICODE parametar je uveden u PHP verziji 5.4
	//Omogućava Unicode enkodiranje JSON fajla
	//Bez ovog parametra, vrši se escape Unicode karaktera
	//Na primer, slovo č će biti \u010
	$json_niz = json_encode ($niz,JSON_UNESCAPED_UNICODE);
	echo indent($json_niz);
	return false;
});
Flight::route('GET /kategorije/@id', function($id){
	header ("Content-Type: application/json; charset=utf-8");
	$db = Flight::db();
	$db->select("kategorije", "*", null, null, null, "kategorije.id = ".$id, null);
	$niz=array();
	
	$red=$db->getResult()->fetch_object();
		
		$niz["id"] = $red->id;
		$niz["kategorija"] = $red->kategorija;
		$db_pomocna=new Database("rest");
		$db_pomocna->select("novosti", "*", null, null, null, "novosti.kategorija_id = ".$red->id, null);
		while ($red_pomocna=$db_pomocna->getResult()->fetch_object()){
		$niz["novosti"][]=$red_pomocna;
		}

	//JSON_UNESCAPED_UNICODE parametar je uveden u PHP verziji 5.4
	//Omogućava Unicode enkodiranje JSON fajla
	//Bez ovog parametra, vrši se escape Unicode karaktera
	//Na primer, slovo č će biti \u010
	$json_niz = json_encode ($niz,JSON_UNESCAPED_UNICODE);
	echo indent($json_niz);
	return false;


});
Flight::route('POST /novosti', function(){
	header ("Content-Type: application/json; charset=utf-8");
	$db = Flight::db();
	$podaci_json = Flight::get("json_podaci");
	$podaci = json_decode ($podaci_json);
	if ($podaci == null){
	$odgovor["poruka"] = "Niste prosledili podatke";
	$json_odgovor = json_encode ($odgovor);
	echo $json_odgovor;
	return $json_odgovor;
	} else {
	if (!property_exists($podaci,'naslov')||!property_exists($podaci,'tekst')||!property_exists($podaci,'kategorija_id')){
			$odgovor["poruka"] = "Niste prosledili korektne podatke";
			$json_odgovor = json_encode ($odgovor,JSON_UNESCAPED_UNICODE);
			echo $json_odgovor;
	
	} else {
			$podaci_query = array();
			foreach ($podaci as $k=>$v){
				$v = "'".$v."'";
				$podaci_query[$k] = $v;
			}
			if ($db->insert("novosti", "naslov, tekst, kategorija_id, datumvreme", array($podaci_query["naslov"], $podaci_query["tekst"], $podaci_query["kategorija_id"], 'NOW()'))){
				$odgovor["poruka"] = "Novost je uspešno ubačena";
				$json_odgovor = json_encode ($odgovor,JSON_UNESCAPED_UNICODE);
				echo $json_odgovor;
			} else {
				$odgovor["poruka"] = "Došlo je do greške pri ubacivanju novosti";
				$json_odgovor = json_encode ($odgovor,JSON_UNESCAPED_UNICODE);
				echo $json_odgovor;
			}
	}
	}	
	}
);
Flight::route('POST /kategorije', function(){
	header ("Content-Type: application/json; charset=utf-8");
	$db = Flight::db();
	$podaci_json = Flight::get("json_podaci");
	$podaci = json_decode ($podaci_json);
	if ($podaci == null){
	$odgovor["poruka"] = "Niste prosledili podatke";
	$json_odgovor = json_encode ($odgovor);
	echo $json_odgovor;
	} else {
	if (!property_exists($podaci,'kategorija')){
			$odgovor["poruka"] = "Niste prosledili korektne podatke";
			$json_odgovor = json_encode ($odgovor,JSON_UNESCAPED_UNICODE);
			echo $json_odgovor;
			return false;
	
	} else {
			$podaci_query = array();
			foreach ($podaci as $k=>$v){
				$v = "'".$v."'";
				$podaci_query[$k] = $v;
			}
			if ($db->insert("kategorije", "kategorija", array($podaci_query["kategorija"]))){
				$odgovor["poruka"] = "Kategorija je uspešno ubačena";
				$json_odgovor = json_encode ($odgovor,JSON_UNESCAPED_UNICODE);
				echo $json_odgovor;
				return false;
			} else {
				$odgovor["poruka"] = "Došlo je do greške pri ubacivanju novosti";
				$json_odgovor = json_encode ($odgovor,JSON_UNESCAPED_UNICODE);
				echo $json_odgovor;
				return false;
			}
	}
	}	


});
Flight::route('PUT /novosti/@id', function($id){
	header ("Content-Type: application/json; charset=utf-8");
	$db = Flight::db();
	$podaci_json = Flight::get("json_podaci");
	$podaci = json_decode ($podaci_json);
	if ($podaci == null){
	$odgovor["poruka"] = "Niste prosledili podatke";
	$json_odgovor = json_encode ($odgovor);
	echo $json_odgovor;
	} else {
	if (!property_exists($podaci,'naslov')||!property_exists($podaci,'tekst')||!property_exists($podaci,'kategorija_id')){
			$odgovor["poruka"] = "Niste prosledili korektne podatke";
			$json_odgovor = json_encode ($odgovor,JSON_UNESCAPED_UNICODE);
			echo $json_odgovor;
			return false;
	
	} else {
			$podaci_query = array();
			foreach ($podaci as $k=>$v){
				$v = "'".$v."'";
				$podaci_query[$k] = $v;
			}
			if ($db->update("novosti", $id, array('naslov','tekst','kategorija_id'),array($podaci->naslov, $podaci->tekst,$podaci->kategorija_id))){
				$odgovor["poruka"] = "Novost je uspešno izmenjena";
				$json_odgovor = json_encode ($odgovor,JSON_UNESCAPED_UNICODE);
				echo $json_odgovor;
				return false;
			} else {
				$odgovor["poruka"] = "Došlo je do greške pri izmeni novosti";
				$json_odgovor = json_encode ($odgovor,JSON_UNESCAPED_UNICODE);
				echo $json_odgovor;
				return false;
			}
	}
	}	




});
Flight::route('PUT /kategorije/@id', function($id){
	header ("Content-Type: application/json; charset=utf-8");
	$db = Flight::db();
	$podaci_json = Flight::get("json_podaci");
	$podaci = json_decode ($podaci_json);
	if ($podaci == null){
	$odgovor["poruka"] = "Niste prosledili podatke";
	$json_odgovor = json_encode ($odgovor);
	echo $json_odgovor;
	} else {
	if (!property_exists($podaci,'kategorija')){
			$odgovor["poruka"] = "Niste prosledili korektne podatke";
			$json_odgovor = json_encode ($odgovor,JSON_UNESCAPED_UNICODE);
			echo $json_odgovor;
			return false;
	
	} else {
			$podaci_query = array();
			foreach ($podaci as $k=>$v){
				$v = "'".$v."'";
				$podaci_query[$k] = $v;
			}
			if ($db->update("kategorije", $id, array('kategorija'),array($podaci->kategorija))){
				$odgovor["poruka"] = "Kategorija je uspešno izmenjena";
				$json_odgovor = json_encode ($odgovor,JSON_UNESCAPED_UNICODE);
				echo $json_odgovor;
				return false;
			} else {
				$odgovor["poruka"] = "Došlo je do greške pri izmeni kategorije";
				$json_odgovor = json_encode ($odgovor,JSON_UNESCAPED_UNICODE);
				echo $json_odgovor;
				return false;
			}
	}
	}	

});
Flight::route('DELETE /novosti/@id', function($id){
		header ("Content-Type: application/json; charset=utf-8");
		$db = Flight::db();
		if ($db->delete("novosti", array("id"),array($id))){
				$odgovor["poruka"] = "Novost je uspešno izbrisana";
				$json_odgovor = json_encode ($odgovor,JSON_UNESCAPED_UNICODE);
				echo $json_odgovor;
				return false;
		} else {
				$odgovor["poruka"] = "Došlo je do greške prilikom brisanja novosti";
				$json_odgovor = json_encode ($odgovor,JSON_UNESCAPED_UNICODE);
				echo $json_odgovor;
				return false;
		
		}		
				
});
Flight::route('DELETE /kategorije/@id', function($id){
		header ("Content-Type: application/json; charset=utf-8");
		$db = Flight::db();
		if ($db->delete("kategorije", array("id"),array($id))){
				$odgovor["poruka"] = "Kategorija je uspešno izbrisana";
				$json_odgovor = json_encode ($odgovor,JSON_UNESCAPED_UNICODE);
				echo $json_odgovor;
				return false;
		} else {
				$odgovor["poruka"] = "Došlo je do greške prilikom brisanja kategorije";
				$json_odgovor = json_encode ($odgovor,JSON_UNESCAPED_UNICODE);
				echo $json_odgovor;
				return false;
		
		}		


});


Flight::start();
?>