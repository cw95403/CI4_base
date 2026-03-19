<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Models\UserModel;

class FixAdminSeeder extends Seeder
{
    public function run()
    {
        $users = model(UserModel::class);
        $user  = $users->find(1); // assuming id=1 is your admin

        if ($user) {
            // Reset password to known value
            $user->fill(['password' => 'ChangeMe!123']);
            $users->save($user);

            // Attach to admin group
            $authorize = service('authorization');
            $authorize->createGroup('admin', 'Admin'); // safe if it already exists
            $authorize->addUserToGroup($user->id, 'admin');

            echo "✅ Admin fixed: user=admin@example.com / password=ChangeMe!123\n";
        } else {
            echo "⚠️ No user with ID=1 found.\n";
        }
    }
}