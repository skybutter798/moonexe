<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\DirectRange;
use App\Mail\SupportContactMail;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Session;

use Illuminate\Support\Facades\Mail;
use Carbon\Carbon; 
use App\Services\TelegramService;
use App\Services\TrxService;

class UserController extends Controller
{
    public function index(Request $request)
    {
        // Lookup data for filter dropdowns
        $packages = DirectRange::pluck('name','id');
        $roles    = User::distinct('role')->pluck('role');

        // Base query: eager-load relations, skip id=1, order by id asc
        $q = User::with(['wallet','upline','packageModel'])
                 ->where('id', '!=', 1)
                 ->orderBy('id','asc');

        // 1) Name
        if ($request->filled('name')) {
            $q->where('name', 'like', '%'.$request->name.'%');
        }

        // 2) Email
        if ($request->filled('email')) {
            $q->where('email', 'like', '%'.$request->email.'%');
        }

        // 3) Upline
        if ($request->filled('upline')) {
            $q->whereHas('upline', function($q2) use ($request) {
                $q2->where('name', 'like', '%'.$request->upline.'%');
            });
        }

        // 4) Status (active/deactive)
        if ($request->filled('status')) {
            if ($request->status === '1') {
                $q->where('status','!=', 0);
            } elseif ($request->status === '0') {
                $q->where('status', 0);
            }
        }

        // 5) Package
        if ($request->filled('package_id')) {
            $q->where('package', $request->package_id);
        }

        // 6) Role
        if ($request->filled('role')) {
            $q->where('role', $request->role);
        }

        // 7) Bonus
        if ($request->filled('bonus')) {
            $q->where('bonus', 'like', '%'.$request->bonus.'%');
        }

        // 8) Type (robot/normal/none)
        if ($request->filled('type')) {
            switch ($request->type) {
                case 'robot':
                    $q->where('status', 2);
                    break;
                case 'normal':
                    $q->where('status', 1);
                    break;
                case 'none':
                    $q->where('status', 0);
                    break;
            }
        }

        // 9) Created At date
        if ($request->filled('date')) {
            $q->whereDate('created_at', $request->date);
        }

        // Paginate & preserve filters
        $users = $q->paginate(15)->appends($request->query());

        return view('app.users.index', compact('users','packages','roles'));
    }

