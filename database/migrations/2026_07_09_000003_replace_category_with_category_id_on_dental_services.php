<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dental_services', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('category')
                ->constrained('service_categories')->nullOnDelete();
        });

        // Turn every distinct free-text category already on services into a real
        // row, then point each service at it — so existing data survives the switch
        // from a typed-in string to a picked-from-a-list foreign key.
        DB::table('dental_services')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category')
            ->each(function (string $name) {
                $categoryId = DB::table('service_categories')->insertGetId([
                    'name' => $name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('dental_services')->where('category', $name)->update(['category_id' => $categoryId]);
            });

        Schema::table('dental_services', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }

    public function down(): void
    {
        Schema::table('dental_services', function (Blueprint $table) {
            $table->string('category', 100)->nullable()->after('code');
        });

        DB::table('dental_services')
            ->join('service_categories', 'dental_services.category_id', '=', 'service_categories.id')
            ->update(['dental_services.category' => DB::raw('service_categories.name')]);

        Schema::table('dental_services', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });
    }
};
