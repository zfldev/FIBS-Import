<?php
/*
Plugin Name: FIBS Import
Plugin URI: https://www.zfl.fau.de/
Description: Erstellt durch Eingabe einer FIBS-ID (Fortbildung in bayerischen Schulen) ein CF7 (Contact Form 7) Formular zum übertragen einer Fortbildung. Achtung es muss ein passendes CF7 Formular existieren. Man kann das sicherlich schöner programmieren aber es musste schnell gehen. Verwende den Shortcode wie folgt [fibsimport cf7id="8124" wpnonce="0d67ebd724" currentid="8143"]. Dabei ist cf7id die ID des passenden CF7 Formulars, wpnonce das hidden field des CF7 Formulars (z.B. CF7 shortcode einfach mal irgendwo einfügen und wpnonce kopieren) und currentid ist die ID des Posts oder der Page in welcher dieser Shortcode eingefügt wird. Diese Plugin erfordert das CF7 Plugin in Version 4.2.1
Author: Zentrum für Lehrerinnen- und Lehrerbildung der FAU
Author URI: https://www.zfl.fau.de/
Version: 1.0
Min WP Version: 4.2.2
*/

function zfl_fibs_code($atts) {
	// Attributes
	extract( shortcode_atts(
		array(
			'cf7id' => '8124',
			'wpnonce' => '0d67ebd724',
			'currentid' => '8143',
		), $atts )
	);
		
		if(!isset($vid)){ ?>
		
		<form name="importdata" action="" method="post"><span class="wpcf7-form-control-wrap url">
		<p><span class="label">Durch Eingabe der <strong>Lehrgangs-ID</strong> oder des <strong>direkten Links</strong> der Fortbildung in FIBS können eine Vielzahl von Daten automatisch übernommen werden. Manches muss von Ihnen manuell vervollständigt werden.</span></p>
		<input name="vid" class="wpcf7-form-control wpcf7-text" value="<?php echo $_GET['vid']?>" size="70" maxlength="70" aria-required="true" aria-invalid="false" placeholder="FIBS Lehrgangs-ID oder Link zur Detailseite" type="text"> </span> <br>
		<input class="wpcf7-form-control wpcf7-submit" type="submit" formmethod="get" value="Daten übernehmen"/> <input type="hidden" name="debug" value="0"/> <input type="hidden" name="cut" value="1"/>
		</form>
		<br>
		<?php }?>
		<?php
		$vid = $_GET['vid'];
		$fibsid = preg_replace("/[^0-9]/","",$vid);
		$fibsid = (int) $fibsid;
		$url = 'https://fibs.alp.dillingen.de/suche/details.php?v_id=' . $fibsid;

		$html = file_get_contents($url);
		//Create a new DOM document
		$dom = new DOMDocument;
		//Parse the HTML. The @ is used to suppress any parsing errors
		//that will be thrown if the $html content isn't valid XHTML.
		@$dom->loadHTML($html);
		//Get all td fields out of the Page
		$data = $dom->getElementsByTagName('td');
		//Setze wichtige Werte
		$data_array = array();
		$wert = 1;
		$fibstag = 'leer';
		foreach ($data as $singledata){
				 $lastkeyname;
				 if ($wert % 2 != 0) {
						// Ungerader wert ist Bezeichner
				 		$node = $dom->getElementsByTagName('td')->item($wert);
				 		$keyname = $node->nodeValue;
				 		
						if ($wert == 1){	 		
								$keyname = 'fibstag';
								$fibstag = $node->nodeValue;
								$fibstag = str_replace(" - Details","",$fibstag);
								$fibstag = str_replace("Lehrgang ","",$fibstag);
						}
				 		$keyname = sanitize_title($keyname);
				 		$lastkeyname = $keyname;
				 		$data_array[$keyname] = ""; 		
						
						} else {
						// gerader wert ist Inhalt
						$node = $dom->getElementsByTagName('td')->item($wert);
						$content = innerXML($node);	
						//Daten bereinigen falls nicht Veranstalter oder Veranstaltungsort
						if($lastkeyname != 'veranstalter' && $lastkeyname != 'veranstaltungsort'  && $lastkeyname != 'dauer'){
							// Hier passiert nichts
						} else {
							$content = preg_replace("/<br\W*?\/>/", '#BRHTMLTAG#', $content); // ersetzt br tags
							
							// Veranstalter-Daten aufteilen
							if($lastkeyname == 'veranstalter'){
									$content = substr_replace($content, "veranstalter-name#", 0, 0);
									$content = str_replace("#BRHTMLTAG#Leitung:","#leitung#",$content);
									$content = str_replace("#BRHTMLTAG#Ansprechpartner: ","#ansprechpartner#",$content);
									$content = str_replace("#BRHTMLTAG#Tel:","#ansprechpartner-telefon#",$content);
									$content = str_replace("#BRHTMLTAG#Fax:","#ansprechpartner-fax#",$content);
									$content = str_replace("#BRHTMLTAG#E-Mail:","#ansprechpartner-email#",$content);
									$content = str_replace("#BRHTMLTAG#E-Mail Sekretariat:","#ansprechparter-email-2#",$content);
									$content = str_replace("#BRHTMLTAG#Homepage:","#veranstalter-link#",$content);
									$content = preg_replace('/#BRHTMLTAG#/', '#veranstalter-strasse#', $content, 1);
									$content = preg_replace('/#BRHTMLTAG#/', '#veranstalter-plz#', $content, 1);
									$posreplace = strpos($content, "#veranstalter-plz#");
									$posreplace = $posreplace + 10 + 13; // um PLZ verschieben
									$content = substr_replace($content, "#veranstalter-ort#", $posreplace, 0);
									//Filter
									$content = CleanTheText( FauTextFilter( $content ) );
									//Erstelle einen Array
									$content = OneDimArrayToTwoDimArray( explode ( "#", $content) );
		
							}
							// Veranstaltungsort-Daten aufteilen
							if($lastkeyname == 'veranstaltungsort'){
									$content = substr_replace($content, "veranstaltungsort-name#", 0, 0);
									$content = str_replace("#BRHTMLTAG#Leitung:","#veranstaltungsort-leitung#",$content);
									$content = str_replace("#BRHTMLTAG#Ansprechpartner: ","#veranstaltungsort-ansprechparnter#",$content);
									$content = str_replace("#BRHTMLTAG#Tel:","#veranstaltungsort-telefon#",$content);
									$content = str_replace("#BRHTMLTAG#E-Mail:","#veranstaltungsort-email#",$content);
									$content = str_replace("#BRHTMLTAG#Homepage:","#veranstaltungsort-link#",$content);
									$content = str_replace("#BRHTMLTAG#Raum:","#veranstaltungsort-raum#",$content);
									$content = preg_replace('/#BRHTMLTAG#/', '#veranstaltungsort-strasse#', $content, 1);
									$content = preg_replace('/#BRHTMLTAG#/', '#veranstaltungsort-plz#', $content, 1);
									$posreplace = strpos($content, "#veranstaltungsort-plz#");
									$posreplace = $posreplace + 10 + 18; // um PLZ verschieben
									$content = substr_replace($content, "#veranstaltungsort-ort#", $posreplace, 0);
									//Filter
									$content = CleanTheText( FauTextFilter( $content ) );
									//Erstelle einen Array
									$content = OneDimArrayToTwoDimArray( explode ( "#", $content) );	
							}
							// Termin-Details aufteilen
							if($lastkeyname == 'dauer'){
									$content = str_replace("von:","start#",$content);
									$content = str_replace(", ","-",$content);
									$content = str_replace("bis:","#ende#",$content);
									$content = str_replace("#BRHTMLTAG#","",$content);
									$content = str_replace("# ","#",$content);
									$content = str_replace(" #","#",$content);
									$content = str_replace("Uhr","",$content);
									$content = str_replace(" ","",$content);
									$content = str_replace("-"," ",$content);
									$content = preg_replace("#<[^>]+>#", '', $content); // Entferne hmtl tags
									//Erstelle einen Array
									$content = OneDimArrayToTwoDimArray( explode ( "#", $content) );
							}
						}
						// Setzte Wert auf FIBS-Aktenzeichen beim zweiten Durchlauf	
						if ($wert == 2){
							$content = $fibstag;
						}
					// Daten in Array schreiben
					$data_array[$lastkeyname] = $content;
					}
			$wert++;
		}
		$data_array['fibsid'] = $fibsid;
		$raw_data_array = array();
		$raw_data_array = $data_array;
		
		if( isset($data_array['name']) ) { $data_array['name'] = sanitize_text_field( $data_array['name']); }
		if( isset($data_array['beschreibung']) ) { $data_array['beschreibung'] = sanitize_text_field($data_array['beschreibung']); }
		if( isset($data_array['zusatzinformationen-im-netz']) ) { $data_array['zusatzinformationen-im-netz'] = sanitize_text_field($data_array['zusatzinformationen-im-netz']); }
		
		// Texte kürzen sofern vom Benutzer gewählt
		if( $_GET['cut'] == True){
			if( isset($data_array['name']) ) { $data_array['name'] = CutTextToLenght( $data_array['name'] , 120 ); }
			if( isset($data_array['beschreibung']) ) { $data_array['beschreibung'] = CutTextToLenght( $data_array['beschreibung'] , 750 ); } 
			if( isset($data_array['veranstalter']['veranstalter-name']) ) { $data_array['veranstalter']['veranstalter-name'] = CutTextToLenght( $data_array['veranstalter']['veranstalter-name'] , 50 ); }
		}
		
		///////////////////////////////////////////////////////////////////////////////////
		// Schularten Erkennen
		///////////////////////////////////////////////////////////////////////////////////
		if( isset($data_array['schulart']) ) {
			$schulart_array = array();
			$data_array['schulart'] = str_replace(", ",",",$data_array['schulart']);
			$data_array['schulart']= explode ( ",", $data_array['schulart']);
			foreach ($data_array['schulart'] as $key => $value) {
				switch ($value) {
					case 'Grundschule':
						$schulart_array['0'] = True;
						break;
					case 'Mittelschule':
						$schulart_array['1'] = True;
						break;
					case 'Realschule':
						$schulart_array['2'] = True;
						break;
					case 'Gymnasium':
						$schulart_array['3'] = True;
						break;
					case 'Fach- u. Berufsoberschulen':
						$schulart_array['4'] = True;
						break;
					case 'berufliche Schulen':
						$schulart_array['5'] = True;
						break;
					case 'Förderschulen':
						$schulart_array['6'] = True;
						break;
					case 'schulartübergreifend':
						$schulart_array['0'] = True;
						$schulart_array['1'] = True;
						$schulart_array['2'] = True;
						$schulart_array['3'] = True;
						$schulart_array['4'] = True;
						$schulart_array['5'] = True;
						$schulart_array['6'] = True;
						break;
					default:
						break;
				}
			}
		
			// Setze wert "Alle"
			if ($schulart_array['0'] && $schulart_array['1'] && $schulart_array['2'] && $schulart_array['3'] && $schulart_array['4'] && $schulart_array['5']) {
				$schulart_array['7'] = True;
			}
			// Schreibe in Hauptarray
			$data_array['schulart'] = $schulart_array;
		}
		///////////////////////////////////////////////////////////////////////////////////
		
		///////////////////////////////////////////////////////////////////////////////////
		// Zielgruppen Erkennen
		///////////////////////////////////////////////////////////////////////////////////
		if( isset($data_array['zielgruppe']) ) {
			$zielgruppe_array = array();
			$data_array['zielgruppe'] = str_replace(", ",",",$data_array['zielgruppe']);
			$data_array['zielgruppe'] = explode ( ",", $data_array['zielgruppe']);
			foreach ($data_array['zielgruppe'] as $key => $value) {
				switch ($value) {
					case 'Lehrer': 
					$zielgruppe_array['0'] = True;
					break;
					case 'Förderlehrer': $zielgruppe_array['1'] = True;
					break;
					case 'Fachlehrer': $zielgruppe_array['2'] = True;
					break;
					case 'Religionslehrkräfte': $zielgruppe_array['3'] = True;
					break;
					case 'Führungspersonal': $zielgruppe_array['4'] = True;
					break;
					case 'Seminarlehrer': $zielgruppe_array['5'] = True;
					break;
					case 'Beratungslehrer': $zielgruppe_array['6'] = True;
					break;
					case 'Verbindungslehrer': $zielgruppe_array['7'] = True;
					break;
					case 'Fachbetreuer': $zielgruppe_array['8'] = True;
					break;
					case 'IT-Systembetreuer': $zielgruppe_array['9'] = True;
					break;
					case 'Fachberater': $zielgruppe_array['10'] = True;
					break;
					case 'Fachmitarbeiter': $zielgruppe_array['11'] = True;
					break;
					case 'Medienpäd.-informationstechn. Berater': $zielgruppe_array['12'] = True;
					break;
					case 'Heilpädagogen im Sonderschuldienst': $zielgruppe_array['13'] = True;
					break;
					case 'Schulpsychologen': $zielgruppe_array['14'] = True;
					break;
					case 'Verwaltungsangestellte': $zielgruppe_array['15'] = True;
					break;
					default:
					break;
				}
			}
			// Schreibe in Hauptarray
			$data_array['zielgruppe'] = $zielgruppe_array;
		}
		///////////////////////////////////////////////////////////////////////////////////
		
		///////////////////////////////////////////////////////////////////////////////////
		// Datumsangaben aufteilen und interpretieren
		///////////////////////////////////////////////////////////////////////////////////
		if( isset($data_array['dauer']) ) {
			$data_array['dauer']['start-datum']= substr($data_array['dauer']['start'], 0, 8);
			$data_array['dauer']['start-zeit']= substr($data_array['dauer']['start'], 9, 15);
			$data_array['dauer']['ende-datum']= substr($data_array['dauer']['ende'], 0, 8);
			$data_array['dauer']['ende-zeit']= substr($data_array['dauer']['ende'], 9, 15);
		
			if ($data_array['dauer']['start-datum'] == $data_array['dauer']['ende-datum']){
			// Nur ein Tag
			$data_array['dauer']['termin-1'] = $data_array['dauer']['start-datum'];
			$data_array['dauer']['termin-1-start'] = $data_array['dauer']['start-zeit'];
			$data_array['dauer']['termin-1-ende'] = $data_array['dauer']['ende-zeit'];
			} else {
			// Mehrere Tage
			$data_array['dauer']['termin-1'] = $data_array['dauer']['start-datum'];
			$data_array['dauer']['termin-1-start'] = $data_array['dauer']['start-zeit'];
			$data_array['dauer']['termin-2'] = $data_array['dauer']['ende-datum'];
			$data_array['dauer']['termin-2-ende'] = $data_array['dauer']['ende-zeit'];
			}
		}
		///////////////////////////////////////////////////////////////////////////////////
		
		
		///////////////////////////////////////////////////////////////////////////////////
		// Gängige ungewünschte Eingaben ändern und Felder Bereinigen
		///////////////////////////////////////////////////////////////////////////////////
		$data_array['name'] = CleanTheText($data_array['name']);
		$data_array['beschreibung'] = FauTextFilter(CleanTheText($data_array['beschreibung']));
		
		if( isset($data_array['veranstalter']['leitung']) ) {
		$data_array['veranstalter']['leitung'] = $content = str_replace(" und ",", ",$data_array['veranstalter']['leitung']);
		$data_array['veranstalter']['leitung'] = $content = str_replace("StR","",$data_array['veranstalter']['leitung']);
		$data_array['veranstalter']['leitung'] = $content = str_replace("OStR","",$data_array['veranstalter']['leitung']);
		$data_array['veranstalter']['leitung'] = $content = str_replace("Frau ","",$data_array['veranstalter']['leitung']);
		$data_array['veranstalter']['leitung'] = $content = str_replace("Herr ","",$data_array['veranstalter']['leitung']);
		}
		if( isset($data_array['veranstalter']['ansprechpartner-email']) ) {$data_array['veranstalter']['ansprechpartner-email'] = strtolower($data_array['veranstalter']['ansprechpartner-email']);}
		if( isset($data_array['veranstalter']['ansprechpartner-telefon']) ) {$data_array['veranstalter']['ansprechpartner-telefon'] = PhoneFilter($data_array['veranstalter']['ansprechpartner-telefon']);}
		if( isset($data_array['veranstalter']['ansprechpartner-fax']) ) {$data_array['veranstalter']['ansprechpartner-fax'] = PhoneFilter($data_array['veranstalter']['ansprechpartner-fax']);}
		if( isset($data_array['veranstaltungsort']['veranstaltungsort-telefon']) ) { $data_array['veranstaltungsort']['veranstaltungsort-telefon'] = PhoneFilter($data_array['veranstaltungsort']['veranstaltungsort-telefon']);}
		
		if($content['veranstaltungsort']["veranstaltungsort-strasse"] == "Regensburger Str. 160"){
			$content['veranstaltungsort']["veranstaltungsort-name"] == "Campus Regensburger Straße";
		}			
		if( $content['veranstaltungsort']["veranstaltungsort-strasse"] == "Dutzendteichstr. 24" ){
			$content['veranstaltungsort']["veranstaltungsort-name"] == "Bildungshaus St. Paul";
		}
		///////////////////////////////////////////////////////////////////////////////////
			
		///////////////////////////////////////////////////////////////////////////////////
		// Gänger Fehler welcher auftaucht wenn nach dem Veranstaltungsortnamen eine weitere Zeile zwischen Name und Straße ist.
		///////////////////////////////////////////////////////////////////////////////////
		if( preg_match_all( "/[0-9]/", $data_array['veranstaltungsort']['veranstaltungsort-plz'] ) < 4 &&  isset($data_array['veranstaltungsort']['BRHTMLTAG']) ){
			// Wohl bekannter Fehler das Raum im Veranstaltungsort
			$data_array['veranstaltungsort']['veranstaltungsort-strasse'] = $data_array['veranstaltungsort']['veranstaltungsort-plz'] . $data_array['veranstaltungsort']['veranstaltungsort-ort'];
			$data_array['veranstaltungsort']['veranstaltungsort-plz'] = substr($data_array['veranstaltungsort']['BRHTMLTAG'], 0, 5);
			$data_array['veranstaltungsort']['veranstaltungsort-ort'] = substr($data_array['veranstaltungsort']['BRHTMLTAG'], 6, 56);
		}
		///////////////////////////////////////////////////////////////////////////////////

		///////////////////////////////////////////////////////////////////////////////////
		// Erzeuge HTML Formular passend zu CF7 Formular
		///////////////////////////////////////////////////////////////////////////////////
		$data_array['teilnehmerzahl']= explode ( ', aktuell ', str_replace('Bewerber über FIBS', '', $data_array['maximale-teilnehmerzahl']));
		
		//$geturl = create_get_url($data_array);
		?>
		<?php if(isset($vid) && $vid > ' '){?>
		<?php if($_GET['debug'] == true){ ?>
		<div style="background: #ccc; color: #333; padding: 20px;">
		<p><span class="label" style="color: #003366; font-weight:bold;">Diese Daten werden nicht übernommen helfen Ihnen jedoch beim Vervollständigen der Eingaben:</span><br>
		<?php ShowArray($raw_data_array);?>
		<a href="<?php echo $data_array['direkter-link']?>" title="Details der Fortbildung auf FIBS anzeigen" target="_blank">Details der Fortbildung auf FIBS</a>
		</p>
		</div>
		<br>
		<?php } ?>
<div role="form" class="wpcf7" id="wpcf7-f<?php echo $cf7id ?>-p<?php echo $currentid ?>-o1" dir="ltr" lang="de-DE">
<div class="screen-reader-response"></div>
<form name="" action="/test-formular/#wpcf7-f<?php echo $cf7id ?>-p<?php echo $currentid ?>-o1" method="post" class="wpcf7-form" novalidate="novalidate">
<div style="display: none;">
<input name="_wpcf7" value="<?php echo $cf7id ?>" type="hidden">
<input name="_wpcf7_version" value="4.2.1" type="hidden">
<input name="_wpcf7_locale" value="de_DE" type="hidden">
<input name="_wpcf7_unit_tag" value="wpcf7-f<?php echo $cf7id ?>-p<?php echo $currentid ?>-o1" type="hidden">
<input name="_wpnonce" value="<?php echo $wpnonce ?>" type="hidden"> 
<input name="post_type" value="training_session" type="hidden">
<input name="dateformat" value="d.m.y" type="hidden">
<input name="timeformat" value="H:i" type="hidden">
<input name="dateseperator" value="." type="hidden">
<input name="timeseperator" value=":" type="hidden">
</div>
<p><span class="wpcf7-form-control-wrap post_title"><?php if( SearchForText( $data_array['name'] , "..." )){ echo '<span style="color:red;">Achtung der Inhalt wurde gekürzt</span>'; } ?><input name="post_title" value="<?php echo $data_array['name'] ?>" size="70" maxlength="120" class="wpcf7-form-control wpcf7-text wpcf7-validates-as-required" aria-required="true" aria-invalid="false" placeholder="Fortbildungstitel, maximal 120 Zeichen" type="text"></span><br>
<span class="wpcf7-form-control-wrap post_content"><?php if( SearchForText( $data_array['beschreibung'] , "..." )){ echo '<span style="color:red;">Achtung der Inhalt wurde gekürzt</span>'; } ?><textarea name="post_content" cols="40" rows="10" maxlength="750" class="wpcf7-form-control wpcf7-textarea wpcf7-validates-as-required" aria-required="true" aria-invalid="false" placeholder="Fortbildungsbeschreibung, maximal 750 Zeichen"><?php echo $data_array['beschreibung'] ?></textarea></span><br>
<span class="label">Beschreibung der Fortbildung. Diese Beschreibung wird für die Druckversion und den FIBS-Eintrag der Fortbildung verwendet.</span><br>
<span class="wpcf7-form-control-wrap training_tag"><input name="training_tag" value="<?php echo $data_array['stichworte'] ?>" size="70" maxlength="200" class="wpcf7-form-control wpcf7-text wpcf7-validates-as-required" aria-required="true" aria-invalid="false" placeholder="Schlagwörter (z.B. Kletterkurs, Anseiltechnik, Knoten)" type="text"></span> <br></p>
<p>
</p><h3>Termindetails</h3>
<p><span class="wpcf7-form-control-wrap training_day_1"><input name="training_day_1" value="<?php echo $data_array['dauer']['termin-1'] ?>" size="40" class="wpcf7-form-control wpcf7-text wpcf7-validates-as-required" aria-required="true" aria-invalid="false" placeholder="1. Tag Datum" type="text"></span> <span class="label">von</span> <span class="wpcf7-form-control-wrap training_day_1_start"><input name="training_day_1_start" value="<?php echo $data_array['dauer']['termin-1-start'] ?>" size="20" maxlength="5" class="wpcf7-form-control wpcf7-text wpcf7-validates-as-required" aria-required="true" aria-invalid="false" placeholder="Beginn" type="text"></span> <span class="label">bis</span> <span class="wpcf7-form-control-wrap training_day_1_end"><input name="training_day_1_end" value="<?php echo $data_array['dauer']['termin-1-ende'] ?>" size="20" maxlength="5" class="wpcf7-form-control wpcf7-text wpcf7-validates-as-required" aria-required="true" aria-invalid="false" placeholder="Ende" type="text"></span> <span class="label">Uhr</span><br>
<span class="wpcf7-form-control-wrap training_day_2"><input name="training_day_2" value="<?php echo $data_array['dauer']['termin-2'] ?>" size="40" class="wpcf7-form-control wpcf7-text" aria-invalid="false" placeholder="2. Tag Datum" type="text"></span> <span class="label">von</span> <span class="wpcf7-form-control-wrap training_day_2_start"><input name="training_day_2_start" value="<?php echo $data_array['dauer']['termin-2-start'] ?>" size="20" maxlength="5" class="wpcf7-form-control wpcf7-text" aria-invalid="false" placeholder="Beginn" type="text"></span> <span class="label">bis</span> <span class="wpcf7-form-control-wrap training_day_2_end"><input name="training_day_2_end" value="<?php echo $data_array['dauer']['termin-2-ende'] ?>" size="20" maxlength="5" class="wpcf7-form-control wpcf7-text" aria-invalid="false" placeholder="Ende" type="text"></span> <span class="label">Uhr</span>
</p>
<p>
</p><h3>Veranstaltungsort</h3>
<p><span class="wpcf7-form-control-wrap training_loc_name"><input name="training_loc_name" value="<?php echo $data_array['veranstaltungsort']['veranstaltungsort-name'] ?>" size="70" maxlength="50" class="wpcf7-form-control wpcf7-text wpcf7-validates-as-required" aria-required="true" aria-invalid="false" placeholder="Name" type="text"></span><br>
<span class="wpcf7-form-control-wrap training_loc_street"><input name="training_loc_street" value="<?php echo $data_array['veranstaltungsort']['veranstaltungsort-strasse'] ?>" size="70" maxlength="50" class="wpcf7-form-control wpcf7-text wpcf7-validates-as-required" aria-required="true" aria-invalid="false" placeholder="Straße" type="text"></span><br>
<span class="wpcf7-form-control-wrap training_loc_postalcode"><input name="training_loc_postalcode" value="<?php echo $data_array['veranstaltungsort']['veranstaltungsort-plz'] ?>" size="5" maxlength="5" class="wpcf7-form-control wpcf7-text wpcf7-validates-as-required" aria-required="true" aria-invalid="false" placeholder="PLZ" type="text"></span> <span class="wpcf7-form-control-wrap training_loc_city"><input name="training_loc_city" value="<?php echo $data_array['veranstaltungsort']['veranstaltungsort-ort'] ?>" size="57" maxlength="30" class="wpcf7-form-control wpcf7-text wpcf7-validates-as-required" aria-required="true" aria-invalid="false" placeholder="Ort" type="text"></span></p>
<p>
</p><h3>Weitere Details der Veranstaltung</h3>
<p><span class="wpcf7-form-control-wrap training_det_orgname"><input name="training_det_orgname" value="<?php echo $data_array['veranstalter']['veranstalter-name'] ?>" size="70" maxlength="50" class="wpcf7-form-control wpcf7-text wpcf7-validates-as-required" aria-required="true" aria-invalid="false" placeholder="Hauptveranstalter" type="text"></span><br>
<span class="wpcf7-form-control-wrap training_det_fibs_id"><input name="training_det_fibs_id" value="<?php echo $data_array['fibsid'] ?>" size="70" maxlength="50" class="wpcf7-form-control wpcf7-text" aria-invalid="false" placeholder="FIBS ID" type="text"></span><br>
<span class="wpcf7-form-control-wrap training_det_fibs_tag"><input name="training_det_fibs_tag" value="<?php echo $data_array['fibstag'] ?>" size="70" maxlength="50" class="wpcf7-form-control wpcf7-text" aria-invalid="false" placeholder="FIBS Aktenzeichen" type="text"></span><br>
<span class="wpcf7-form-control-wrap training_day_deadline"><input name="training_day_deadline" value="<?php echo $data_array['anmeldeschluss'] ?>" size="15" class="wpcf7-form-control wpcf7-text wpcf7-validates-as-required" aria-required="true" aria-invalid="false" placeholder="Anmeldeschluss" type="text"></span><br>
<span class="wpcf7-form-control-wrap training_det_participants"><input name="training_det_participants" value="<?php echo $data_array['teilnehmerzahl'][0] ?>" class="wpcf7-form-control wpcf7-number wpcf7-validates-as-required wpcf7-validates-as-number" min="1" max="999" aria-required="true" aria-invalid="false" placeholder="Max. Teilnehmerzahl" type="number"></span><br>
<span class="wpcf7-form-control-wrap training_det_fees"><input name="training_det_fees" value="" size="15" maxlength="6" class="wpcf7-form-control wpcf7-text wpcf7-validates-as-required" aria-required="true" aria-invalid="false" placeholder="Kosten" type="text"></span> <span class="label">EUR</span><br><br>
<strong>Schulart(en)</strong><br>
<span class="wpcf7-form-control-wrap schularten"><span class="wpcf7-form-control wpcf7-checkbox wpcf7-validates-as-required"><span class="wpcf7-list-item first"><label>
<input type="checkbox" name="training_det_schooltype[]" value="Grundschule" <?php if($data_array['schulart']['0']){echo " CHECKED";}?> />&nbsp;<span class="wpcf7-list-item-label">Grundschule</span></label></span><span class="wpcf7-list-item"><label>
<input type="checkbox" name="training_det_schooltype[]" value="Mittelschule" <?php if($data_array['schulart']['1']){echo " CHECKED";}?> />&nbsp;<span class="wpcf7-list-item-label">Mittelschule</span></label></span><span class="wpcf7-list-item"><label>
<input type="checkbox" name="training_det_schooltype[]" value="Realschule" <?php if($data_array['schulart']['2']){echo " CHECKED";}?> />&nbsp;<span class="wpcf7-list-item-label">Realschule</span></label></span><span class="wpcf7-list-item"><label>
<input type="checkbox" name="training_det_schooltype[]" value="Gymnasium" <?php if($data_array['schulart']['3']){echo " CHECKED";}?> />&nbsp;<span class="wpcf7-list-item-label">Gymnasium</span></label></span><span class="wpcf7-list-item"><label>
<input type="checkbox" name="training_det_schooltype[]" value="Fach- und Berufsoberschulen" <?php if($data_array['schulart']['4']){echo " CHECKED";}?> />&nbsp;<span class="wpcf7-list-item-label">Fach- und Berufsoberschulen</span></label></span><span class="wpcf7-list-item"><label>
<input type="checkbox" name="training_det_schooltype[]" value="Berufsschule" <?php if($data_array['schulart']['5']){echo " CHECKED";}?> />&nbsp;<span class="wpcf7-list-item-label">Berufsschule</span></label></span><span class="wpcf7-list-item"><label>
<input type="checkbox" name="training_det_schooltype[]" value="Förderschule" <?php if($data_array['schulart']['6']){echo " CHECKED";}?> />&nbsp;<span class="wpcf7-list-item-label">Förderschule</span></label></span></span></span><br><br />
<strong>Zielgruppe(n)</strong><br>
<span class="wpcf7-form-control-wrap zielgruppe"><span class="wpcf7-form-control wpcf7-checkbox wpcf7-validates-as-required"><span class="wpcf7-list-item first"><label>
<input type="checkbox" name="training_det_audience[]" value="Lehrer" <?php if($data_array['zielgruppe']['0']){echo " CHECKED";}?> />&nbsp;<span class="wpcf7-list-item-label">Lehrer</span></label></span><span class="wpcf7-list-item"><label>
<input type="checkbox" name="training_det_audience[]" value="Förderlehrer" <?php if($data_array['zielgruppe']['1']){echo " CHECKED";}?> />&nbsp;<span class="wpcf7-list-item-label">Förderlehrer</span></label></span><span class="wpcf7-list-item"><label>
<input type="checkbox" name="training_det_audience[]" value="Fachlehrer" <?php if($data_array['zielgruppe']['2']){echo " CHECKED";}?> />&nbsp;<span class="wpcf7-list-item-label">Fachlehrer</span></label></span><span class="wpcf7-list-item"><label>
<input type="checkbox" name="training_det_audience[]" value="Religionslehrkräfte" <?php if($data_array['zielgruppe']['3']){echo " CHECKED";}?> />&nbsp;<span class="wpcf7-list-item-label">Religionslehrkräfte</span></label></span><span class="wpcf7-list-item"><label>
<input type="checkbox" name="training_det_audience[]" value="Führungspersonal" <?php if($data_array['zielgruppe']['4']){echo " CHECKED";}?> />&nbsp;<span class="wpcf7-list-item-label">Führungspersonal</span></label></span><span class="wpcf7-list-item"><label>
<input type="checkbox" name="training_det_audience[]" value="Seminarlehrer" <?php if($data_array['zielgruppe']['5']){echo " CHECKED";}?> />&nbsp;<span class="wpcf7-list-item-label">Seminarlehrer</span></label></span><span class="wpcf7-list-item"><label>
<input type="checkbox" name="training_det_audience[]" value="Beratungslehrer" <?php if($data_array['zielgruppe']['6']){echo " CHECKED";}?>/>&nbsp;<span class="wpcf7-list-item-label">Beratungslehrer</span></label></span><span class="wpcf7-list-item"><label>
<input type="checkbox" name="training_det_audience[]" value="Verbindungslehrer" <?php if($data_array['zielgruppe']['7']){echo " CHECKED";}?> />&nbsp;<span class="wpcf7-list-item-label">Verbindungslehrer</span></label></span><span class="wpcf7-list-item"><label>
<input type="checkbox" name="training_det_audience[]" value="Fachbetreuer" <?php if($data_array['zielgruppe']['8']){echo " CHECKED";}?> />&nbsp;<span class="wpcf7-list-item-label">Fachbetreuer</span></label></span><span class="wpcf7-list-item"><label>
<input type="checkbox" name="training_det_audience[]" value="IT-Systembetreuer" <?php if($data_array['zielgruppe']['9']){echo " CHECKED";}?> />&nbsp;<span class="wpcf7-list-item-label">IT-Systembetreuer</span></label></span><span class="wpcf7-list-item"><label>
<input type="checkbox" name="training_det_audience[]" value="Fachberater" <?php if($data_array['zielgruppe']['10']){echo " CHECKED";}?> />&nbsp;<span class="wpcf7-list-item-label">Fachberater</span></label></span><span class="wpcf7-list-item"><label>
<input type="checkbox" name="training_det_audience[]" value="Fachmitarbeiter" <?php if($data_array['zielgruppe']['11']){echo " CHECKED";}?> />&nbsp;<span class="wpcf7-list-item-label">Fachmitarbeiter</span></label></span><span class="wpcf7-list-item"><label>
<input type="checkbox" name="training_det_audience[]" value="Medienpäd.-informationstechn. Berater" <?php if($data_array['zielgruppe']['12']){echo " CHECKED";}?> />&nbsp;<span class="wpcf7-list-item-label">Medienpäd.-informationstechn. Berater</span></label></span><span class="wpcf7-list-item"><label>
<input type="checkbox" name="training_det_audience[]" value="Heilpädagogen im Sonderschuldienst" <?php if($data_array['zielgruppe']['13']){echo " CHECKED";}?> />&nbsp;<span class="wpcf7-list-item-label">Heilpädagogen im Sonderschuldienst</span></label></span><span class="wpcf7-list-item"><label>
<input type="checkbox" name="training_det_audience[]" value="Schulpsychologen" <?php if($data_array['zielgruppe']['14']){echo " CHECKED";}?> />&nbsp;<span class="wpcf7-list-item-label">Schulpsychologen</span></label></span><span class="wpcf7-list-item"><label>
<input type="checkbox" name="training_det_audience[]" value="Verwaltungsangestellte" <?php if($data_array['zielgruppe']['15']){echo " CHECKED";}?> />&nbsp;<span class="wpcf7-list-item-label">Verwaltungsangestellte</span></label></span></span></span> <br><br />
<strong>Unterrichtsfach oder Kategorie:</strong><br>
<span class="wpcf7-form-control-wrap training_category"><select name="training_category" class="wpcf7-form-control wpcf7-select wpcf7-validates-as-required" aria-required="true" aria-invalid="false"><option value="">---</option><option value="Ästhetische Bildung">Ästhetische Bildung</option><option value="Berufsspädagogik Technik">Berufsspädagogik Technik</option><option value="Biologie">Biologie</option><option value="Chemie">Chemie</option><option value="Darstellendes Spiel">Darstellendes Spiel</option><option value="Deutsch">Deutsch</option><option value="Didaktik des Deutschen als Zweitsprache">Didaktik des Deutschen als Zweitsprache</option><option value="Englisch">Englisch</option><option value="Evangelische Religionslehre">Evangelische Religionslehre</option><option value="Französisch">Französisch</option><option value="Geographie">Geographie</option><option value="Geschichte">Geschichte</option><option value="Gesellschaftliche Themen">Gesellschaftliche Themen</option><option value="Gesprächsführung und Beratung">Gesprächsführung und Beratung</option><option value="Griechisch">Griechisch</option><option value="Informatik">Informatik</option><option value="Interkulturell">Interkulturell</option><option value="Islamisch Religionslehre">Islamisch Religionslehre</option><option value="Italienisch">Italienisch</option><option value="Katholische Religionslehre">Katholische Religionslehre</option><option value="Kunst">Kunst</option><option value="Latein">Latein</option><option value="Mathematik">Mathematik</option><option value="Medienpädagogik">Medienpädagogik</option><option value="MINT (Mathematik, Informatik, Natur und Technik)">MINT (Mathematik, Informatik, Natur und Technik)</option><option value="Musik">Musik</option><option value="Netzwerken und Zusammenarbeit">Netzwerken und Zusammenarbeit</option><option value="Neue Medien">Neue Medien</option><option value="Pädagogik">Pädagogik</option><option value="Physik">Physik</option><option value="Psychologie">Psychologie</option><option value="Russisch">Russisch</option><option value="Sozialkunde">Sozialkunde</option><option value="Spanisch">Spanisch</option><option value="Sport">Sport</option><option value="Wirtschaftswissenschaften">Wirtschaftswissenschaften</option></select></span> <br></p>
<h4>ReferentIn</h4>
<p><span class="wpcf7-form-control-wrap training_det_referee_label"><select name="training_det_referee_label" class="wpcf7-form-control wpcf7-select wpcf7-validates-as-required" aria-required="true" aria-invalid="false"><option value="">---</option><option value="Referentin">Referentin</option><option value="Referent">Referent</option><option value="Referierende">Referierende</option></select></span><br>
<span class="wpcf7-form-control-wrap training_det_referee"><input name="training_det_referee" value="<?php echo $data_array['veranstalter']['leitung'] ?>" size="50" maxlength="200" class="wpcf7-form-control wpcf7-text wpcf7-validates-as-required" aria-required="true" aria-invalid="false" placeholder="Referentin, Referent oder Referierende (mit Komma getrennt)" type="text"></span>
</p>
<h4>AnsprechpartnerIn</h4>
<p><span class="wpcf7-form-control-wrap training_con_name"><input name="training_con_name" value="<?php echo $data_array['veranstalter']['ansprechpartner'] ?>" size="50" maxlength="50" class="wpcf7-form-control wpcf7-text wpcf7-validates-as-required" aria-required="true" aria-invalid="false" placeholder="Name" type="text"></span><br>
<span class="wpcf7-form-control-wrap training_con_phone"><input name="training_con_phone" value="<?php echo $data_array['veranstalter']['ansprechpartner-telefon'] ?>" size="50" minlength="10" class="wpcf7-form-control wpcf7-text wpcf7-tel wpcf7-validates-as-required wpcf7-validates-as-tel" aria-required="true" aria-invalid="false" placeholder="Telefon (Format: 09131 85-22124)" type="tel"></span><br>
<span class="wpcf7-form-control-wrap training_con_email"><input name="training_con_email" value="<?php echo $data_array['veranstalter']['ansprechpartner-email'] ?>" size="50" class="wpcf7-form-control wpcf7-text wpcf7-email wpcf7-validates-as-required wpcf7-validates-as-email" aria-required="true" aria-invalid="false" placeholder="E-Mail" type="email"></span></p>
<p><br></p>
<p><strong>Ich habe die <a href="https://www.zfl.fau.de/datenschutz/#fortbildungen" target="_blank">Bedingungen für das Eintragen von Fortbildungen</a> gelesen und akzeptiere diese.</strong></p>
<p>
</p><h3>BearbeiterIn</h3>
<p><span class="wpcf7-form-control-wrap training_det_editor_email"><input name="training_det_editor_email" value="" size="40" class="wpcf7-form-control wpcf7-text wpcf7-email wpcf7-validates-as-required wpcf7-validates-as-email" aria-required="true" aria-invalid="false" placeholder="E-Mail-Adresse" type="email"></span><br>
<span class="label">An diese E-Mail-Adresse wird eine Bestätigung der Eintragung gesendet.</span>
</p>
<p><input value="Fortbildung eintragen" class="wpcf7-form-control wpcf7-submit" type="submit"></p>
<div class="wpcf7-response-output wpcf7-display-none"></div></form></div>
		
		<?php } else { ?> 
		Bitte geben Sie einen Link zu einer Detailseite in <a href="https://fibs.alp.dillingen.de/" title="FIBS öffnen" target="_blank">FIBS</a> oder eine Lehrgangs-ID ein und drücken Sie auf „Daten übernehmen“. Das Formular wird dann für Sie erstellt und vorausgefüllt. <?php } ?>
		<?php
}

