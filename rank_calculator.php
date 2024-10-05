<?php
// ランク定義
$RANKS = [
    1 => [
        'name' => '一兵卒',
        'message' => '地域との関わりを始めたばかりの初心者。これから地域を深く知っていきましょう！あなたの関わりをお待ちしています。'
    ],
    2 => [
        'name' => '百人隊長',
        'message' => '地域の活動に主体的に取り組めます。地域の魅力をどんどん発信していってください。期待しています！'
    ],
    3 => [
        'name' => '千人将',
        'message' => '多くの仲間とともに地域の魅力を発信いただきありがとうございます。地域の良さをもっともっと知っていただき、地域の人との関わりを積極的に取ってください。あなたの行動が地域を変えます！'
    ],
    4 => [
        'name' => '将軍',
        'message' => '地域の未来を一緒に考えていただける存在です。ともに考え、ともに成長していきましょう。もうあなたは地域のリーダーですよ！'
    ],
    5 => [
        'name' => '大将軍',
        'message' => '地域の発展に貢献していただいています。あなたの活動が地域の未来に、そして子どもたちの未来につながります。より良い地域を目指し、ともに次世代に繋げていきましょう。'
    ]
];

function calculateFurusatoIDRank($data) {
    $points = 0;
    $categories = [
        'connection' => 0,
        'contribution' => 0,
        'knowledge' => 0,
        'experience' => 0
    ];

    // 1. 出身地・居住地（Connection）
    if ($data['birthplace'] === 'yes') {
        $points += 10;
        $categories['connection'] += 10;
    }
    switch ($data['place_of_residence']) {
        case 'current':
            $points += 15;
            $categories['connection'] += 15;
            break;
        case 'past':
            $points += 10;
            $categories['connection'] += 10;
            break;
        case 'never':
            // 0点
            break;
    }

    // 2. 旅行経験（Experience）
    if ($data['travel_experience'] === 'yes') {
        $points += 5;
        $categories['experience'] += 5;
        
        switch ($data['visit_frequency']) {
            case 'yearly':
                $points += 15;
                $categories['experience'] += 15;
                break;
            case 'occasionally':
                $points += 10;
                $categories['experience'] += 10;
                break;
            case 'rarely':
                $points += 5;
                $categories['experience'] += 5;
                break;
            case 'never':
                // 0点
                break;
        }
        
        switch ($data['stay_duration']) {
            case 'long_stay':
                $points += 15;
                $categories['experience'] += 15;
                break;
            case 'short_stay':
                $points += 10;
                $categories['experience'] += 10;
                break;
            case 'day_trip':
                $points += 5;
                $categories['experience'] += 5;
                break;
            case 'no_experience':
                // 0点
                break;
        }
    }

    // 3. ボランティア経験（Contribution）
    if ($data['volunteer_experience'] === 'yes') {
        $points += 15;
        $categories['contribution'] += 15;
        $volunteer_activities = json_decode($data['volunteer_activity'], true);
        if (!in_array('no_experience', $volunteer_activities)) {
            $points += count($volunteer_activities) * 2;
            $categories['contribution'] += count($volunteer_activities) * 2;
        }
        
        switch ($data['volunteer_frequency']) {
            case 'regular':
                $points += 10;
                $categories['contribution'] += 10;
                break;
            case 'occasional':
                $points += 5;
                $categories['contribution'] += 5;
                break;
            case 'one_time':
                $points += 2;
                $categories['contribution'] += 2;
                break;
            case 'no_experience':
                // 0点
                break;
        }
    }

    // 4. ふるさと納税（Contribution）
    if ($data['donation_experience'] === 'yes') {
        switch ($data['donation_count']) {
            case '毎年':
                $points += 20;
                $categories['contribution'] += 20;
                break;
            case '4回以上':
                $points += 15;
                $categories['contribution'] += 15;
                break;
            case '1～3回':
                $points += 10;
                $categories['contribution'] += 10;
                break;
            case '0回':
                // 0点
                break;
        }
    }

    // 5. 地域物産品の購入（Knowledge and Contribution）
    if ($data['product_purchase'] === 'yes') {
        switch ($data['purchase_frequency']) {
            case '毎月':
                $points += 20;
                $categories['knowledge'] += 10;
                $categories['contribution'] += 10;
                break;
            case '7回以上':
                $points += 15;
                $categories['knowledge'] += 7;
                $categories['contribution'] += 8;
                break;
            case '4～6回':
                $points += 10;
                $categories['knowledge'] += 5;
                $categories['contribution'] += 5;
                break;
            case '1～3回':
                $points += 5;
                $categories['knowledge'] += 2;
                $categories['contribution'] += 3;
                break;
            case '購入経験なし':
                // 0点
                break;
        }
    }

    // 6. 仕事での地域との関わり（Experience and Contribution）
    if ($data['work_experience'] === 'yes') {
        $points += 10;
        $categories['experience'] += 5;
        $categories['contribution'] += 5;
        $work_types = json_decode($data['work_type'], true);
        if (!in_array('関わりなし', $work_types)) {
            $points += count($work_types) * 2;
            $categories['experience'] += count($work_types);
            $categories['contribution'] += count($work_types);
        }
        
        switch ($data['work_frequency']) {
            case '月に1回以上':
                $points += 15;
                $categories['experience'] += 7;
                $categories['contribution'] += 8;
                break;
            case '年に数回':
                $points += 10;
                $categories['experience'] += 5;
                $categories['contribution'] += 5;
                break;
            case 'ほとんど関わりなし':
                $points += 5;
                $categories['experience'] += 2;
                $categories['contribution'] += 3;
                break;
            case '関わりなし':
                // 0点
                break;
        }
    }

    // ランクの決定
    $rank = 1;  // デフォルトは一兵卒
    if ($points >= 90) {
        $rank = 5;  // 大将軍
    } elseif ($points >= 70) {
        $rank = 4;  // 将軍
    } elseif ($points >= 50) {
        $rank = 3;  // 千人将
    } elseif ($points >= 30) {
        $rank = 2;  // 百人隊長
    }

    return [
        'rank' => $rank,
        'points' => $points,
        'categories' => $categories
    ];
}
?>