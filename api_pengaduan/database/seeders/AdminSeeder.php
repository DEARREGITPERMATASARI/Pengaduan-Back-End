<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // insert data ke table admin
        \App\Models\User::create([
            'id'=> 1,
        	'nik' => 3562730001,
        	'nama' => 'Dear Regita Permatasari',
        	'telp' => '082177371875',
            'username' => '@dear.com',
            'password' => bcrypt('000000'),
        	'level' => 'admin'
        ]);
    }
}