add_shortcode('fibsimport', 'zfl_fibs_code');

function innerXML($node){
	$doc  = $node->ownerDocument;
	$frag = $doc->createDocumentFragment();
	foreach ($node->childNodes as $child)
	{
		$frag->appendChild($child->cloneNode(TRUE));
	}
	return $doc->saveXML($frag);
}

function CleanTheText($content)
{									
	$content = sanitize_text_field( $content );							
	$content = str_replace("\n ","\n",$content);
	$content = str_replace("\r ","\n",$content);
	$content = str_replace("\r\n ","\n",$content);
	$content = str_replace(" .",". ",$content); 		// Leerstellen vor Punkte raus
	$content = str_replace("  "," ",$content); 			// Entferne doppelte Leerzeichen
	$content = str_replace(" - "," – ",$content); 		// Setze Gedankenstriche
	//$content = preg_replace("#<[^>]+>#", '', $content); // Entferne HTML Tags
	$content = str_replace(" =&gt; Karte","",$content); // Entferne Kartenpfeil
	$content = str_replace("# ","#",$content);
	$content = str_replace(" #","#",$content);
	$content = str_replace("&#13; ","\n",$content);
	$content = str_replace("&#13;","\n",$content);
	$content = str_replace("&#; ","",$content);
	return $content;
}

