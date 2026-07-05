<?php
namespace Database\Seeders;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::all();
        $suppliers  = Supplier::all();
        $products = [
            ['name'=>'Aqua Botol 600ml','purchase_price'=>2500,'sale_price'=>4000,'unit'=>'botol','stock'=>150,'min_stock'=>20],
            ['name'=>'Indomie Goreng','purchase_price'=>2800,'sale_price'=>4500,'unit'=>'bungkus','stock'=>200,'min_stock'=>30],
            ['name'=>'Beras Premium 5kg','purchase_price'=>65000,'sale_price'=>80000,'unit'=>'karung','stock'=>50,'min_stock'=>10],
            ['name'=>'Minyak Goreng 1L','purchase_price'=>14000,'sale_price'=>18000,'unit'=>'liter','stock'=>80,'min_stock'=>15],
            ['name'=>'Gula Pasir 1kg','purchase_price'=>12000,'sale_price'=>16000,'unit'=>'kg','stock'=>100,'min_stock'=>20],
            ['name'=>'Sabun Mandi Lifebuoy','purchase_price'=>5000,'sale_price'=>8000,'unit'=>'buah','stock'=>60,'min_stock'=>10],
            ['name'=>'Shampo Pantene 170ml','purchase_price'=>18000,'sale_price'=>25000,'unit'=>'botol','stock'=>40,'min_stock'=>8],
            ['name'=>'Pasta Gigi Pepsodent 190g','purchase_price'=>9000,'sale_price'=>14000,'unit'=>'tube','stock'=>55,'min_stock'=>10],
            ['name'=>'Kopi Kapal Api Sachet','purchase_price'=>1200,'sale_price'=>2000,'unit'=>'sachet','stock'=>300,'min_stock'=>50],
            ['name'=>'Teh Botol Sosro 450ml','purchase_price'=>4500,'sale_price'=>7000,'unit'=>'botol','stock'=>120,'min_stock'=>20],
            ['name'=>'Pulpen Pilot G2','purchase_price'=>8000,'sale_price'=>12000,'unit'=>'buah','stock'=>30,'min_stock'=>5],
            ['name'=>'Buku Tulis Sidu 58 Lembar','purchase_price'=>4500,'sale_price'=>7500,'unit'=>'buah','stock'=>80,'min_stock'=>15],
        ];
        foreach ($products as $i => $p) {
            $cat = $categories->random();
            $code = 'PRD-'.str_pad($i+1,5,'0',STR_PAD_LEFT);
            $product = Product::create(array_merge($p,['code'=>$code,'category_id'=>$cat->id,'barcode'=>'890'.str_pad($i+1,10,'0',STR_PAD_LEFT),'is_active'=>true]));
            $product->suppliers()->sync($suppliers->random(rand(1,2))->pluck('id')->toArray());
        }
    }
}
