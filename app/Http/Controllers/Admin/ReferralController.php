<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class ReferralController extends Controller
{

    public function index()
{
    $users = User::where('id', '!=', 1)->get();

    $grouped = [];
    foreach ($users as $u) {
        // remap children of 1 up to top‐level
        $parent = $u->referral === 1 ? null : $u->referral;
        $grouped[$parent][] = $u;
    }

    $tree = $this->buildTree(null, $grouped);

    return view('admin.referrals.index', compact('tree'));
}

    
    private function buildTree($parentId, array &$grouped): array
    {
        $branch = [];
    
        if (isset($grouped[$parentId])) {
            foreach ($grouped[$parentId] as $user) {
                // always include a children array, even if empty
                $user->children = $this->buildTree($user->id, $grouped);
                $branch[]      = $user;
            }
        }
    
        return $branch;
    }

}