function FauTextFilter($content){
	$content = str_replace("http:  ","http://",$content);
	$content = str_replace("https:  ","http://",$content);
	$content = str_replace("Universität Erlangen-Nürnberg (FAU)","",$content);
	$content = str_replace("Friedrich-Alexander","",$content);
	$content = str_replace("Universität Erlangen-Nürnberg","",$content);
	$content = str_replace("Straße","Str.",$content);
	return $content;
}

function PhoneFilter($content){
	$content = str_replace("/"," ",$content); // Mache tefonnummer nach DIN5008
	$content = str_replace("(","",$content);
	$content = str_replace(")"," ",$content);
	$content = str_replace("  "," ",$content);
	$content = str_replace("0911 5302","0911 5302-",$content); // Mache durchwahl Nürnberg
	$content = str_replace("09131 85","09131 85-",$content); // Mache durchwahl Erlangen
return $content;	
}

function CutTextToLenght( $content , $lenght ) {
	if(strlen($content) > $lenght) {
			$content = substr($content,0,$lenght).'...';
	}
return $content;
}

function SearchForText( $string , $search ){
	if (strpos($string, $search) !== false) {
    return true;
    }
return false;
}

function ShowArray($data_array){
foreach ($data_array as $key => $value){
		 if(is_array($value)){
			foreach ($value as $key => $value){
			 echo "<strong>";
			 echo $key;
			  echo "</strong>: ";
			 echo $value;
			 echo "<br>";
		 	}
		 } else { 
		 echo "<strong>";
		 echo $key;
		 echo "</strong>: ";
		 echo $value;
	  	 echo "<br>";
	  	 }
}
}

