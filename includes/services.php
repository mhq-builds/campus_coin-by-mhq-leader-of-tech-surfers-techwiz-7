<?php
declare(strict_types=1);

function learned_category_suggestion(int $userId, string $description, string $type): ?array
{
    $words = preg_split('/[^a-z0-9]+/i', strtolower($description), -1, PREG_SPLIT_NO_EMPTY);
    $words = array_values(array_filter(array_unique($words), static fn($w) => strlen($w) >= 4));
    foreach ($words as $word) {
        $stmt = db()->prepare(
            'SELECT s.accepted_category_id AS category_id, c.name
             FROM ai_category_suggestions s
             INNER JOIN categories c ON c.category_id=s.accepted_category_id
             WHERE s.user_id=? AND s.was_overridden=1 AND c.type=?
               AND LOWER(s.description_text) LIKE ?
             ORDER BY s.created_at DESC LIMIT 1'
        );
        $stmt->execute([$userId, $type, '%' . $word . '%']);
        $row = $stmt->fetch();
        if ($row) {
            return ['category_id'=>(int)$row['category_id'], 'name'=>$row['name'], 'confidence'=>0.97, 'source'=>'learned'];
        }
    }
    return null;
}

function suggest_category(int $uid, string $text, string $type): ?array
{
    $text = strtolower(trim($text));
    if ($text === '') return null;

    $learned = learned_category_suggestion($uid, $text, $type);
    if ($learned) return $learned;

    $map = $type === 'income'
        ? [
            'Allowance'=>['allowance','pocket money','family','monthly money'],
            'Part-time Job'=>['salary','freelance','part time','part-time','job','gig','upwork'],
            'Scholarship'=>['scholarship','stipend'],
            'Gift'=>['gift','eid money','birthday money'],
        ]
        : [
            'Food'=>['cafe','café','food','burger','pizza','canteen','lunch','dinner','coffee','snack','delivery','restaurant'],
            'Transport'=>['bus','uber','careem','fuel','petrol','rickshaw','metro','ride','transport'],
            'Hostel/Rent'=>['hostel','rent','room','accommodation'],
            'Academics'=>['book','books','tuition','stationery','pen','course','exam','academy','college fee','university fee'],
            'Subscriptions'=>['netflix','spotify','youtube premium','subscription','prime','disney','app'],
            'Entertainment'=>['movie','cinema','outing','game','gaming','concert'],
            'Miscellaneous'=>['misc','other'],
        ];

    foreach ($map as $name=>$words) {
        foreach ($words as $word) {
            if (str_contains($text, $word)) {
                $stmt=db()->prepare('SELECT category_id,name FROM categories WHERE name=? AND type=? AND is_active=1 AND (is_default=1 OR user_id=?) ORDER BY is_default DESC LIMIT 1');
                $stmt->execute([$name,$type,$uid]);
                if ($row=$stmt->fetch()) return ['category_id'=>(int)$row['category_id'],'name'=>$row['name'],'confidence'=>0.94,'source'=>'rule'];
                return null;
            }
        }
    }
    return null;
}

function totals(int $uid,string $start,string $end):array
{
    $s=db()->prepare('SELECT COALESCE(SUM(CASE WHEN type="income" THEN amount ELSE 0 END),0) income,COALESCE(SUM(CASE WHEN type="expense" THEN amount ELSE 0 END),0) expense FROM transactions WHERE user_id=? AND status="active" AND transaction_date BETWEEN ? AND ?');
    $s->execute([$uid,$start,$end]);
    $r=$s->fetch()?:['income'=>0,'expense'=>0];
    return ['income'=>(float)$r['income'],'expense'=>(float)$r['expense'],'balance'=>(float)$r['income']-(float)$r['expense']];
}

