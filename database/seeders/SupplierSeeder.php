<?php
namespace Database\Seeders;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            ['name'=>'PT. Sumber Makmur','email'=>'info@sumbermakmur.com','phone'=>'02112345678','city'=>'Jakarta','address'=>'Jl. Industri No. 1, Jakarta Barat'],
            ['name'=>'CV. Berkah Jaya','email'=>'order@berkahjaya.co.id','phone'=>'02298765432','city'=>'Surabaya','address'=>'Jl. Raya Darmo No. 45, Surabaya'],
            ['name'=>'UD. Karya Mandiri','email'=>'karya.mandiri@gmail.com','phone'=>'02411234567','city'=>'Semarang','address'=>'Jl. Pemuda No. 78, Semarang'],
            ['name'=>'PT. Global Distribusi','email'=>'sales@globaldist.com','phone'=>'02111111111','city'=>'Bandung','address'=>'Jl. Asia Afrika No. 100, Bandung'],
        ];
        foreach ($suppliers as $i => $s) {
            Supplier::create(array_merge($s, ['code'=>'SUP-'.str_pad($i+1,4,'0',STR_PAD_LEFT),'is_active'=>true]));
        }
    }
}
