<?php
require_once __DIR__.'/../_private/config/config.php';
require_once ROOT_PATH.'/_private/lib/security.php';
$u=require_auth();

function evruni_extract_docx_text(string $path): string {
  if(!class_exists('ZipArchive')) return '';
  $z=new ZipArchive();
  if($z->open($path)!==true) return '';
  $xml=$z->getFromName('word/document.xml');
  $z->close();
  if(!$xml) return '';
  $xml=preg_replace('/<\/w:p>/i',"\n",$xml);
  $xml=preg_replace('/<w:tab[^>]*\/>/i',"\t",$xml);
  $text=strip_tags($xml);
  return trim(html_entity_decode($text,ENT_QUOTES|ENT_XML1,'UTF-8'));
}


function evruni_extract_pdf_text(string $path): string {
  if(!function_exists('shell_exec') || !is_file($path)) return '';
  $bin=trim((string)@shell_exec('command -v pdftotext 2>/dev/null'));
  if($bin==='') return '';
  $tmp=$path.'.txt';
  $cmd=escapeshellcmd($bin).' -enc UTF-8 '.escapeshellarg($path).' '.escapeshellarg($tmp).' 2>/dev/null';
  @shell_exec($cmd);
  $text=is_file($tmp)?(string)@file_get_contents($tmp):'';
  @unlink($tmp);
  return trim($text);
}

if($_SERVER['REQUEST_METHOD']==='GET'){
  $project=(int)($_GET['project_id']??0);
  $id=(int)($_GET['id']??0);
  if($id){
    $s=$pdo->prepare('SELECT id,name,mime_type,size_bytes,created_at,project_id,extracted_text FROM documents WHERE id=? AND user_id=?');
    $s->execute([$id,$u['id']]);$d=$s->fetch();
    if(!$d)json_out(['error'=>'Document not found'],404);
    json_out(['document'=>$d]);
  }
  $sql='SELECT id,name,mime_type,size_bytes,created_at,project_id FROM documents WHERE user_id=?';
  $args=[$u['id']];
  if($project){$sql.=' AND project_id=?';$args[]=$project;}
  $sql.=' ORDER BY id DESC';
  $s=$pdo->prepare($sql);$s->execute($args);json_out(['documents'=>$s->fetchAll()]);
}

require_csrf();
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_FILES['file'])){
  $project=(int)($_POST['project_id']??0);
  if($project){
    $p=$pdo->prepare('SELECT id FROM projects WHERE id=? AND user_id=?');$p->execute([$project,$u['id']]);
    if(!$p->fetchColumn())json_out(['error'=>'Project not found'],404);
  }
  $f=$_FILES['file'];
  if($f['error']!==UPLOAD_ERR_OK)json_out(['error'=>'Upload failed'],422);
  if($f['size']>25*1024*1024)json_out(['error'=>'File too large'],422);
  $fi=new finfo(FILEINFO_MIME_TYPE);$mime=$fi->file($f['tmp_name']);
  $allowed=[
    'text/plain','text/csv','application/json','application/pdf',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
  ];
  if(!in_array($mime,$allowed,true))json_out(['error'=>'Supported: TXT, CSV, JSON, PDF, DOCX'],422);
  $dir=ROOT_PATH.'/_private/storage/docs/'.$u['id'];
  if(!is_dir($dir))mkdir($dir,0700,true);
  $safe=preg_replace('/[^A-Za-z0-9._-]/','_',basename($f['name']));
  $name=bin2hex(random_bytes(10)).'_'.$safe;$path=$dir.'/'.$name;
  if(!move_uploaded_file($f['tmp_name'],$path))json_out(['error'=>'Could not store file'],500);

  $text='';
  if($mime==='text/plain'||$mime==='text/csv'||$mime==='application/json'){
    $text=file_get_contents($path)?:'';
  }elseif($mime==='application/vnd.openxmlformats-officedocument.wordprocessingml.document'){
    $text=evruni_extract_docx_text($path);
  }elseif($mime==='application/pdf'){
    $text=evruni_extract_pdf_text($path);
  }
  if(strlen($text)>120000)$text=substr($text,0,120000);

  $s=$pdo->prepare('INSERT INTO documents(user_id,project_id,name,mime_type,size_bytes,stored_path,extracted_text) VALUES(?,?,?,?,?,?,?)');
  $s->execute([$u['id'],$project?:null,$f['name'],$mime,$f['size'],$path,$text]);
  json_out(['ok'=>true,'id'=>(int)$pdo->lastInsertId(),'name'=>$f['name'],'project_id'=>$project]);
}
if($_SERVER['REQUEST_METHOD']==='DELETE'){
  $id=(int)($_GET['id']??0);
  $s=$pdo->prepare('SELECT stored_path FROM documents WHERE id=? AND user_id=?');$s->execute([$id,$u['id']]);$d=$s->fetch();
  if($d){@unlink($d['stored_path']);$pdo->prepare('DELETE FROM documents WHERE id=? AND user_id=?')->execute([$id,$u['id']]);}
  json_out(['ok'=>true]);
}
json_out(['error'=>'Method not allowed'],405);