function current_insight(int $uid):?array
{
    $m=month_start();$start=$m;$end=date('Y-m-t',strtotime($m));
    $top=db()->prepare('SELECT c.category_id,c.name,SUM(t.amount) spent FROM transactions t JOIN categories c ON c.category_id=t.category_id WHERE t.user_id=? AND t.status="active" AND t.type="expense" AND t.transaction_date BETWEEN ? AND ? GROUP BY c.category_id,c.name ORDER BY spent DESC LIMIT 1');
    $top->execute([$uid,$start,$end]);$r=$top->fetch();if(!$r)return null;
    $avg=db()->prepare('SELECT COALESCE(SUM(amount),0)/3 FROM transactions WHERE user_id=? AND category_id=? AND status="active" AND type="expense" AND transaction_date>=DATE_SUB(?,INTERVAL 3 MONTH) AND transaction_date<?');
    $avg->execute([$uid,$r['category_id'],$start,$start]);$a=(float)$avg->fetchColumn();
    $growth=$a>0?(((float)$r['spent']-$a)/$a)*100:0;
    if($growth>15){$summary=sprintf('%s is your largest expense category this month at %s, about %.0f%% above your recent category average.',$r['name'],money($r['spent']),$growth);$tip=sprintf('Review your %s transactions and set a realistic weekly cap.',strtolower($r['name']));}
    else{$summary=sprintf('Your largest expense category is %s at %s this month.',$r['name'],money($r['spent']));$tip='Compare this month with your budget before adding new recurring commitments.';}
    return ['summary_text'=>$summary,'tip_text'=>$tip,'category_id'=>(int)$r['category_id'],'growth'=>round($growth,2)];
}

function upsert_insight(int $uid):?array
{
    $ins=current_insight($uid);if(!$ins)return null;$m=month_start();
    $q=db()->prepare('SELECT insight_id,version_no,generated_at FROM insights WHERE user_id=? AND month_start=? ORDER BY version_no DESC LIMIT 1');$q->execute([$uid,$m]);$old=$q->fetch();
    if($old && strtotime($old['generated_at']) >= strtotime('-6 hours')) return array_merge($ins,['insight_id'=>(int)$old['insight_id'],'version_no'=>(int)$old['version_no'],'month_start'=>$m]);
    $version=$old?((int)$old['version_no']+1):1;
    $i=db()->prepare('INSERT INTO insights(user_id,month_start,version_no,summary_text,tip_text,flagged_category_id,flagged_growth_percent) VALUES(?,?,?,?,?,?,?)');
    $i->execute([$uid,$m,$version,$ins['summary_text'],$ins['tip_text'],$ins['category_id'],$ins['growth']]);
    return array_merge($ins,['insight_id'=>(int)db()->lastInsertId(),'version_no'=>$version,'month_start'=>$m]);
}

function saving_tips(int $uid):array
{
    $month=month_start();$out=[];
    $s=db()->prepare('SELECT b.category_id,c.name,b.limit_amount,COALESCE(SUM(CASE WHEN t.status="active" THEN t.amount ELSE 0 END),0) spent FROM budgets b JOIN categories c ON c.category_id=b.category_id LEFT JOIN transactions t ON t.user_id=b.user_id AND t.category_id=b.category_id AND t.type="expense" AND t.transaction_date>=b.month_start AND t.transaction_date<DATE_ADD(b.month_start,INTERVAL 1 MONTH) WHERE b.user_id=? AND b.month_start=? AND b.is_active=1 GROUP BY b.category_id,c.name,b.limit_amount ORDER BY spent DESC');
    $s->execute([$uid,$month]);
    foreach($s->fetchAll() as $r){$over=(float)$r['spent']-(float)$r['limit_amount'];if($over>0)$out[]=['title'=>$r['name'].' budget is over','tip_text'=>sprintf('Your %s spending is %s above the monthly budget. Review the largest transactions and set a practical weekly cap.',strtolower($r['name']),money($over)),'potential'=>$over,'impact'=>$over+1000,'category_id'=>(int)$r['category_id']];}
    $s=db()->prepare('SELECT c.category_id,c.name,SUM(t.amount) spent FROM transactions t JOIN categories c ON c.category_id=t.category_id WHERE t.user_id=? AND t.status="active" AND t.type="expense" AND t.transaction_date BETWEEN ? AND ? GROUP BY c.category_id,c.name ORDER BY spent DESC LIMIT 4');
    $s->execute([$uid,$month,date('Y-m-d')]);
    foreach($s->fetchAll() as $r){$p=round((float)$r['spent']*.1,2);$out[]=['title'=>'Optimize '.$r['name'],'tip_text'=>'Try reducing '.strtolower($r['name']).' spending by about 10% and compare the result with your normal pattern.','potential'=>$p,'impact'=>$p,'category_id'=>(int)$r['category_id']];}
    usort($out,fn($a,$b)=>$b['impact']<=>$a['impact']);
    return array_slice($out,0,6);
}

