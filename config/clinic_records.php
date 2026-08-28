<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Thư mục Excel hệ thống cũ
    |--------------------------------------------------------------------------
    |
    | Nơi chứa bộ file "DuLieuHeThong" (18-19ok.xlsx … 25-26.xlsx) dùng để đối
    | chiếu và bù dữ liệu cho bảng clinic_records. Chỉ dùng cho lệnh artisan
    | chạy thủ công; đổi bằng biến môi trường hoặc tuỳ chọn --dir.
    |
    */

    'legacy_dir' => env('CLINIC_RECORDS_LEGACY_DIR', storage_path('app/legacy-excel')),

];
