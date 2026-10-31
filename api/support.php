<?php
require_once __DIR__.'/../_private/config/config.php';
require_once ROOT_PATH.'/_private/lib/security.php';

$u=require_auth();

function support_json_input(): array {
    $raw=file_get_contents('php://input');
    if($raw===false || trim($raw)==='') return [];
    $d=json_decode($raw,true);
    return is_array($d)?$d:[];
}
function ensure_support_tables(): void {
    global $pdo;
    $pdo->exec("CREATE TABLE IF NOT EXISTS support_threads (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      user_id BIGINT UNSIGNED NOT NULL,
      subject VARCHAR(180) NOT NULL DEFAULT 'Support',
      status ENUM('open','closed') NOT NULL DEFAULT 'open',
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      closed_at TIMESTAMP NULL,
      PRIMARY KEY(id),
      KEY idx_support_threads_user(user_id,updated_at),
      KEY idx_support_threads_status(status,updated_at),
      CONSTRAINT fk_support_threads_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS support_messages (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      thread_id BIGINT UNSIGNED NOT NULL,
      user_id BIGINT UNSIGNED NULL,
      author_role ENUM('user','admin') NOT NULL,
      content TEXT NOT NULL,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY(id),
      KEY idx_support_messages_thread(thread_id,created_at),
      CONSTRAINT fk_support_messages_thread FOREIGN KEY(thread_id) REFERENCES support_threads(id) ON DELETE CASCADE,
      CONSTRAINT fk_support_messages_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

ensure_support_tables();
$method=$_SERVER['REQUEST_METHOD']??'GET';
$action=trim((string)($_GET['action']??$_POST['action']??''));

if($method==='GET'){
    if($action==='' || $action==='threads'){
        $st=$pdo->prepare("SELECT t.id,t.subject,t.status,t.created_at,t.updated_at,
          (SELECT COUNT(*) FROM support_messages m WHERE m.thread_id=t.id) AS message_count,
          (SELECT m.id FROM support_messages m WHERE m.thread_id=t.id ORDER BY m.id DESC LIMIT 1) AS last_message_id,
          (SELECT m.author_role FROM support_messages m WHERE m.thread_id=t.id ORDER BY m.id DESC LIMIT 1) AS last_author_role
          FROM support_threads t WHERE t.user_id=? ORDER BY t.updated_at DESC LIMIT 50");
        $st->execute([(int)$u['id']]);
        json_out(['ok'=>true,'threads'=>$st->fetchAll()]);
    }

    if($action==='thread'){
        $id=(int)($_GET['id']??0);
        if($id<1) json_out(['error'=>'Invalid thread id'],422);
        $st=$pdo->prepare("SELECT id,subject,status,created_at,updated_at,closed_at FROM support_threads WHERE id=? AND user_id=? LIMIT 1");
        $st->execute([$id,(int)$u['id']]);
        $thread=$st->fetch();
        if(!$thread) json_out(['error'=>'Thread not found'],404);
        $st=$pdo->prepare("SELECT id,author_role,content,created_at FROM support_messages WHERE thread_id=? ORDER BY id ASC LIMIT 300");
        $st->execute([$id]);
        json_out(['ok'=>true,'thread'=>$thread,'messages'=>$st->fetchAll()]);
    }
    json_out(['error'=>'Unknown support action'],404);
}

if($method==='POST'){
    require_csrf();
    rate_limit('support_write',30,60);
    $in=support_json_input();
    if(!$in && !empty($_POST)) $in=$_POST;

    if($action==='create'){
        $subject=trim((string)($in['subject']??''));
        $message=trim((string)($in['message']??''));
        if($message==='') json_out(['error'=>'Message is required'],422);
        if($subject==='') $subject='Support';
        $subject=mb_substr($subject,0,180);
        $message=mb_substr($message,0,5000);

        $st=$pdo->prepare("SELECT id FROM support_threads WHERE user_id=? AND status='open' ORDER BY id DESC LIMIT 1");
        $st->execute([(int)$u['id']]);
        $existing=$st->fetchColumn();
        if($existing){
            $threadId=(int)$existing;
        }else{
            $st=$pdo->prepare("INSERT INTO support_threads(user_id,subject,status) VALUES(?,?,'open')");
            $st->execute([(int)$u['id'],$subject]);
            $threadId=(int)$pdo->lastInsertId();
        }
        $st=$pdo->prepare("INSERT INTO support_messages(thread_id,user_id,author_role,content) VALUES(?,?, 'user',?)");
        $st->execute([$threadId,(int)$u['id'],$message]);
        $pdo->prepare("UPDATE support_threads SET updated_at=CURRENT_TIMESTAMP,status='open',closed_at=NULL WHERE id=? AND user_id=?")
            ->execute([$threadId,(int)$u['id']]);
        audit('support.user_message',(int)$u['id'],['thread_id'=>$threadId]);
        json_out(['ok'=>true,'thread_id'=>$threadId]);
    }

    if($action==='message'){
        $threadId=(int)($in['thread_id']??0);
        $message=trim((string)($in['message']??''));
        if($threadId<1 || $message==='') json_out(['error'=>'Invalid message'],422);
        $message=mb_substr($message,0,5000);
        $st=$pdo->prepare("SELECT id,status FROM support_threads WHERE id=? AND user_id=? LIMIT 1");
        $st->execute([$threadId,(int)$u['id']]);
        $thread=$st->fetch();
        if(!$thread) json_out(['error'=>'Thread not found'],404);
        if($thread['status']!=='open') json_out(['error'=>'Thread is closed'],409);
        $pdo->prepare("INSERT INTO support_messages(thread_id,user_id,author_role,content) VALUES(?,?, 'user',?)")
            ->execute([$threadId,(int)$u['id'],$message]);
        $pdo->prepare("UPDATE support_threads SET updated_at=CURRENT_TIMESTAMP WHERE id=?")->execute([$threadId]);
        audit('support.user_message',(int)$u['id'],['thread_id'=>$threadId]);
        json_out(['ok'=>true]);
    }


    if($action==='reopen'){
        $threadId=(int)($in['thread_id']??0);
        $st=$pdo->prepare("UPDATE support_threads SET status='open',closed_at=NULL,updated_at=CURRENT_TIMESTAMP WHERE id=? AND user_id=?");
        $st->execute([$threadId,(int)$u['id']]);
        if($st->rowCount()<1) json_out(['error'=>'Thread not found'],404);
        audit('support.user_reopen',(int)$u['id'],['thread_id'=>$threadId]);
        json_out(['ok'=>true]);
    }

    if($action==='close'){
        $threadId=(int)($in['thread_id']??0);
        $st=$pdo->prepare("UPDATE support_threads SET status='closed',closed_at=CURRENT_TIMESTAMP WHERE id=? AND user_id=?");
        $st->execute([$threadId,(int)$u['id']]);
        if($st->rowCount()<1) json_out(['error'=>'Thread not found'],404);
        audit('support.user_close',(int)$u['id'],['thread_id'=>$threadId]);
        json_out(['ok'=>true]);
    }

    json_out(['error'=>'Unknown support action'],404);
}
