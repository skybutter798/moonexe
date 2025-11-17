<?php

namespace App\Exports;

use App\Models\Withdrawal;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\Exportable;

class WithdrawalsExport implements FromView
{
    use Exportable;

    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function view(): View
    {
        $query = Withdrawal::with('user')
            ->whereNotIn('id', [61, 63])
            ->whereHas('user', function ($q) {
                $q->where('id', '!=', 665);
            })
            ->orderBy('created_at', 'desc');

        if (!empty($this->filters['username'])) {
            $query->whereHas('user', function($q) {
                $q->where('name', 'like', '%'.$this->filters['username'].'%');
            });
        }

        if (!empty($this->filters['txid'])) {
            $query->where('txid', 'like', '%'.$this->filters['txid'].'%');
        }

        if (!empty($this->filters['trc20_address'])) {
            $query->where('trc20_address', 'like', '%'.$this->filters['trc20_address'].'%');
        }

        if (!empty($this->filters['amount'])) {
            $val = number_format((float)$this->filters['amount'], 2, '.', '');
            $query->whereRaw('FORMAT(amount, 2) = ?', [$val]);
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['start_date']) && !empty($this->filters['end_date'])) {
            $query->whereBetween('created_at', [
                $this->filters['start_date'].' 00:00:00',
                $this->filters['end_date'].' 23:59:59',
            ]);
        } elseif (!empty($this->filters['start_date'])) {
            $query->whereDate('created_at', '>=', $this->filters['start_date']);
        } elseif (!empty($this->filters['end_date'])) {
            $query->whereDate('created_at', '<=', $this->filters['end_date']);
        }

        $withdrawals = $query->get();

        return view('admin.withdrawals.export', compact('withdrawals'));
    }
}