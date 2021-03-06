<?php
/**
 * @package Simple Groupware
 * @link http://www.simple-groupware.de
 * @copyright Simple Groupware Solutions Thomas Bley 2002-2012
 * @license GPLv2
 */

class extensions {

static function uninstall($filename) {
  $source = SIMPLE_EXT.basename($filename);
  setup::out(sprintf("{t}Processing %s ...{/t}",basename($source)));

  $tar_object = new Archive_Tar($source);
  $tar_object->setErrorHandling(PEAR_ERROR_PRINT);

  $file_list = $tar_object->ListContent();
  if (!is_array($file_list) or !isset($file_list[0]["filename"])) {
	sys_die("{t}Error{/t}: tar [1] ".$source);
  }
  $base = "../old/".basename($source)."/";
  foreach ($file_list as $file) {
	if (is_file(SIMPLE_EXT.$file["filename"])) {
	  sys_mkdir(dirname($base.$file["filename"]));
	  rename(SIMPLE_EXT.$file["filename"],$base.$file["filename"]);
	  
	  if (basename($file["filename"])=="uninstall.php") {
		setup::out("");
		require($base.$file["filename"]);
	  }
	}
	@rmdir(SIMPLE_EXT.dirname($file["filename"]));
  }
  rename($source,$base.basename($source));
}

static function install($source, $filename) {
	$target = SIMPLE_EXT.substr($filename,0,-3);

	setup::out("{t}Download{/t}: ".$source." ...");
	if ($fz = gzopen($source,"r") and $fp = fopen($target,"w")) {
	  $i = 0;
	  while (!gzeof($fz)) {
		$i++;
		setup::out(".",false);
		if ($i%160==0) setup::out();
		fwrite($fp,gzread($fz, 16384));
	  }
	  gzclose($fz);
	  fclose($fp);
	} else sys_die("{t}Error{/t}: gzopen [2] ".$source);

	setup::out();
	if (!file_exists($target) or filesize($target)==0 or filesize($target)%10240!=0) {
	  sys_die("{t}Error{/t}: file-check [3] Filesize: ".filesize($target)." ".$target);
	}
	setup::out(sprintf("{t}Processing %s ...{/t}",basename($target)));

	$tar_object = new Archive_Tar($target);
	$tar_object->setErrorHandling(PEAR_ERROR_PRINT);
	$tar_object->extract(SIMPLE_EXT);

	$file_list = $tar_object->ListContent();
	if (!is_array($file_list) or !isset($file_list[0]["filename"]) or !is_dir(SIMPLE_EXT.$file_list[0]["filename"])) {
	  sys_die("{t}Error{/t}: tar [4] ".$target);
	}
	self::update_modules_list();

	$ext_folder = db_select_value("simple_sys_tree","id","anchor=@anchor@",array("anchor"=>"extensions"));

	foreach ($file_list as $file) {
	  sys_chmod(SIMPLE_EXT.$file["filename"]);
	  setup::out(sprintf("{t}Processing %s ...{/t}",SIMPLE_EXT.$file["filename"]));
	  
	  if (basename($file["filename"])=="install.php") {
		setup::out("");
		require(SIMPLE_EXT.$file["filename"]);
		setup::out("");
	  }
	  if (basename($file["filename"])=="readme.txt") {
		$data = file_get_contents(SIMPLE_EXT.$file["filename"]);
		setup::out(nl2br("\n".q($data)."\n"));
	  }
	  if (!empty($ext_folder) and basename($file["filename"])=="folders.xml") {
		setup::out(sprintf("{t}Processing %s ...{/t}","folder structure"));
		folders::create_default_folders(SIMPLE_EXT.$file["filename"],$ext_folder,false);
} } }
  
static function update_modules_list() {
  setup::out(sprintf("<br>{t}Processing %s ...{/t}",SIMPLE_EXT."*/modules.txt"));
  $data = "";
  foreach (scandir(SIMPLE_EXT) as $file) {
	if ($file[0]=="." or !is_dir(SIMPLE_EXT.$file)) continue;
	if (!file_exists(SIMPLE_EXT.$file."/modules.txt")) continue;
	$data .= trim(file_get_contents(SIMPLE_EXT.$file."/modules.txt"))."\n";
  }
  $target = SIMPLE_EXT."modules/schema/modules_ext.txt";
  @unlink($target);
  if ($data!="") {
	sys_mkdir(dirname($target)); 
	file_put_contents($target, $data, LOCK_EX);
  }
  setup::out(sprintf("{t}Processing %s ...{/t}",SIMPLE_EXT."*/sys_modules.txt"));
  $data = "";
  foreach (scandir(SIMPLE_EXT) as $file) {
	if ($file[0]=="." or !is_dir(SIMPLE_EXT.$file)) continue;
	if (!file_exists(SIMPLE_EXT.$file."/sys_modules.txt")) continue;
	$data .= trim(file_get_contents(SIMPLE_EXT.$file."/sys_modules.txt"))."\n";
  }
  $target = SIMPLE_EXT."modules/schema_sys/modules_ext.txt";
  @unlink($target);
  if ($data!="") {
	sys_mkdir(dirname($target));
	file_put_contents($target, $data, LOCK_EX);
  }
}

static function showlist() {
  setup::out("
	<div style='color:#ff0000;'>
	<b>{t}Warning{/t}</b>:<br>
	- Please make a complete backup of your database (e.g. using phpMyAdmin)<br>
	- Please make a complete backup of your sgs folder (e.g. /var/www/htdocs/sgs/)<br>
	- Make sure both backups are complete!
    </div>
  ");
  setup::out("{t}Downloading extension list{/t} ...<br>");

  $url = "http://sourceforge.net/projects/simplgroup/files/simplegroupware_modules/modules.xml";
  if (!($data = sys_cache_get("modules.xml"))) {
	$data = @file_get_contents($url);
	sys_cache_set("modules.xml", $data, 3600);
  }
  if (($xml = @simplexml_load_string($data))) {
	foreach ($xml as $package) {
	  $php_version = (string)$package->php_version;
	  $sgs_version = (string)$package->require_version;

	  $target = SIMPLE_EXT.substr(basename($package->filename),0,-3);
	  if (file_exists($target)) continue;
	  $id = md5($package->filename);
	  
	  if (version_compare(PHP_VERSION, $php_version, "<")) {
		setup::out(sprintf("{t}Setup needs php with at least version %s !{/t} ", $php_version), false);
	  } else if (version_compare(CORE_VERSION_STRING, $sgs_version, "<")) {
		setup::out(sprintf("{t}Setup needs Simple Groupware with at least version %s !{/t} ", $sgs_version), false);
	  } else {
		setup::out("<a href='extensions.php?token=".modify::get_form_token()."&extension=".$package->name."&filename=".$package->filename."'>{t}I n s t a l l{/t}</a> ", false);
	  }
	  setup::out($package->title." <a href='#' onclick='return showhide(\"".$id."\")'>{t}Info{/t}</a>", false);
	  setup::out("<br><div class='description' style='display:none;' id='".$id."'>".nl2br(trim($package->description))."</div>");
	}
  } else {
	setup::out(sprintf("{t}Connection error: %s [%s]{/t}", $url, "HTTP")."<br>".strip_tags($data, "<br><p><h1><center>"));
  }
  
  setup::out("{t}Package from local file system (.tar.gz){/t}:<br/>{t}current path{/t}: ".str_replace("\\","/",getcwd())."/<br/>");

  $dir = opendir("./");
  while (($file=readdir($dir))) {
    if ($file!="." and $file!=".." and preg_match("|^SimpleGroupware\_.*?.tar\.gz\$|i",$file)) {
	  setup::out("<a href='extensions.php?token=".modify::get_form_token()."&cfile=".$file."'>{t}I n s t a l l{/t}</a>&nbsp; ".$file."<br/>");
	}
  }
  closedir($dir);
  setup::out("<form method='POST'><input type='hidden' name='token' value='".modify::get_form_token()."'><input type='text' name='cfile' value='/tmp/SimpleGroupware_SomeExtension_0.x.tar.gz' style='width:300px;'>&nbsp;<input type='submit' class='submit' value='{t}I n s t a l l{/t}'><br>");
  
  $can_uninstall = false;
  foreach (scandir(SIMPLE_EXT) as $file) {
    if ($file[0]=="." or !is_dir(SIMPLE_EXT.$file) or !file_exists(SIMPLE_EXT.$file."/package.xml")) continue;

	$package = simplexml_load_file(SIMPLE_EXT.$file."/package.xml");
	$id = md5($package->filename);

	setup::out("<a onclick='if (!confirm(\"{t}Really uninstall the module ?{/t}\")) return false;' href='extensions.php?token=".modify::get_form_token()."&uninstall=".$package->filename."'>{t}U n i n s t a l l{/t}</a> ".$package->title, false);
	setup::out(" <a href='#' onclick='return showhide(\"".$id."\")'>{t}Info{/t}</a>", false);
	setup::out(" ({t}installed{/t} ".sys_date("{t}m/d/Y{/t}", filemtime(SIMPLE_EXT.$file)).")");
	setup::out("<div class='description' style='display:none;' id='".$id."'>".nl2br(trim($package->description))."</div>");
	$can_uninstall = true;
  }
  if ($can_uninstall) setup::out("<b>{t}Note{/t}:</b> {t}Uninstall does not delete any data in the database.{/t}<br>");
  setup::out_exit('<div style="border-top: 1px solid black;">Powered by Simple Groupware, Copyright (C) 2002-2012 by Thomas Bley.</div></div></body></html>');
}

static function header() {
  setup::out('
	<html><head>
	<title>Simple Groupware</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<style>
	  body, h2, img, div, a {
		background-color: #FFFFFF; color: #666666; font-size: 13px; font-family: Arial, Helvetica, Verdana, sans-serif;
	  }
	  a, input { color: #0000FF; }
	  .border { border-bottom: 1px solid black; }
	  .headline {
		letter-spacing: 2px;
		font-size: 18px;
		font-weight: bold;
	  }
	  .description {
		background-color:#EFEFEF;
		padding:10px;
	  }
	  input {
		font-size: 11px; background-color: #F5F5F5; border: 1px solid #AAAAAA; height: 18px;
		vertical-align: middle; padding-left: 5px; padding-right: 5px; border-radius: 10px;
	  }
	  .submit { color: #0000FF; background-color: #FFFFFF; width: 125px; font-weight: bold; }
	</style>
	<script>
	function showhide(obj) {
	  obj = document.getElementById(obj);
	  if (obj.style.display=="none") {
		obj.style.display="";
	  } else {
		obj.style.display="none";
	  }
	  return false;
	}
	</script>
	</head>
	<body>
	<div class="border headline">Simple Groupware '.CORE_VERSION_STRING.' {t}Extensions{/t}</div>
  ');
  setup::out("<a href='index.php'>{t}Back{/t}</a><br>");
}

static function footer() {
  setup::out("<br><a href='extensions.php'>{t}C O N T I N U E{/t}</a>");
  setup::out('<br><div style="border-top: 1px solid black;">Powered by Simple Groupware, Copyright (C) 2002-2012 by Thomas Bley.</div></div></body></html>');
}
}