function create_get_url($data_array){
	$url = '?';
	$url .= 'post_title=' . urlencode ( $data_array['name']) . '&';
	$url .= 'post_content=' . urlencode ($data_array['beschreibung']) . '&';
	$url .= 'training_tag=' . $data_array['stichworte'] . '&';
	$url .= 'training_day_1=' . $data_array['dauer']['termin-1'] . '&';
	$url .= 'training_day_1_start=' . $data_array['dauer']['termin-1-start'] . '&';
	$url .= 'training_day_1_end=' . $data_array['dauer']['termin-1-ende'] . '&';
	$url .= 'training_day_2=' . $data_array['dauer']['termin-2'] . '&';
	$url .= 'training_day_2_start=' . $data_array['dauer']['termin-2-start'] . '&';
	$url .= 'training_day_2_end=' . $data_array['dauer']['termin-2-ende'] . '&';
	$url .= 'training_loc_name=' . $data_array['veranstaltungsort']['veranstaltungsort-name'] . '&';
	$url .= 'training_loc_street=' . $data_array['veranstaltungsort']['veranstaltungsort-strasse'] . '&';
	$url .= 'training_loc_postalcode=' . $data_array['veranstaltungsort']['veranstaltungsort-plz'] . '&';
	$url .= 'training_loc_city=' . $data_array['veranstaltungsort']['veranstaltungsort-ort'] . '&';
	$url .= 'training_det_orgname=' . $data_array['veranstalter']['veranstalter-name'] . '&';
	$url .= 'training_det_fibs_id=' . $data_array['fibsid'] . '&';
	$url .= 'training_det_fibs_tag=' . $data_array['fibstag'] . '&';
	$url .= 'training_day_deadline=' . $data_array['anmeldeschluss'] . '&';
	$url .= 'training_det_participants=' . $data_array['maximale-teilnehmerzahl'] . '&';
	$url .= 'training_det_schooltype=' . implode(',',$data_array['schularten']). '&';
	$url .= 'training_det_audience=' . implode(',',$data_array['zielgruppe']);
return $url;
}


function OneDimArrayToTwoDimArray($content){
$new_content_array =  array();
for ( $keypointer = 0; $keypointer < count($content) ; $keypointer++) {
										$pos_content = $keypointer + 1;
										
										$key_value = $content[$keypointer];
										$new_content = $content[$pos_content];
										
										if( $keypointer % 2 == 0){
										$new_content_array[$key_value]= $new_content;
										}															
									}
return $new_content_array;
}
