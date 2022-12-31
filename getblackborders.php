<?php
/****************************************************/
/*       v1.0 - 30/12/2022                          */
/*  détermine bandes noires à partir de :           */
/*  - $_GET['com'] : répertoire de ffmpeg.exe       */
/*  - $_GET['rep'] : répertoire de la vidéo         */
/*  - $_GET['fi1'] : nom du fichier vidéo           */
/*  - $_GET['func'] : fonction de rappel javascript */ 
/*  Création répertoire temporaire puis libération  */
/*  Appel et retour par injection de script         */
/****************************************************/

	ini_set("default_charset", 'utf-8');
	ini_set("display_errors", 1);
	ini_set("error_reporting", E_ALL | E_STRICT);
	date_default_timezone_set('Europe/Paris');  
	set_time_limit(0);

/*    fonction pour debugging             */
function file_ecrit($filename,$data)
  {                                  // pour gestion des erreurs ET sauvegarde de compte.txt (ceinture ET bretelles)
    if($fp = fopen($filename,'a'))   // mode ajout !!
    {
      $ok = fwrite($fp,$data);
      fclose($fp);
      return $ok;
    }
    else return false;
  }      

/*   conversion secondes au format hh:mm:ss.mmm  */
function convertTo($input)
{
    $input  = number_format($input, 3, '.','');
    $secs  = floor($input);
    $milli = (int) (($input - $secs) * 1000);
    $milli = str_pad($milli, 3, '0', STR_PAD_LEFT);
    $hours   = floor($secs / 3600);
    $minutes = (($secs / 60) % 60);
    $minutes = str_pad($minutes, 2, '0', STR_PAD_LEFT);
    $seconds = $secs % 60;
    $seconds = str_pad($seconds, 2, '0', STR_PAD_LEFT);
    if ($hours > 1) {
        $hours = str_pad($hours, 2, '0', STR_PAD_LEFT);
    } else {
        $hours = '00';
    }
    $Time = "$hours:$minutes:$seconds.$milli";
    return $Time;
}

  	$now = time();
  	$rnow =  './'.$now;
    @mkdir($rnow);
    $ret_func=$_GET['func'];
    $rep=$_GET['rep'];
    $fic1='"'.$rep.chr(47).$_GET['fi1'].'"';
    file_ecrit('debgetblackborders.php.txt',$fic1."\n");
    $ffprob = '"'.$_GET['com'].chr(47).'ffprobe'.'"';
    $ffmpeg = '"'.$_GET['com'].chr(47).'ffmpeg'.'"';
    $info1 = $ffprob."  -hide_banner -v panic -of default=noprint_wrappers=1 -select_streams v:0 -show_entries stream=duration,height,width -i ".$fic1." >null >".$rnow."/ninfo1.txt";
    $outfile = $rnow."/ninfo1.txt";
    //file_ecrit('debgetblackborders.php.txt',$info1."\n");    // debugging
    $movie_nfo = array('width' => 0, 'height' => 0, 'duration' => 0, 'left' => 0, 'top' => 0, 'right' => 0, 'bottom' => 0);
    system($info1,$ret1);
    //file_ecrit('debgetblackborders.php.txt',$info1."\n");    // debugging

    $contents=file_get_contents($outfile);
    $contents=str_replace(chr(13),'',$contents); // nettoit les éventuels "\r" => transforme \r\n ou \n\r en \n 
		$lines=explode("\n",$contents);
		foreach($lines as $line){
			if (strlen($line) > 2){
				$tmp = explode("=",$line);
				$movie_nfo[$tmp[0]] = $tmp[1];
			}
		}

    //*****   création 3 images ******************//  
    $dureetot = floatval($movie_nfo['duration']);
    $dureetot = floor($dureetot*1000) / 1000;
 		$t1 = floor($dureetot *50) / 1000;
    $t1 = convertTo($t1);
    $t2 = floor($dureetot *100) /1000;
    $t2 = convertTo($t2);
    $t3 = floor($dureetot *200) /1000;
    $t3 = convertTo($t3);

    $creimg1 = $ffmpeg." -i ".$fic1." -an -sn -ss ".$t1." -vframes 1 ".$rnow."/img1.gif";
    //file_ecrit('debgetblackborders.php.txt',$creimg1."\n");
    system($creimg1,$ret1);
    $creimg2 = $ffmpeg." -i ".$fic1." -an -sn -ss ".$t2." -vframes 1 ".$rnow."/img2.gif";
    system($creimg2,$ret1);
    $creimg3 = $ffmpeg." -i ".$fic1." -an -sn -ss ".$t3." -vframes 1 ".$rnow."/img3.gif";
    system($creimg3,$ret1);

    $hor = array();
    $img1 = imagecreatefromgif($rnow.'/img1.gif');
    $larg=imagesx($img1); $haut=imagesy($img1); 
    $img2 = imagecreatefromgif($rnow.'/img2.gif');
    $img3 = imagecreatefromgif($rnow.'/img3.gif');
    for ($x=0; $x<$larg; $x++){
    		$tot=0;
    		for ($y=0; $y<$haut; $y++){
    			$tot +=  abs(imagecolorat($img1, $x, $y) - imagecolorat($img2, $x, $y));
    			 $tot +=  abs(imagecolorat($img1, $x, $y) - imagecolorat($img3, $x, $y));
    			 $tot +=  abs(imagecolorat($img3, $x, $y) - imagecolorat($img2, $x, $y));
    		}
    		$tot = $tot / (3 * $haut);
    		$tott = floor($tot * 10) / 10;
    		array_push($hor,$tott);
    } 

    // détermination du nombres de lignes noires en haut
    $bhaut = 0;
    $x=0;
    while (($hor[$x] < 4) && ($x<$larg)){
    	$bhaut+=1;
    	$x += 1;
    }
    $movie_nfo['top']=$bhaut;

    // détermination du nombres de lignes noires en bas
    $bbas = 0;
    $x=$larg-1;
    while (($hor[$x] < 4) && ($x>-1)){
    	$bbas+=1;
    	$x -= 1;
    }
    $movie_nfo['bottom']=$bbas;

    $hor=array();
    for ($y=0; $y<$haut; $y++){
    		$tot12=0; $tot13=0; $tot23=0; $tot=0;
    		for ($x=0; $x<$larg; $x++){
    			  $tot12 += abs(imagecolorat($img1, $x, $y) - imagecolorat($img2, $x, $y));
    			  $tot13+= abs(imagecolorat($img1, $x, $y) - imagecolorat($img3, $x, $y));
    			  $tot23 += abs(imagecolorat($img3, $x, $y) - imagecolorat($img2, $x, $y));
    			  //$tot = $tot + ($tot12 + $tot13 + $tot23);
    		}
    		$tot12 = $tot12 / $larg;
    		$tot13 = $tot13 / $larg;
    		$tot23 = $tot23 / $larg;
    		$tot = ($tot12 + $tot13 + $tot23) / 3;
    		$tott = floor($tot * 10) / 10;
    		array_push($hor,$tott);
    } 

    // détermination du nombres de colonnes noires à gauche
    $bgauche = 0;
    $x=0;
    while (($hor[$x] < 4) && ($x<$haut)){
    	$bgauche+=1;
    	$x += 1;
    }
    $movie_nfo['left']=$bgauche;

    // détermination du nombres de colonnes noires à droite
    $bdroit = 0;
    $x=$haut-1;
    while (($hor[$x] < 4) && ($x>-1)){
    	$bdroit+=1;
    	$x -= 1;
    }
    $movie_nfo['right']=$bdroit;

    // nettoyage disque dur et mémoire
    @imagedestroy($img1);
    @imagedestroy($img2);
    @imagedestroy($img3);
    $hor=array();
    @unlink($rnow.'/img1.gif');
    @unlink($rnow.'/img2.gif');
    @unlink($rnow.'/img3.gif');
    @unlink($rnow.'/ninfo1.txt');
    @rmdir($rnow);

    $ret=$movie_nfo['width']."|".$movie_nfo['height']."|".$movie_nfo['duration']."|".$movie_nfo['top']."|".$movie_nfo['bottom']."|".$movie_nfo['left']."|".$movie_nfo['right'];
    //file_ecrit('debgetblackborders.php.txt',$ret."\n");  // debugging
    echo "$ret_func('".$ret."');";
?>