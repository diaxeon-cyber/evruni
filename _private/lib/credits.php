<?php
declare(strict_types=1);
require_once ROOT_PATH.'/_private/lib/security.php';
require_once ROOT_PATH.'/_private/lib/fx.php';

/*
 * Evruni unified monetary wallet.
 * The live wallet has ONE canonical balance in USD.
 * The profile currency is display/input currency only and is converted to/from USD.
 */
function wallet_currency_for_user(int $uid): string{
    global $pdo;
    $s=$pdo->prepare("SELECT currency FROM users WHERE id=?");
    $s->execute([$uid]);
    $c=strtoupper((string)$s->fetchColumn());
    return in_array($c,['EUR','USD','GBP','RUB'],true)?$c:'USD';
}

function wallet_canonicalize(int $uid): void{
    global $pdo;
    $pdo->beginTransaction();
    try{
        $s=$pdo->prepare("SELECT currency,balance FROM wallet_balances WHERE user_id=? FOR UPDATE");
        $s->execute([$uid]);
        $rows=$s->fetchAll();

        $usd=0.0;
        $hasWallet=false;
        foreach($rows as $r){
            $hasWallet=true;
            $cur=strtoupper((string)$r['currency']);
            $bal=(float)$r['balance'];
            if($cur==='USD') $usd+=$bal;
            else $usd+=fx_convert($bal,$cur,'USD');
        }

        if(!$hasWallet){
            $legacy=$pdo->prepare("SELECT credits FROM users WHERE id=?");
            $legacy->execute([$uid]);
            $legacyBalance=(float)$legacy->fetchColumn();
            if($legacyBalance>0){
                $usd=$legacyBalance;
                $hasWallet=true;
            }
        }

        if($hasWallet){
            $pdo->prepare("DELETE FROM wallet_balances WHERE user_id=? AND currency<>'USD'")->execute([$uid]);
            $pdo->prepare(
                "INSERT INTO wallet_balances(user_id,currency,balance) VALUES(?,?,?)
                 ON DUPLICATE KEY UPDATE balance=VALUES(balance)"
            )->execute([$uid,'USD',round($usd,6)]);
        }
        $pdo->commit();
    }catch(Throwable $e){
        if($pdo->inTransaction())$pdo->rollBack();
        throw $e;
    }
}

function wallet_balance_usd(int $uid): float{
    global $pdo;
    // Fast path: the unified wallet is canonical USD, so avoid a full
    // canonicalization transaction on every balance read.
    $s=$pdo->prepare("SELECT balance FROM wallet_balances WHERE user_id=? AND currency='USD' LIMIT 1");
    $s->execute([$uid]);
    $usd=$s->fetchColumn();
    if($usd!==false) return round((float)$usd,6);
    // One-time fallback for legacy/non-canonical accounts.
    wallet_canonicalize($uid);
    $s=$pdo->prepare("SELECT balance FROM wallet_balances WHERE user_id=? AND currency='USD' LIMIT 1");
    $s->execute([$uid]);
    return round((float)$s->fetchColumn(),6);
}

function wallet_balance_exact(int $uid,string $currency): float{
    $currency=strtoupper($currency);
    $usd=wallet_balance_usd($uid);
    return round($currency==='USD'?$usd:fx_convert($usd,'USD',$currency),2);
}

function wallet_total_converted(int $uid,string $to): float{
    return wallet_balance_exact($uid,$to);
}

function wallet_adjust_exact(int $uid,float $amount,string $currency,string $reason,string $refType='',?int $refId=null):void{
    $currency=strtoupper($currency);
    wallet_add($uid,$amount,$currency,$reason,$refType,$refId);
}

function wallet_add(int $uid,float $amount,string $currency,string $reason,string $refType='',?int $refId=null):void{
    global $pdo;
    $currency=strtoupper($currency);
    $usdAmount=$currency==='USD'?$amount:fx_convert($amount,$currency,'USD');
    if(abs($usdAmount)<0.000001)return;

    wallet_canonicalize($uid);
    $pdo->beginTransaction();
    try{
        $s=$pdo->prepare("SELECT balance FROM wallet_balances WHERE user_id=? AND currency='USD' FOR UPDATE");
        $s->execute([$uid]);
        $bal=(float)$s->fetchColumn();
        $new=round($bal+$usdAmount,6);
        if($new < -0.000001)throw new RuntimeException('Недостаточно средств');

        $pdo->prepare(
            "INSERT INTO wallet_balances(user_id,currency,balance) VALUES(?,?,?)
             ON DUPLICATE KEY UPDATE balance=VALUES(balance)"
        )->execute([$uid,'USD',$new]);

        $pdo->prepare(
            "INSERT INTO wallet_ledger(user_id,currency,amount,balance_after,reason,reference_type,reference_id)
             VALUES(?,?,?,?,?,?,?)"
        )->execute([$uid,'USD',$usdAmount,$new,$reason,$refType?:null,$refId]);

        $pdo->commit();
    }catch(Throwable $e){
        if($pdo->inTransaction())$pdo->rollBack();
        throw $e;
    }
}

function wallet_spend(int $uid,float $usdCost,string $reason,string $refType='',?int $refId=null):void{
    if($usdCost<=0)return;
    global $pdo;
    wallet_canonicalize($uid);
    $pdo->beginTransaction();
    try{
        $s=$pdo->prepare("SELECT balance FROM wallet_balances WHERE user_id=? AND currency='USD' FOR UPDATE");
        $s->execute([$uid]);
        $bal=(float)$s->fetchColumn();
        if($bal+0.000001 < $usdCost)throw new RuntimeException('Недостаточно средств');

        $new=round($bal-$usdCost,6);
        $pdo->prepare("UPDATE wallet_balances SET balance=? WHERE user_id=? AND currency='USD'")->execute([$new,$uid]);

        $pdo->prepare(
            "INSERT INTO wallet_ledger(user_id,currency,amount,balance_after,reason,reference_type,reference_id)
             VALUES(?,?,?,?,?,?,?)"
        )->execute([$uid,'USD',-$usdCost,$new,$reason,$refType?:null,$refId]);

        $pdo->commit();
    }catch(Throwable $e){
        if($pdo->inTransaction())$pdo->rollBack();
        throw $e;
    }
}

// Compatibility wrappers for existing generation/API code.
// These now operate on the single monetary wallet, not a separate credit balance.
function add_credits(int $uid,float $amount,string $reason,string $refType='',?int $refId=null):void{
    wallet_add($uid,$amount,'USD',$reason,$refType,$refId);
}
function spend_credits(int $uid,float $amount,string $reason,string $refType='',?int $refId=null):void{
    wallet_spend($uid,$amount,$reason,$refType,$refId);
}
