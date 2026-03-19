<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;

class RbacSeeder extends Seeder
{
    public function run()
    {
        // 1) Create or fetch the admin user
        /** @var UserModel $users */
        $users = model(UserModel::class);

        // If you prefer, change these before running in prod
        $email = 'admin@sonomarin.net';

        $existing = $users->where('email', $email)->first();
        if (! $existing) {
            $admin = new User([
                'username' => 'admin',
                'email'    => $email,
                'password' => 'Winter15Comming',
            ]);
            $users->save($admin);
            $admin = $users->find($users->getInsertID());
        } else {
            $admin = $existing;
        }

        // 2) Create groups (roles) and permissions via Authorization service
        $authorize = service('authorization');

        foreach (['member' => 'Member', 'admin' => 'Admin'] as $slug => $desc) {
            // createGroup() is idempotent; it will no-op if the group exists
            $authorize->createGroup($slug, $desc);
        }

        $perms = ['view_dashboard', 'manage_users', 'manage_roles', 'view_audit'];
        foreach ($perms as $p) {
            $authorize->createPermission($p, $p); // idempotent
        }

        // 3) Grant permissions to groups
        foreach (['manage_users', 'manage_roles', 'view_audit', 'view_dashboard'] as $p) {
            $authorize->addPermissionToGroup($p, 'admin');
        }
        $authorize->addPermissionToGroup('view_dashboard', 'member');

        // 4) Put the admin user in the admin group
        // (User entity helper is fine in CLI)
        if ($admin instanceof User) {
            $admin->addGroup('admin');
        } else {
            // Fallback (if you fetched as array)
            $authorize->addUserToGroup($admin['id'], 'admin');
        }
    }
}