    public function walletBreakdown($id)
    {
        $data = \App\Services\WalletBreakdownService::generate($id);
    
        // Fetch topup transfers (no date filter)
        $topups = \App\Models\Transfer::where('user_id', $id)
            ->where('status', 'Completed')
            ->where('remark', 'package')
            ->orderBy('created_at', 'desc')
            ->get();
    
        // Create a basic HTML table
        $html = '<table class="table table-bordered table-sm">';
        $html .= '<thead><tr><th>Date</th><th>Amount</th><th>TXID</th></tr></thead><tbody>';
        foreach ($topups as $t) {
            $html .= '<tr>';
            $html .= '<td>' . $t->created_at->format('d M Y H:i') . '</td>';
            $html .= '<td>' . number_format($t->amount, 4) . '</td>';
            $html .= '<td>' . e($t->txid) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
    
        $data['topups'] = $html;
    
        return response()->json($data);
    }
    
    public function disable($id)
    {
        $user = User::findOrFail($id);
        $user->status = 0;
        $user->save();

        return redirect()->route('admin.users.index')
                         ->with('success', 'User disabled successfully.');
    }
    
    public function enable($id)
    {
        $user = User::findOrFail($id);
        $user->status = 1;
        $user->save();
    
        return redirect()->route('admin.users.index')
                         ->with('success', 'User enabled successfully.');
    }
    
    public function update(Request $request, $id)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'referral_code'  => 'nullable|string|max:255',
            'status'         => 'required|boolean',
            'referral'       => 'nullable|exists:users,id',
        ]);
    
        $user = User::findOrFail($id);
        $user->update([
            'name'           => $request->name,
            'referral_code'  => $request->referral_code,
            'status'         => $request->status,
            'referral'       => $request->referral,
        ]);
    
        return redirect()->route('admin.users.index')
                         ->with('success', 'User updated successfully.');
    }
    
    public function generateWalletAddress(Request $request, TelegramService $telegram, TrxService $trxService)
    {
        $user = Auth::user();
        $chatId = '-1002720623603';
    
        \Log::channel('admin')->info('[Wallet] Generating wallet address request received', [
            'user_id'    => $user->id,
            'email'      => $user->email,
            'ip'         => $request->ip(),
            'user_agent' => $request->userAgent(),
            'has_wallet' => !empty($user->wallet_address),
            'timestamp'  => now()->toDateTimeString(),
        ]);
    
        $telegram->sendMessage("Wallet clicked for User ID: {$user->id} ({$user->email})", $chatId);
    
        // Skip only if BOTH wallet_address AND trx_address exist
        if ($user->trx_address) {
            $fixedPath = str_replace('storage/', '', $user->trx_qr);
        
            return response()->json([
                'message'        => 'TRX wallet generated',
                'wallet_address' => $user->trx_address,
                'wallet_qr'      => asset("storage/{$fixedPath}"), // Final: https://app.moonexe.com/storage/trxqr/3.png
            ]);
        }
    
        $telegram->sendMessage("Generating wallet for User ID: {$user->id} ({$user->email})", $chatId);
    
        if (is_null($user->trx_address)) {
            \Log::channel('admin')->info('[Wallet] trx_address is null. Proceeding to call TrxService.');
        
            $response = $trxService->createAccount((string) $user->id);
        
            \Log::channel('admin')->info('[Wallet] TRX Response from TrxService', [
                'response' => $response
            ]);
        
            if ($response['status'] === 'success') {
                $address = $response['message'];
                \Log::channel('admin')->info('[Wallet] Received TRX address', [
                    'user_id' => $user->id,
                    'address' => $address
                ]);
        
                $qrPath = "storage/trxqr/{$user->id}.png";
        
                try {
                    \QrCode::format('png')->size(300)->generate($address, public_path($qrPath));
                    \Log::channel('admin')->info('[Wallet] QR Code generated and saved', [
                        'user_id' => $user->id,
                        'qr_path' => $qrPath
                    ]);
                } catch (\Throwable $e) {
                    \Log::channel('admin')->error('[Wallet] QR Code generation failed', [
                        'user_id' => $user->id,
                        'error'   => $e->getMessage(),
                    ]);
                }
        
                $user->trx_address = $address;
                $user->trx_qr = $qrPath;
        
                \Log::channel('admin')->info('[Wallet] About to save user TRX info', [
                    'user_id'     => $user->id,
                    'trx_address' => $user->trx_address,
                    'trx_qr'      => $user->trx_qr,
                ]);
        
                $user->save();
        
                \Log::channel('admin')->info('[Wallet] User TRX info saved to DB', [
                    'user_id' => $user->id,
                ]);
        
                $telegram->sendMessage("✅ TRX Wallet created for {$user->id}: <code>{$user->trx_address}</code>", $chatId);
        
                $fixedPath = str_replace('storage/', '', $user->trx_qr);

                return response()->json([
                    'message'        => 'TRX wallet generated',
                    'wallet_address' => $user->trx_address,
                    'wallet_qr'      => asset("storage/{$fixedPath}"),
                    'wallet_expired' => $user->wallet_expired
                ]);
            }
        
            \Log::channel('admin')->error('[Wallet] TRX Wallet Generation Failed', [
                'user_id'  => $user->id,
                'response' => $response,
            ]);
        
            $telegram->sendMessage("❌ TRX wallet generation failed for {$user->id}", $chatId);
        
            return response()->json(['error' => 'Failed to generate TRX wallet'], 500);
        }

        // Existing logic for ARB
        /*$eligibleIds = array_merge(range(620, 625));
        if (in_array($user->id, $eligibleIds)) {
            $response = Http::post('https://app.arbitrumium.xyz/api/generate-wallet', [
                'merchant_code' => env('ARBCODE'),
                'secret_key'    => env('ARBKEY'),
                'userid'        => $user->id,
            ]);
    
            \Log::channel('admin')->info('[Wallet] ARB Raw Response', [
                'body'   => $response->body(),
                'status' => $response->status()
            ]);
    
            if ($response->ok() && $response->json('address')) {
                $data = $response->json();
    
                $user->wallet_address = $data['address'];
                $user->wallet_qr = $data['qr_code'] ?? null;
                $user->wallet_expired = now()->addMonths(3);
                $user->save();
    
                $telegram->sendMessage("✅ Wallet (ARB) created for {$user->id}: <code>{$user->wallet_address}</code>", $chatId);
    
                return response()->json([
                    'message'         => 'Wallet generated (ARB)',
                    'wallet_address'  => $user->wallet_address,
                    'wallet_qr'       => $user->wallet_qr,
                    'wallet_expired'  => $user->wallet_expired
                ]);
            }
    
            \Log::channel('admin')->error('[Wallet] Arbitrumium API failed', [
                'user_id'  => $user->id,
                'response' => $response->body(),
            ]);
    
            $telegram->sendMessage("❌ ARB wallet generation failed for {$user->id}", $chatId);
    
            return response()->json(['error' => 'Failed to generate wallet address (ARB)'], 500);
        }*/
    
        return response()->json(['error' => 'Failed to generate wallet address'], 500);
    }
    
    public function contactSupport(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string',
            'email'    => 'required|email',
            'subject'  => 'required|string',
            'question' => 'required|string',
        ]);
    
        // Send to support inbox
        Mail::to('support@moonexe.com')->send(new SupportContactMail($data));
    
        return back()->with('success', 'Support message sent successfully. We will respond as soon as possible.');
    }
    
    public function impersonate($id)
    {
        $adminId = Auth::id();
        $user = User::findOrFail($id);
    
        // Prevent impersonating another admin
        if ($user->is_admin) {
            return redirect()->back()->with('error', 'Cannot impersonate another admin.');
        }
    
        Session::put('impersonate_admin_id', $adminId);
        Auth::login($user);
    
        // Redirect to user dashboard
        return redirect('/user-dashboard/dashboard')->with('success', 'Now impersonating: ' . $user->name);
    }
    
    public function leaveImpersonation()
    {
        $adminId = Session::pull('impersonate_admin_id');
    
        if ($adminId) {
            $admin = User::find($adminId);
            Auth::login($admin); // Restore admin session
            return redirect('/admin/users')->with('success', 'Returned to admin.');
        }
    
        return redirect('/')->with('error', 'Not impersonating anyone.');
    }

    
}