function forecast_next_month(int $uid):array
{
    $s=db()->prepare('SELECT DATE_FORMAT(transaction_date,"%Y-%m-01") m, SUM(CASE WHEN type="income" THEN amount ELSE 0 END) income, SUM(CASE WHEN type="expense" THEN amount ELSE 0 END) expense FROM transactions WHERE user_id=? AND status="active" AND transaction_date>=DATE_SUB(DATE_FORMAT(CURRENT_DATE,"%Y-%m-01"),INTERVAL 3 MONTH) AND transaction_date<DATE_FORMAT(CURRENT_DATE,"%Y-%m-01") GROUP BY m ORDER BY m DESC LIMIT 3');
    $s->execute([$uid]);$rows=$s->fetchAll();if(!$rows)return ['income'=>0,'expense'=>0,'balance'=>0,'confidence'=>0];
    $income=array_sum(array_map(fn($r)=>(float)$r['income'],$rows))/count($rows);$expense=array_sum(array_map(fn($r)=>(float)$r['expense'],$rows))/count($rows);$confidence=min(.95,.5+count($rows)*.15);
    return ['income'=>$income,'expense'=>$expense,'balance'=>$income-$expense,'confidence'=>$confidence];
}

function final_financial_health(int $uid):array
{
    $month=month_start();$today=date('Y-m-d');$tot=totals($uid,$month,$today);
    $budgetScore=70;$b=db()->prepare('SELECT b.limit_amount,COALESCE(SUM(CASE WHEN t.status="active" AND t.type="expense" THEN t.amount ELSE 0 END),0) spent FROM budgets b LEFT JOIN transactions t ON t.user_id=b.user_id AND t.category_id=b.category_id AND t.transaction_date>=b.month_start AND t.transaction_date<DATE_ADD(b.month_start,INTERVAL 1 MONTH) WHERE b.user_id=? AND b.month_start=? AND b.is_active=1 GROUP BY b.budget_id,b.limit_amount');$b->execute([$uid,$month]);$rows=$b->fetchAll();if($rows){$ok=0;foreach($rows as $r){$pct=(float)$r['limit_amount']>0?((float)$r['spent']/(float)$r['limit_amount'])*100:0;if($pct<=80)$ok++;}$budgetScore=(int)round($ok/count($rows)*100);}
    $g=db()->prepare('SELECT COALESCE(monthly_savings_goal,0) FROM users WHERE user_id=?');$g->execute([$uid]);$goal=(float)$g->fetchColumn();$savingScore=$goal>0?(int)min(100,max(0,round($tot['balance']/$goal*100))):($tot['balance']>=0?80:35);
    $h=db()->prepare('SELECT SUM(CASE WHEN type="expense" THEN amount ELSE 0 END) expense FROM transactions WHERE user_id=? AND status="active" AND transaction_date>=DATE_SUB(CURRENT_DATE,INTERVAL 90 DAY) GROUP BY DATE_FORMAT(transaction_date,"%Y-%m")');$h->execute([$uid]);$vals=array_map(fn($r)=>(float)$r['expense'],$h->fetchAll());$stability=75;if(count($vals)>=2){$avg=array_sum($vals)/count($vals);$spread=$avg>0?(max($vals)-min($vals))/$avg:0;$stability=(int)max(20,min(100,100-$spread*70));}
    return ['score'=>(int)round(($budgetScore+$savingScore+$stability)/3),'budget'=>$budgetScore,'saving'=>$savingScore,'stability'=>$stability,'balance'=>$tot['balance']];
}

function duplicate_transactions(int $uid):array
{
    $q=db()->prepare('SELECT a.transaction_id a_id,b.transaction_id b_id,a.transaction_date,a.amount,a.description,c.name category_name FROM transactions a JOIN transactions b ON b.user_id=a.user_id AND b.transaction_id>a.transaction_id AND b.status="active" AND a.status="active" AND b.type=a.type AND b.category_id=a.category_id AND b.amount=a.amount AND ABS(DATEDIFF(b.transaction_date,a.transaction_date))<=1 JOIN categories c ON c.category_id=a.category_id WHERE a.user_id=? ORDER BY a.transaction_date DESC LIMIT 25');$q->execute([$uid]);return $q->fetchAll();
}
