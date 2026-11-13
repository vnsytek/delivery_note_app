<?php

return [
    // Danh sách tần suất từ tiếng Việt dùng để đoán chữ bị mất dấu (mỗi dòng: "từ số_lần").
    // Nguồn: hermitdave/FrequencyWords (OpenSubtitles 2018, vi_50k), dữ liệu CC-BY-SA 4.0, mã MIT.
    'vocabulary' => env('ACCENT_VOCABULARY', resource_path('data/vi-words.txt')),

    // Từ và cụm từ của ngành (địa chỉ, hàng IT/văn phòng): được ưu tiên khi đoán, và giúp đoán theo ngữ cảnh.
    'domain' => env('ACCENT_DOMAIN', resource_path('data/vi-domain.txt')),

    // Một từ có nhiều '?' hơn số này thì không tự đoán (quá nhiều khả năng), để người chọn.
    'max_unknown_letters' => 3,

    // "Chắc chắn" khi điểm của phương án tốt nhất hơn phương án thứ hai ít nhất chừng này (thang log).
    'confidence_margin' => 1.5,
];
