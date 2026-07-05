<?php
namespace Database\Seeders;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = ['Makanan & Minuman','Elektronik','Pakaian & Aksesoris','Peralatan Rumah Tangga','Kesehatan & Kecantikan','Alat Tulis & Kantor','Mainan & Hobi','Otomotif'];
        foreach ($categories as $name) {
            Category::create(['name'=>$name,'slug'=>Str::slug($name),'is_active'=>true]);
        }
    }
}
