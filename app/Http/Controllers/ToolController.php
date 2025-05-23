<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use App\Events\CampaignBalanceUpdated;
use Illuminate\Support\Facades\Log;

class ToolController extends Controller
{
    public function index(Request $request)
    {
        // Check password submission
        if ($request->isMethod('post') && $request->input('password')) {
            if ($request->input('password') === 'moonexe@1') {
                session(['tool_authenticated' => true]);
            } else {
                return back()->withErrors(['password' => 'Incorrect password']);
            }
        }
    
        // Check if authenticated
        if (!session('tool_authenticated')) {
            return view('tool_password');
        }
    
        // Fetch settings
        $settings = Setting::whereIn('name', [
            'cam_balance', 'cam_min_time', 'cam_max_time', 'cam_min_buy', 'cam_max_buy'
        ])->pluck('value', 'name');
    
        return view('tool', compact('settings'));
    }
    
    public function update(Request $request)
    {
        Log::info('⚙️ ToolController::update triggered!');
    
        $validated = $request->validate([
            'cam_min_time' => 'required|numeric',
            'cam_max_time' => 'required|numeric',
            'cam_min_buy' => 'required|numeric',
            'cam_max_buy' => 'required|numeric',
            'adjust_amount' => 'nullable|numeric|min:0',
            'adjust_type' => 'nullable|in:buy,return'
        ]);
    
        // Update individual setting values
        foreach (['cam_min_time', 'cam_max_time', 'cam_min_buy', 'cam_max_buy'] as $key) {
            Setting::updateOrCreate(
                ['name' => $key],
                ['value' => $validated[$key]]
            );
        }
    
        // Adjust campaign balance if amount is provided and not zero
        if (!is_null($validated['adjust_amount']) && $validated['adjust_amount'] > 0 && !empty($validated['adjust_type'])) {
            $balance = Setting::where('name', 'cam_balance')->first();
            if ($balance) {
                $newValue = $balance->value;
    
                if ($validated['adjust_type'] === 'buy') {
                    $newValue = max(0, $balance->value - $validated['adjust_amount']);
                } elseif ($validated['adjust_type'] === 'return') {
                    $newValue = $balance->value + $validated['adjust_amount'];
                }
    
                $balance->value = $newValue;
                $balance->save();
    
                // Only trigger event when balance is actually changed
                \Log::info('🔥 Triggering CampaignBalanceUpdated...');
                event(new CampaignBalanceUpdated($balance->value));
                \Log::info('🔔 Event triggered with value: ' . $balance->value);
            }
        }
    
        return redirect()->route('tool.index')->with('success', 'Campaign settings updated successfully.');
    }


}
